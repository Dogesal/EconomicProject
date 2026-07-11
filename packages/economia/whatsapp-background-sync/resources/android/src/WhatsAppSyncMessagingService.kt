package com.economia.whatsappsync

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.util.Log
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import com.nativephp.mobile.R
import com.nativephp.mobile.bridge.PHPBridge
import com.nativephp.mobile.ui.MainActivity
import org.json.JSONObject
import java.io.File

/**
 * Recibe el push FCM de datos {action: whatsapp_pull} — incluso con la app
 * cerrada — y registra los movimientos pendientes sin intervención del
 * usuario: arranca un runtime PHP efímero (contexto TSRM propio, coexiste
 * con el runtime persistente si la app está abierta) y ejecuta el comando
 * `whatsapp:sync-headless`. Al terminar muestra una notificación informativa
 * con el resultado.
 *
 * FCM con prioridad HIGH otorga ~10-20s de ejecución: suficiente para el
 * boot de PHP (~1-3s) y las llamadas HTTP del sync (timeouts de 2-3s). Si el
 * sistema mata el proceso antes, el movimiento queda pendiente y se aplica
 * al abrir la app (red de seguridad existente).
 */
class WhatsAppSyncMessagingService : FirebaseMessagingService() {

    companion object {
        private const val TAG = "WhatsAppSync"
        private const val CHANNEL_ID = "whatsapp_sync"
        private const val NOTIFICATION_ID = 42_001
    }

    override fun onMessageReceived(message: RemoteMessage) {
        if (message.data["action"] != "whatsapp_pull") {
            return
        }

        Log.i(TAG, "whatsapp_pull push received — running headless sync")

        val bridge = PHPBridge(applicationContext)
        val bootstrap = "${bridge.getLaravelPath()}/vendor/nativephp/mobile/bootstrap/android/persistent.php"

        if (!File(bootstrap).exists()) {
            Log.w(TAG, "Laravel bundle not extracted yet — skipping (first app open pending)")
            return
        }

        try {
            // Si el runtime persistente vive, el proceso ya inicializó PHP;
            // repetir la init de proceso podría corromper el estado nativo.
            if (!bridge.nativeIsPersistentRuntimeLive()) {
                bridge.ensureRuntimeInitialized()
            }

            if (bridge.nativeEphemeralBoot(bootstrap) != 0) {
                Log.e(TAG, "Ephemeral PHP boot failed")
                return
            }

            val output = try {
                bridge.nativeEphemeralArtisan("whatsapp:sync-headless")
            } finally {
                bridge.nativeEphemeralShutdown()
            }

            Log.i(TAG, "Sync output: ${output.take(300)}")
            notifyResult(output)
        } catch (t: Throwable) {
            Log.e(TAG, "Background sync failed", t)
        }
    }

    override fun onNewToken(token: String) {
        // El token rotado se re-sube en el siguiente sync dentro de la app
        // (enroll de PushNotifications); aquí solo queda constancia.
        Log.i(TAG, "FCM token rotated")
    }

    /**
     * Muestra el resumen ("Se registró 1 movimiento…") si el sync aplicó o
     * falló algo. El comando imprime una línea JSON {"applied":N,"failed":N}.
     */
    private fun notifyResult(output: String) {
        val json = output.lineSequence()
            .map { it.trim() }
            .lastOrNull { it.startsWith("{") && it.contains("applied") }
            ?: return

        val (applied, failed) = try {
            val parsed = JSONObject(json)
            parsed.optInt("applied", 0) to parsed.optInt("failed", 0)
        } catch (e: Exception) {
            Log.w(TAG, "Unparseable sync output")
            return
        }

        if (applied == 0 && failed == 0) {
            return
        }

        val parts = mutableListOf<String>()
        if (applied > 0) {
            parts += if (applied == 1) "Se registró 1 movimiento" else "Se registraron $applied movimientos"
        }
        if (failed > 0) {
            parts += if (failed == 1) "1 falló" else "$failed fallaron"
        }
        val body = parts.joinToString(" · ")

        val manager = NotificationManagerCompat.from(this)
        if (!manager.areNotificationsEnabled()) {
            return
        }

        val channel = NotificationChannel(
            CHANNEL_ID,
            "Movimientos de WhatsApp",
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

        val notification = NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.mipmap.ic_launcher)
            .setContentTitle("WhatsApp · Mi Economía")
            .setContentText(body)
            .setContentIntent(tapIntent)
            .setAutoCancel(true)
            .build()

        try {
            manager.notify(NOTIFICATION_ID, notification)
        } catch (e: SecurityException) {
            Log.w(TAG, "POST_NOTIFICATIONS not granted")
        }
    }
}
