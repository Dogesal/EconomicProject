<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppCard from '@/Components/AppCard.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { openExternal } from '@/native/bridge';

const props = defineProps({
    whatsapp: { type: Object, default: () => ({}) },
    accounts: { type: Array, default: () => [] },
});

const page = usePage();

// El código generado llega como flash tras pedir la vinculación.
const linkCode = computed(() => page.props.flash?.whatsappLinkCode ?? null);

const requesting = ref(false);
const checking = ref(false);
const unlinking = ref(false);
const confirmUnlink = ref(false);

const requestCode = () => {
    requesting.value = true;
    router.post('/settings/whatsapp/link', {}, {
        preserveScroll: true,
        onFinish: () => (requesting.value = false),
    });
};

const checkLinked = () => {
    checking.value = true;
    router.post('/settings/whatsapp/refresh', {}, {
        preserveScroll: true,
        onFinish: () => (checking.value = false),
    });
};

const doUnlink = () => {
    unlinking.value = true;
    router.delete('/settings/whatsapp/link', {
        preserveScroll: true,
        onFinish: () => {
            unlinking.value = false;
            confirmUnlink.value = false;
        },
    });
};

const defaultAccountId = computed({
    get: () => props.whatsapp.defaultAccountId ?? '',
    set: (value) => {
        if (value) {
            router.put('/settings/whatsapp/account', { account_id: value }, { preserveScroll: true });
        }
    },
});

const waLink = computed(() => {
    if (!linkCode.value?.bot_phone) return null;
    const phone = linkCode.value.bot_phone.replace(/[^\d]/g, '');
    return `https://wa.me/${phone}?text=${encodeURIComponent(`VINCULAR ${linkCode.value.code}`)}`;
});

const openWhatsApp = () => {
    if (waLink.value) {
        openExternal(waLink.value);
    }
};

const statusLabel = (entry) => (entry.status === 'applied' ? '✔' : '✖');
</script>

<template>
    <AppCard v-if="whatsapp.configured" class="mb-4">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Registrar por WhatsApp</h2>

        <template v-if="!whatsapp.linked">
            <p class="mb-3 mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                Vinculá tu WhatsApp y registrá movimientos enviando mensajes como “comida 100 hoy”.
            </p>

            <template v-if="linkCode">
                <div class="mb-3 rounded-lg bg-slate-50 p-3 text-center dark:bg-slate-800/60">
                    <p class="text-xs text-slate-400 dark:text-slate-500">Envía este mensaje al bot:</p>
                    <p class="mt-1 font-mono text-lg font-bold tracking-widest text-slate-900 dark:text-slate-100">
                        VINCULAR {{ linkCode.code }}
                    </p>
                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">El código vence en 10 minutos.</p>
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="waLink"
                        type="button"
                        class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-center text-sm font-medium text-white transition-colors hover:bg-emerald-500"
                        @click="openWhatsApp"
                    >
                        Abrir WhatsApp
                    </button>
                    <BaseButton variant="secondary" class="flex-1" :processing="checking" @click="checkLinked">
                        Ya lo envié
                    </BaseButton>
                </div>
            </template>

            <BaseButton v-else :processing="requesting" @click="requestCode">Vincular WhatsApp</BaseButton>
        </template>

        <template v-else>
            <p class="mb-3 mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                Vinculado. Envía “comida 100 hoy” al bot y se registrará al abrir la app.
            </p>

            <div class="mb-3">
                <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">
                    Cuenta destino de los movimientos
                </label>
                <BaseSelect v-model="defaultAccountId">
                    <option value="" disabled>Elige una cuenta…</option>
                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                        {{ account.name }}
                    </option>
                </BaseSelect>
                <p v-if="!whatsapp.defaultAccountId" class="mt-1 text-[11px] font-medium text-amber-600 dark:text-amber-400">
                    Sin cuenta destino los mensajes quedan en espera.
                </p>
            </div>

            <div v-if="whatsapp.recentInbox?.length" class="mb-3">
                <p class="mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Últimos mensajes</p>
                <ul class="divide-y divide-slate-100 rounded-lg border border-slate-100 dark:divide-slate-800 dark:border-slate-800">
                    <li v-for="entry in whatsapp.recentInbox" :key="entry.id" class="px-3 py-1.5 text-xs">
                        <div class="flex items-center gap-2">
                            <span :class="entry.status === 'applied' ? 'text-emerald-500' : 'text-rose-500'">
                                {{ statusLabel(entry) }}
                            </span>
                            <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-300">{{ entry.raw_text }}</span>
                        </div>
                        <p v-if="entry.reason" class="mt-0.5 pl-5 text-[11px] text-rose-500 dark:text-rose-400">{{ entry.reason }}</p>
                    </li>
                </ul>
            </div>

            <BaseButton variant="secondary" size="sm" @click="confirmUnlink = true">Desvincular</BaseButton>
        </template>

        <ConfirmDialog
            :open="confirmUnlink"
            title="Desvincular WhatsApp"
            message="El bot dejará de aceptar tus mensajes y se descartarán los pendientes."
            :processing="unlinking"
            @confirm="doUnlink"
            @cancel="confirmUnlink = false"
        />
    </AppCard>
</template>
