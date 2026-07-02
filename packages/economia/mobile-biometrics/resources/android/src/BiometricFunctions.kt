package com.economia.biometrics

import android.app.KeyguardManager
import android.os.Build
import android.util.Log
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricManager.Authenticators
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject

/**
 * Bridge functions for biometric authentication.
 * Namespace: "Biometric.*" (same contract as the JS core `Biometrics.prompt()`)
 */
object BiometricFunctions {

    private const val TAG = "BiometricFunctions"
    private const val DEFAULT_EVENT = "Native\\Mobile\\Events\\Biometric\\Completed"

    /**
     * Shows the biometric prompt (fingerprint/face) falling back to the
     * device credential (PIN/pattern). The result is delivered
     * asynchronously through the `Completed` event; the bridge call itself
     * returns immediately.
     *
     * Parameters (all optional):
     *   - event: string - Event class to dispatch (default: core Biometric\Completed)
     *   - id: string - Correlation id echoed back in the event payload
     *   - title / subtitle: string - Prompt texts
     */
    class Prompt(private val activity: FragmentActivity) : BridgeFunction {

        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val eventClass = parameters["event"] as? String ?: DEFAULT_EVENT
            val id = parameters["id"] as? String
            val title = parameters["title"] as? String ?: "Desbloquear"
            val subtitle = parameters["subtitle"] as? String ?: "Confirmá tu identidad para continuar"

            Log.i(TAG, "Prompt requested (API ${Build.VERSION.SDK_INT})")

            // BiometricPrompt must be created and shown on the main thread.
            activity.runOnUiThread {
                try {
                    showPrompt(eventClass, id, title, subtitle)
                } catch (e: Exception) {
                    Log.e(TAG, "Failed to show biometric prompt", e)
                    dispatchResult(eventClass, false, id, -1, "EXCEPTION: ${e.message}")
                }
            }

            return mapOf("status" to "prompted")
        }

        private fun showPrompt(eventClass: String, id: String?, title: String, subtitle: String) {
            val biometricStatus = BiometricManager.from(activity)
                .canAuthenticate(Authenticators.BIOMETRIC_WEAK)
            val biometricOk = biometricStatus == BiometricManager.BIOMETRIC_SUCCESS

            val keyguard = ContextCompat.getSystemService(activity, KeyguardManager::class.java)
            val credentialOk = keyguard?.isDeviceSecure == true

            Log.i(TAG, "Availability: biometric=$biometricStatus credential=$credentialOk")

            if (!biometricOk && !credentialOk) {
                Log.w(TAG, "No biometric hardware/enrollment and no device credential (code $biometricStatus)")
                dispatchResult(eventClass, false, id, biometricStatus, "UNAVAILABLE (code $biometricStatus)")
                return
            }

            val builder = BiometricPrompt.PromptInfo.Builder()
                .setTitle(title)
                .setSubtitle(subtitle)
                .setConfirmationRequired(false)

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                // API 30+: modern authenticators API supports the combination.
                builder.setAllowedAuthenticators(Authenticators.BIOMETRIC_WEAK or Authenticators.DEVICE_CREDENTIAL)
            } else if (credentialOk) {
                // API < 30: the combined-authenticators flag set is not supported;
                // the deprecated call shims biometric + credential via Keyguard.
                @Suppress("DEPRECATION")
                builder.setDeviceCredentialAllowed(true)
            } else {
                // Biometric-only devices without a secure lock screen need an
                // explicit negative button.
                builder.setNegativeButtonText("Cancelar")
            }

            val callback = object : BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                    Log.i(TAG, "Authentication succeeded")
                    dispatchResult(eventClass, true, id, 0, null)
                }

                override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                    // Includes user cancellation (ERROR_USER_CANCELED / ERROR_NEGATIVE_BUTTON).
                    Log.w(TAG, "Authentication error $errorCode: $errString")
                    dispatchResult(eventClass, false, id, errorCode, errString.toString())
                }

                // onAuthenticationFailed() is a single failed attempt; the
                // prompt stays open and the user can retry, so no dispatch.
            }

            BiometricPrompt(activity, ContextCompat.getMainExecutor(activity), callback)
                .authenticate(builder.build())

            Log.i(TAG, "Prompt shown")
        }

        private fun dispatchResult(eventClass: String, success: Boolean, id: String?, code: Int, reason: String?) {
            val payload = JSONObject().apply {
                put("success", success)
                put("code", code)
                if (reason != null) put("reason", reason)
                if (id != null) put("id", id)
            }

            NativeActionCoordinator.dispatchEvent(activity, eventClass, payload.toString())
        }
    }
}
