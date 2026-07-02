<script setup>
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { isNativeApp, promptBiometrics } from '../native/bridge';

defineOptions({ layout: null });

defineProps({
    appName: { type: String, default: 'Mi Economía' },
});

const loading = ref(false);
const failed = ref(false);

const unlock = async () => {
    loading.value = true;
    failed.value = false;

    const ok = await promptBiometrics();

    if (ok) {
        router.post('/unlock', {}, { onFinish: () => (loading.value = false) });
    } else {
        failed.value = true;
        loading.value = false;
    }
};

onMounted(() => {
    // Auto-prompt the sensor as soon as the lock screen appears on device.
    if (isNativeApp()) {
        unlock();
    }
});
</script>

<template>
    <Head title="Bloqueado" />

    <div class="flex min-h-dvh flex-col items-center justify-center bg-slate-900 px-6 text-center text-white">
        <div class="mb-8 flex h-20 w-20 items-center justify-center rounded-3xl bg-indigo-600/20">
            <svg class="h-10 w-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 0h10.5a1.5 1.5 0 011.5 1.5v6a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5v-6a1.5 1.5 0 011.5-1.5z" />
            </svg>
        </div>

        <h1 class="text-xl font-bold">{{ appName }}</h1>
        <p class="mt-1 text-sm text-slate-400">Desbloqueá con tu biometría para continuar.</p>

        <button
            class="mt-8 w-full max-w-xs rounded-xl bg-indigo-600 py-3 text-sm font-semibold text-white transition active:scale-95 disabled:opacity-60"
            :disabled="loading"
            @click="unlock"
        >
            {{ loading ? 'Verificando…' : 'Desbloquear' }}
        </button>

        <p v-if="failed" class="mt-4 text-sm text-rose-400">No se pudo verificar. Intentá de nuevo.</p>
    </div>
</template>
