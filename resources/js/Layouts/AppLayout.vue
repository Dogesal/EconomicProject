<script>
// Module scope: survives layout remounts within the same page load, so the
// cold-start relock fires exactly once per real app launch (not every time
// the user comes back from the lock screen).
let bootRelockHandled = false;

// Same idea for the WhatsApp sync: layout remounts on every navigation, but
// the sync should only fire on real app opens/resumes (server throttles too).
let lastWhatsAppSyncAt = 0;
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
// al abrir la app y al volver del background. El servidor tiene su propio
// throttle, este solo evita el POST redundante.
const WHATSAPP_SYNC_THROTTLE_MS = 60000;

const syncWhatsApp = () => {
    if (Date.now() - lastWhatsAppSyncAt < WHATSAPP_SYNC_THROTTLE_MS) {
        return;
    }

    lastWhatsAppSyncAt = Date.now();
    router.post('/whatsapp/sync', {}, { preserveScroll: true, preserveState: true });
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

    syncWhatsApp();

    const onVisibilityChange = () => {
        if (document.hidden) {
            hiddenAt = Date.now();
            return;
        }

        if (shouldGuard() && hiddenAt !== null && Date.now() - hiddenAt > RESUME_GRACE_MS) {
            relock();
        }

        syncWhatsApp();
    };

    document.addEventListener('visibilitychange', onVisibilityChange);
    cleanups.push(() => document.removeEventListener('visibilitychange', onVisibilityChange));
});

onUnmounted(() => {
    clearTimeout(delayTimer);
    cleanups.forEach((off) => off());
});

const navItems = [
    { label: 'Inicio', route: 'dashboard', href: '/', icon: 'M3 12l9-9 9 9M5 10v10h14V10' },
    { label: 'Movimientos', route: 'transactions.index', href: '/transactions', icon: 'M4 7h16M4 12h16M4 17h10' },
    { label: 'Presupuestos', route: 'budgets.index', href: '/budgets', icon: 'M4 4h16v16H4zM4 10h16' },
    { label: 'Reportes', route: 'reports.index', href: '/reports', match: ['/reports', '/statistics'], icon: 'M4 20V10M10 20V4M16 20v-6M22 20H2' },
    { label: 'Metas', route: 'goals.index', href: '/goals', match: ['/goals', '/debts'], icon: 'M12 2v20M2 12h20' },
    { label: 'Ajustes', route: 'settings.index', href: '/settings', icon: 'M10.3 3.3a2 2 0 013.4 0M12 8a4 4 0 100 8 4 4 0 000-8z' },
];

const isActive = (item) => {
    if (item.href === '/') {
        return currentPath.value === '/';
    }

    return (item.match ?? [item.href]).some((prefix) => currentPath.value.startsWith(prefix));
};
</script>

<template>
    <div class="mx-auto flex min-h-full max-w-md flex-col bg-slate-50 dark:bg-slate-950">
        <main class="relative flex-1 px-4 pb-24 pt-[calc(env(safe-area-inset-top)+1rem)]">
            <Transition
                enter-active-class="transition-opacity duration-150"
                leave-active-class="transition-opacity duration-150"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="loading"
                    class="absolute inset-0 z-30 flex items-start justify-center bg-slate-50/70 pt-24 backdrop-blur-[1px] dark:bg-slate-950/70"
                    aria-live="polite"
                    aria-busy="true"
                >
                    <svg class="h-8 w-8 animate-spin text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
                    </svg>
                    <span class="sr-only">Cargando…</span>
                </div>
            </Transition>

            <slot />
        </main>

        <ToastNotification />

        <nav
            class="fixed inset-x-0 bottom-0 z-20 mx-auto max-w-md border-t border-slate-200 bg-white/90 pb-[env(safe-area-inset-bottom)] backdrop-blur dark:border-slate-800 dark:bg-slate-900/90"
        >
            <ul class="grid grid-cols-6">
                <li v-for="item in navItems" :key="item.href">
                    <Link
                        :href="item.href"
                        class="flex flex-col items-center gap-1 py-2 text-[10px] font-medium transition-colors"
                        :class="
                            isActive(item)
                                ? 'text-indigo-600 dark:text-indigo-400'
                                : 'text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300'
                        "
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
