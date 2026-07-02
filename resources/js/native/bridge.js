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
 * Triggers the device biometric prompt and resolves with whether the user
 * authenticated. Falls back to resolving `true` on web (no biometric hardware),
 * so development in a browser is never blocked.
 *
 * @returns {Promise<boolean>}
 */
export function promptBiometrics({ timeoutMs = 30000 } = {}) {
    if (!isNativeApp()) {
        return Promise.resolve(true);
    }

    return new Promise((resolve) => {
        let settled = false;

        const finish = (result) => {
            if (settled) {
                return;
            }
            settled = true;
            BIOMETRIC_EVENTS.forEach((event) => Off(event, handler));
            clearTimeout(timer);
            resolve(result);
        };

        const handler = (payload) => {
            const ok = payload === true || payload?.success === true;
            finish(ok);
        };

        const timer = setTimeout(() => finish(false), timeoutMs);

        BIOMETRIC_EVENTS.forEach((event) => On(event, handler));

        // Biometrics.prompt() returns a lazy thenable: the bridge call only
        // fires once `.then()` is invoked, so wrap it in a real promise.
        Promise.resolve(Biometrics.prompt()).catch(() => finish(false));
    });
}
