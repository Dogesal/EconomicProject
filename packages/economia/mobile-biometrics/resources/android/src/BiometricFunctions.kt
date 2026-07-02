package com.economia.biometrics

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

            // BiometricPrompt must be created and shown on the main thread.
            activity.runOnUiThread {
                val authenticators = Authenticators.BIOMETRIC_WEAK or Authenticators.DEVICE_CREDENTIAL
                val availability = BiometricManager.from(activity).canAuthenticate(authenticators)

                if (availability != BiometricManager.BIOMETRIC_SUCCESS) {
                    Log.w(TAG, "Biometric authentication unavailable (code $availability)")
                    dispatchResult(eventClass, false, id)
                    return@runOnUiThread
                }

                val promptInfo = BiometricPrompt.PromptInfo.Builder()
                    .setTitle(title)
                    .setSubtitle(subtitle)
                    .setAllowedAuthenticators(authenticators)
                    .setConfirmationRequired(false)
                    .build()

                val callback = object : BiometricPrompt.AuthenticationCallback() {
                    override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                        dispatchResult(eventClass, true, id)
                    }

                    override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                        // Includes user cancellation (ERROR_USER_CANCELED / ERROR_NEGATIVE_BUTTON).
                        Log.w(TAG, "Biometric error $errorCode: $errString")
                        dispatchResult(eventClass, false, id)
                    }

                    // onAuthenticationFailed() is a single failed attempt; the
                    // prompt stays open and the user can retry, so no dispatch.
                }

                BiometricPrompt(activity, ContextCompat.getMainExecutor(activity), callback)
                    .authenticate(promptInfo)
            }

            return mapOf("status" to "prompted")
        }

        private fun dispatchResult(eventClass: String, success: Boolean, id: String?) {
            val payload = JSONObject().apply {
                put("success", success)
                if (id != null) put("id", id)
            }

            NativeActionCoordinator.dispatchEvent(activity, eventClass, payload.toString())
        }
    }
}
