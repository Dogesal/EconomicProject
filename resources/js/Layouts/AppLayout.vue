<script>
// Module scope: survives layout remounts within the same page load, so the
// cold-start relock fires exactly once per real app launch (not every time
// the user comes back from the lock screen).
let bootRelockHandled = false;

// Same idea for the WhatsApp sync: layout remounts on every navigation, but
// the sync should only fire on real app opens/resumes (server throttles too).
let lastWhatsAppSyncAt = 0;

// Boot maintenance (recurring catch-up + reminder rescheduling) also runs
// post-mount so it never blocks first paint; once per real open/resume.
let lastBootTasksAt = 0;
</script>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import { isNativeApp } from '@/native/bridge';

const page = usePage();
const currentPath = computed(() => new URL(page.props.ziggy?.location ?? window.location.href).pathname);

// The blade root resolves the theme before first paint; this keeps it in
// sync when the preference changes in-app or the device switches mode
// while the app is open (`system` follows prefers-color-scheme).
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

const applyTheme = () => {
    const theme = page.props.theme ?? 'system';
    const dark = theme === 'dark' || (theme !== 'light' && prefersDark.matches);
    document.documentElement.classList.toggle('dark', dark);
};

watch(() => page.props.theme, applyTheme, { immediate: true });
prefersDark.addEventListener('change', applyTheme);
onUnmounted(() => prefersDark.removeEventListener('change', applyTheme));

// Centralised view-load indicator: shows while navigating between views (GET
// visits only, so form submits keep their own button state). A short delay
// avoids flashing on the near-instant on-device navigations.
const loading = ref(false);
let delayTimer = null;
const cleanups = [];

// App lock lifecycle: the middleware only runs on HTTP requests, but Android
// resumes the webview without navigating, so the lock must be re-engaged
// from the client on cold start and when returning from background.
const RESUME_GRACE_MS = 30000;
let hiddenAt = null;

const relock = () => router.post('/lock/relock', {}, { preserveScroll: false });

const shouldGuard = () => page.props.appLockEnabled && isNativeApp();

// Aplica los movimientos de WhatsApp pendientes desde cualquier pantalla:
// al abrir la app, al volver del background y cuando el push FCM llega con
// la app abierta (el lado nativo invoca window.__syncWhatsApp(true), con
// force porque el push garantiza que hay un mensaje nuevo esperando). El
// POST recarga las props de la página, así el movimiento aparece al toque.
const WHATSAPP_SYNC_THROTTLE_MS = 60000;

const syncWhatsApp = (force = false) => {
    if (!force && Date.now() - lastWhatsAppSyncAt < WHATSAPP_SYNC_THROTTLE_MS) {
        return;
    }

    lastWhatsAppSyncAt = Date.now();
    router.post('/whatsapp/sync', { force: force ? 1 : 0 }, { preserveScroll: true, preserveState: true });
};

// Movido fuera del render del Dashboard: pone al día recurrentes y
// recordatorios sin demorar el primer paint. Throttle propio para no
// repetir en cada navegación; corre al abrir y al volver del background.
const BOOT_TASKS_THROTTLE_MS = 60000;

const runBootTasks = () => {
    if (Date.now() - lastBootTasksAt < BOOT_TASKS_THROTTLE_MS) {
        return;
    }

    lastBootTasksAt = Date.now();
    router.post('/boot/tasks', {}, { preserveScroll: true, preserveState: true });
};

onMounted(() => {
    cleanups.push(
        router.on('start', (event) => {
            if (event.detail.visit.method !== 'get') {
                return;
            }
            delayTimer = setTimeout(() => (loading.value = true), 150);
        }),
    );
    cleanups.push(
        router.on('finish', () => {
            clearTimeout(delayTimer);
            loading.value = false;
        }),
    );

    if (!bootRelockHandled) {
        bootRelockHandled = true;
        if (shouldGuard()) {
            relock();
        }
    }

    window.__syncWhatsApp = syncWhatsApp;
    // Camino del widget de voz con la app abierta: el lado nativo
    // (VoiceCaptureActivity) entrega aquí el texto transcrito y el reply
    // del servidor llega como flash con las props ya recargadas.
    window.__sendVoiceNote = (text) => {
        router.post('/voice-notes', { text }, { preserveScroll: true });
    };
    syncWhatsApp();
    runBootTasks();

    const onVisibilityChange = () => {
        if (document.hidden) {
            hiddenAt = Date.now();
            return;
        }

        if (shouldGuard() && hiddenAt !== null && Date.now() - hiddenAt > RESUME_GRACE_MS) {
            relock();
        }

        syncWhatsApp();
        runBootTasks();
    };

    document.addEventListener('visibilitychange', onVisibilityChange);
    cleanups.push(() => document.removeEventListener('visibilitychange', onVisibilityChange));
});

onUnmounted(() => {
    clearTimeout(delayTimer);
    cleanups.forEach((off) => off());
});

const navItems = [
    {
        label: 'Inicio',
        route: 'dashboard',
        href: '/',
        icon: 'M2.25 12l8.955-8.955a1.5 1.5 0 012.122 0L22.28 12M4.5 9.75V19.5a1.5 1.5 0 001.5 1.5h3.75v-5.25a1.5 1.5 0 011.5-1.5h1.5a1.5 1.5 0 011.5 1.5V21H18a1.5 1.5 0 001.5-1.5V9.75',
    },
    {
        label: 'Movimientos',
        route: 'transactions.index',
        href: '/transactions',
        icon: 'M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.01M3.75 12h.01M3.75 17.25h.01',
    },
    {
        label: 'Presupuestos',
        route: 'budgets.index',
        href: '/budgets',
        icon: 'M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6zM13.5 3v7.5H21A7.5 7.5 0 0013.5 3z',
    },
    {
        label: 'Reportes',
        route: 'reports.index',
        href: '/reports',
        match: ['/reports', '/statistics'],
        icon: 'M3.75 20.25h16.5M6.75 20.25v-7.5M12 20.25V6.75M17.25 20.25v-4.5',
    },
    {
        label: 'Metas',
        route: 'goals.index',
        href: '/goals',
        match: ['/goals', '/debts'],
        icon: 'M12 21a9 9 0 100-18 9 9 0 000 18zm0-4.5a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0-3a1.5 1.5 0 100-3 1.5 1.5 0 000 3z',
    },
    {
        label: 'Ajustes',
        route: 'settings.index',
        href: '/settings',
        icon: 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09a1.65 1.65 0 00-1.08-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09a1.65 1.65 0 001.51-1.08 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z',
    },
];

const isActive = (item) => {
    if (item.href === '/') {
        return currentPath.value === '/';
    }

    return (item.match ?? [item.href]).some((prefix) => currentPath.value.startsWith(prefix));
};

// Botón flotante del mockup: acción principal (registrar movimiento) siempre a
// mano. Se oculta donde ya hay un flujo propio de alta o no aplica.
const FAB_HIDDEN_PREFIXES = ['/settings', '/lock'];

const showFab = computed(() => !FAB_HIDDEN_PREFIXES.some((prefix) => currentPath.value.startsWith(prefix)));
</script>

<template>
    <div class="mx-auto flex min-h-full max-w-md flex-col bg-surface">
        <main class="relative flex-1 px-4 pb-24 pt-[calc(env(safe-area-inset-top)+1rem)]">
            <Transition
                enter-active-class="transition-opacity duration-150"
                leave-active-class="transition-opacity duration-150"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="loading"
                    class="absolute inset-0 z-30 flex items-start justify-center bg-surface/70 pt-24 backdrop-blur-[1px]"
                    aria-live="polite"
                    aria-busy="true"
                >
                    <svg class="h-8 w-8 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
                    </svg>
                    <span class="sr-only">Cargando…</span>
                </div>
            </Transition>

            <slot />
        </main>

        <ToastNotification />

        <Link
            v-if="showFab"
            href="/transactions?new=1"
            class="fixed bottom-24 right-[max(1rem,calc(50%-13rem))] z-30 flex h-14 w-14 items-center justify-center rounded-full bg-brand-500 text-white shadow-lg shadow-brand-500/30 transition-transform active:scale-95"
            aria-label="Nuevo movimiento"
        >
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
        </Link>

        <nav
            class="fixed inset-x-0 bottom-0 z-20 mx-auto max-w-md border-t border-line bg-card/95 pb-[env(safe-area-inset-bottom)] backdrop-blur"
        >
            <ul class="grid grid-cols-6">
                <li v-for="item in navItems" :key="item.href">
                    <Link
                        :href="item.href"
                        class="flex flex-col items-center gap-1 py-2.5 text-[10px] transition-colors"
                        :class="isActive(item) ? 'font-bold text-brand-500' : 'font-medium text-ink-faint'"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                        </svg>
                        <span class="w-full truncate px-0.5 text-center leading-tight">{{ item.label }}</span>
                    </Link>
                </li>
            </ul>
        </nav>
    </div>
</template>
