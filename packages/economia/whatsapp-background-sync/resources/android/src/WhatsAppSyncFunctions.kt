package com.economia.whatsappsync

import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.google.android.gms.tasks.Tasks
import com.google.firebase.messaging.FirebaseMessaging
import com.nativephp.mobile.bridge.BridgeFunction
import java.util.concurrent.TimeUnit

/**
 * Bridge functions del plugin de sync en background.
 * Namespace: "WhatsAppSync.*"
 *
 * El core de NativePHP expone el facade PushNotifications pero el scaffold
 * no implementa ningún bridge "PushNotification.*" (eso vive en el plugin
 * premium mobile-firebase), así que la app obtiene el token FCM por aquí y
 * lo sube al servidor por HTTP normal.
 */
object WhatsAppSyncFunctions {

    private const val TAG = "WhatsAppSyncFunctions"

    /**
     * Devuelve el token FCM del dispositivo: {"token": "..."} o
     * {"error": "..."} si Firebase no está disponible. Bloqueante con
     * timeout corto; el bridge corre fuera del hilo principal.
     */
    class GetFcmToken(@Suppress("unused") private val activity: FragmentActivity) : BridgeFunction {

        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            return try {
                val token = Tasks.await(FirebaseMessaging.getInstance().token, 10, TimeUnit.SECONDS)
                Log.i(TAG, "FCM token obtained (${token.take(12)}…)")
                mapOf("token" to token)
            } catch (e: Exception) {
                Log.w(TAG, "FCM token unavailable: ${e.message}")
                mapOf("error" to (e.message ?: "token unavailable"))
            }
        }
    }
}
