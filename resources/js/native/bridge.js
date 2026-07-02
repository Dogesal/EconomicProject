import { Biometrics, On, Off, Events } from '@nativephp/mobile';

// Event dispatched by our own plugin (economia/mobile-biometrics), which
// reuses the core event class. The official paid plugin uses the second
// name; listening to both keeps this working if the plugin is ever swapped.
const BIOMETRIC_EVENTS = [
    'Native\\Mobile\\Events\\Biometric\\Completed',
    Events.Biometrics.Completed,
];

/**
 * Detects whether the app is running inside the NativePHP native shell (as
 * opposed to a plain browser during development). NativePHP serves the app from
 * a local origin and exposes its bridge endpoint; we treat a non-standard
 * origin / injected flag as "native".
 */
export function isNativeApp() {
    if (typeof window === 'undefined') {
        return false;
    }

    if (window.NativePHP || window.__nativephp) {
        return true;
    }

    return /nativephp/i.test(navigator.userAgent ?? '');
}

/**
 * Triggers the device biometric prompt and resolves with the outcome and,
 * on failure, a human-readable reason (bridge error, sensor availability
 * code, timeout…) so the lock screen can surface what went wrong. Falls
 * back to `{ ok: true }` on web so browser development is never blocked.
 *
 * @returns {Promise<{ ok: boolean, reason: string|null }>}
 */
export function promptBiometrics({ timeoutMs = 30000 } = {}) {
    if (!isNativeApp()) {
        return Promise.resolve({ ok: true, reason: null });
    }

    return new Promise((resolve) => {
        let settled = false;

        const finish = (ok, reason = null) => {
            if (settled) {
                return;
            }
            settled = true;
            BIOMETRIC_EVENTS.forEach((event) => Off(event, handler));
            clearTimeout(timer);
            resolve({ ok, reason });
        };

        const handler = (payload) => {
            if (payload === true || payload?.success === true) {
                finish(true);
                return;
            }

            finish(false, payload?.reason ?? (payload?.code != null ? `code ${payload.code}` : 'rechazado'));
        };

        const timer = setTimeout(() => finish(false, 'timeout'), timeoutMs);

        BIOMETRIC_EVENTS.forEach((event) => On(event, handler));

        // Biometrics.prompt() returns a lazy thenable: the bridge call only
        // fires once `.then()` is invoked, so wrap it in a real promise.
        Promise.resolve(Biometrics.prompt()).catch((error) => finish(false, error?.message ?? 'bridge error'));
    });
}
