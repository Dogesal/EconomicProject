<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { isNativeApp, promptBiometrics } from '../native/bridge';

defineOptions({ layout: null });

defineProps({
    appName: { type: String, default: 'Mi Economía' },
});

const biometricLoading = ref(false);
const biometricFailed = ref(false);
const biometricReason = ref(null);
const pinInput = ref(null);

const pinForm = useForm({ pin: '' });

const unlockWithBiometrics = async () => {
    biometricLoading.value = true;
    biometricFailed.value = false;
    biometricReason.value = null;

    const { ok, reason } = await promptBiometrics();

    if (ok) {
        pinForm.transform(() => ({})).post('/unlock', {
            onFinish: () => (biometricLoading.value = false),
        });
    } else {
        biometricFailed.value = true;
        biometricReason.value = reason;
        biometricLoading.value = false;
        pinInput.value?.$el?.focus();
    }
};

const unlockWithPin = () => {
    pinForm.transform((data) => data).post('/unlock', {
        onError: () => {
            pinForm.reset('pin');
            pinInput.value?.$el?.focus();
        },
    });
};

onMounted(() => {
    // Auto-prompt the sensor as soon as the lock screen appears on device.
    if (isNativeApp()) {
        unlockWithBiometrics();
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
        <p class="mt-1 text-sm text-slate-400">Ingresá tu PIN o usá tu huella para continuar.</p>

        <form class="mt-8 w-full max-w-xs" @submit.prevent="unlockWithPin">
            <input
                ref="pinInput"
                v-model="pinForm.pin"
                type="password"
                inputmode="numeric"
                maxlength="6"
                autocomplete="off"
                placeholder="PIN"
                class="w-full rounded-xl border border-slate-700 bg-slate-800 px-4 py-3 text-center text-lg tracking-[0.5em] text-white placeholder:tracking-normal placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
            />
            <p v-if="pinForm.errors.pin" class="mt-2 text-sm text-rose-400">{{ pinForm.errors.pin }}</p>

            <button
                type="submit"
                class="mt-3 w-full rounded-xl bg-indigo-600 py-3 text-sm font-semibold text-white transition active:scale-95 disabled:opacity-60"
                :disabled="pinForm.processing || pinForm.pin.length < 4"
            >
                {{ pinForm.processing ? 'Verificando…' : 'Entrar' }}
            </button>
        </form>

        <button
            class="mt-4 flex w-full max-w-xs items-center justify-center gap-2 rounded-xl border border-slate-700 py-3 text-sm font-semibold text-slate-200 transition active:scale-95 disabled:opacity-60"
            :disabled="biometricLoading"
            @click="unlockWithBiometrics"
        >
            <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.9 8.6a5 5 0 018.2 0M6 12a6.7 6.7 0 0112 0c0 2.5-.6 4.9-1.7 7M9.3 12a3.4 3.4 0 016.7 0c0 2-.4 3.9-1.2 5.6M12 12v2.8" />
            </svg>
            {{ biometricLoading ? 'Verificando huella…' : 'Usar huella' }}
        </button>

        <div v-if="biometricFailed" class="mt-4">
            <p class="text-sm text-rose-400">No se pudo verificar la huella. Usá tu PIN.</p>
            <p v-if="biometricReason" class="mt-1 text-xs text-slate-500">({{ biometricReason }})</p>
        </div>
    </div>
</template>
