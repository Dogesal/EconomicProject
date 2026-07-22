package com.economia.voicewidget

import android.app.Activity
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.speech.RecognizerIntent
import android.util.Base64
import android.util.Log
import android.widget.Toast
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import com.nativephp.mobile.R
import com.nativephp.mobile.bridge.LaravelEnvironment
import com.nativephp.mobile.bridge.PHPBridge
import com.nativephp.mobile.ui.MainActivity
import org.json.JSONObject
import java.io.File
import kotlin.concurrent.thread

/**
 * Activity transparente que lanza el widget de voz: abre el diálogo de
 * reconocimiento de voz de Google (RecognizerIntent, sin permiso de
 * micrófono propio) y entrega el texto transcrito:
 *
 * - App viva: window.__sendVoiceNote(texto) en el webview (el POST corre
 *   dentro de la app, sin pelear con el runtime persistente por la DB) y
 *   trae la app al frente para que el reply se vea como flash.
 * - App cerrada: runtime PHP efímero + `voice:send-headless` (mismo patrón
 *   que el sync de WhatsApp en background); el reply se muestra como
 *   notificación. La activity queda viva (invisible) mientras el LLM
 *   responde para que el sistema no mate el proceso.
 */
class VoiceCaptureActivity : Activity() {

    companion object {
        private const val TAG = "VoiceWidget"
        private const val SPEECH_REQUEST = 7001
        private const val CHANNEL_ID = "voice_widget"
        private const val NOTIFICATION_ID = 42_002
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val speechIntent = Intent(RecognizerIntent.ACTION_RECOGNIZE_SPEECH).apply {
            putExtra(RecognizerIntent.EXTRA_LANGUAGE_MODEL, RecognizerIntent.LANGUAGE_MODEL_FREE_FORM)
            putExtra(RecognizerIntent.EXTRA_LANGUAGE, "es")
            putExtra(RecognizerIntent.EXTRA_PROMPT, "Dime tu gasto, ingreso, deuda o consulta 🎙️")
        }

        // Sin resolveActivity(): desde Android 11 devuelve null si no se
        // declara <queries> (package visibility) y el compilador de plugins
        // no puede inyectar ese bloque. Intentar y capturar es más robusto.
        try {
            @Suppress("DEPRECATION")
            startActivityForResult(speechIntent, SPEECH_REQUEST)
        } catch (e: android.content.ActivityNotFoundException) {
            Toast.makeText(this, "Este dispositivo no tiene reconocimiento de voz 🎙️", Toast.LENGTH_LONG).show()
            finish()
        }
    }

    @Deprecated("Deprecated in Java")
    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)

        if (requestCode != SPEECH_REQUEST) {
            return
        }

        val text = data?.getStringArrayListExtra(RecognizerIntent.EXTRA_RESULTS)
            ?.firstOrNull()
            ?.trim()

        if (resultCode != Activity.RESULT_OK || text.isNullOrEmpty()) {
            // Siempre dar feedback: cancelado por el usuario vs. nada
            // transcrito son los dos únicos casos que llegan aquí.
            Log.i(TAG, "Speech result=$resultCode, empty=${text.isNullOrEmpty()}")
            if (resultCode != Activity.RESULT_CANCELED) {
                Toast.makeText(this, "No te escuché 🎙️ Intenta de nuevo", Toast.LENGTH_SHORT).show()
            }
            finish()
            return
        }

        Log.i(TAG, "Voice note transcribed (${text.length} chars)")

        val bridge = PHPBridge(applicationContext)

        // Solo delegar en el webview cuando la app está de verdad en primer
        // plano: con la app en background el runtime sigue "vivo" pero el
        // sistema puede matar el proceso en cualquier momento (MIUI lo hace
        // agresivamente), y el POST se perdería en silencio. Desde el widget
        // ese es el caso normal, así que casi siempre va por headless.
        val mainActivity = MainActivity.instance?.takeIf { it.hasWindowFocus() }

        if (bridge.nativeIsPersistentRuntimeLive() && mainActivity != null) {
            val jsText = JSONObject.quote(text)
            mainActivity.runOnUiThread {
                mainActivity.getWebView().evaluateJavascript(
                    "window.__sendVoiceNote && window.__sendVoiceNote($jsText);",
                    null
                )
            }
            startActivity(Intent(this, MainActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            })
            finish()
            return
        }

        Toast.makeText(this, "Registrando… 🎙️", Toast.LENGTH_SHORT).show()

        thread {
            val output = runHeadless(bridge, text)
            runOnUiThread {
                if (output != null) {
                    notifyReply(output)
                } else {
                    Toast.makeText(this, "No se pudo registrar tu nota de voz", Toast.LENGTH_LONG).show()
                }
                finish()
            }
        }
    }

    /**
     * Arranca un runtime PHP efímero y corre `voice:send-headless`. El texto
     * viaja en base64 para sobrevivir tildes, comillas y espacios en la
     * línea de comandos. Devuelve la salida cruda del comando o null.
     */
    private fun runHeadless(bridge: PHPBridge, text: String): String? {
        val bootstrap = "${bridge.getLaravelPath()}/vendor/nativephp/mobile/bootstrap/android/persistent.php"

        if (!File(bootstrap).exists()) {
            Log.w(TAG, "Laravel bundle not extracted yet — open the app once first")
            runOnUiThread {
                Toast.makeText(this, "Abre Mi Economía una vez y vuelve a intentar 📱", Toast.LENGTH_LONG).show()
            }
            return null
        }

        return try {
            LaravelEnvironment(applicationContext).initialize()
            bridge.ensureRuntimeInitialized()

            if (bridge.nativeEphemeralBoot(bootstrap) != 0) {
                Log.e(TAG, "Ephemeral PHP boot failed")
                return null
            }

            val encoded = Base64.encodeToString(text.toByteArray(Charsets.UTF_8), Base64.NO_WRAP)

            try {
                bridge.nativeEphemeralArtisan("voice:send-headless --text-base64=$encoded")
            } finally {
                bridge.nativeEphemeralShutdown()
            }
        } catch (t: Throwable) {
            Log.e(TAG, "Headless voice note failed", t)
            null
        }
    }

    /**
     * Muestra el reply del servidor ("📝 Gasto de 50.00 · comida · hoy." o
     * la respuesta a una consulta) como notificación. El comando imprime una
     * línea JSON {"reply":"...","applied":N,"ok":bool}.
     */
    private fun notifyReply(output: String) {
        val json = output.lineSequence()
            .map { it.trim() }
            .lastOrNull { it.startsWith("{") && it.contains("reply") }

        val reply = try {
            json?.let { JSONObject(it).optString("reply") }?.takeIf { it.isNotEmpty() }
        } catch (e: Exception) {
            null
        }

        if (reply == null) {
            Log.w(TAG, "Unparseable voice output: ${output.take(200)}")
            Toast.makeText(this, "No se pudo registrar tu nota de voz", Toast.LENGTH_LONG).show()
            return
        }

        val manager = NotificationManagerCompat.from(this)
        if (!manager.areNotificationsEnabled()) {
            Toast.makeText(this, reply, Toast.LENGTH_LONG).show()
            return
        }

        val channel = NotificationChannel(
            CHANNEL_ID,
            "Notas de voz",
            NotificationManager.IMPORTANCE_DEFAULT
        )
        (getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager)
            .createNotificationChannel(channel)

        val tapIntent = PendingIntent.getActivity(
            this,
            0,
            Intent(this, MainActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            },
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val largeIcon = runCatching {
            android.graphics.BitmapFactory.decodeResource(resources, R.mipmap.ic_launcher_foreground)
        }.getOrNull()

        val notification = NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.mipmap.ic_launcher)
            .setLargeIcon(largeIcon)
            .setColor(0xFF4F46E5.toInt())
            .setContentTitle("Nota de voz · Mi Economía")
            .setContentText(reply)
            .setStyle(NotificationCompat.BigTextStyle().bigText(reply))
            .setContentIntent(tapIntent)
            .setAutoCancel(true)
            .build()

        try {
            manager.notify(NOTIFICATION_ID, notification)
        } catch (e: SecurityException) {
            Log.w(TAG, "POST_NOTIFICATIONS not granted")
            Toast.makeText(this, reply, Toast.LENGTH_LONG).show()
        }
    }
}
