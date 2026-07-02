<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppCard from '@/Components/AppCard.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BottomSheet from '@/Components/BottomSheet.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import SectionTabs from '@/Components/SectionTabs.vue';
import DebtForm from './Partials/DebtForm.vue';
import PayDebtForm from './Partials/PayDebtForm.vue';

const props = defineProps({
    debts: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ iOwe: [], owedToMe: [] }) },
    accounts: { type: Array, default: () => [] },
});

const tabs = [
    { label: 'Metas', href: '/goals' },
    { label: 'Deudas', href: '/debts' },
];

const sheetOpen = ref(false);
const paying = ref(null);
const deleting = ref(null);
const deleteProcessing = ref(false);

const confirmDelete = () => {
    deleteProcessing.value = true;
    router.delete(`/debts/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
            deleting.value = null;
        },
    });
};
</script>

<template>
    <Head title="Deudas" />

    <header class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Deudas</h1>
        <BaseButton size="sm" @click="sheetOpen = true">Nueva</BaseButton>
    </header>

    <SectionTabs :tabs="tabs" />

    <AppCard v-if="summary.iOwe.length || summary.owedToMe.length" class="mb-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Debo</p>
                <p v-for="total in summary.iOwe" :key="total.currency" class="mt-0.5 font-semibold text-rose-600 dark:text-rose-400">
                    {{ total.formatted }}
                </p>
                <p v-if="!summary.iOwe.length" class="mt-0.5 font-semibold text-slate-400 dark:text-slate-500">—</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Me deben</p>
                <p v-for="total in summary.owedToMe" :key="total.currency" class="mt-0.5 font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ total.formatted }}
                </p>
                <p v-if="!summary.owedToMe.length" class="mt-0.5 font-semibold text-slate-400 dark:text-slate-500">—</p>
            </div>
        </div>
    </AppCard>

    <ul v-if="debts.length" class="space-y-3">
        <li v-for="debt in debts" :key="debt.id">
            <AppCard :class="debt.status === 'settled' ? 'opacity-60' : ''">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-800 dark:text-slate-200">{{ debt.name }}</p>
                        <p class="text-xs" :class="debt.isOverdue ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-slate-400 dark:text-slate-500'">
                            <span
                                class="mr-1 inline-block rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                :class="
                                    debt.direction === 'i_owe'
                                        ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'
                                        : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
                                "
                            >
                                {{ debt.directionLabel }}
                            </span>
                            {{ debt.statusLabel }}<span v-if="debt.dueDate"> · vence {{ debt.dueDate }}</span>
                            <span v-if="debt.isOverdue"> · ¡vencida!</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-full p-1.5 text-slate-300 transition-colors hover:bg-rose-50 hover:text-rose-500 dark:text-slate-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                        aria-label="Eliminar deuda"
                        @click="deleting = debt"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <ProgressBar
                    :percentage="debt.progress"
                    :bar-class="debt.status === 'settled' ? 'bg-emerald-500' : debt.isOverdue ? 'bg-rose-500' : 'bg-indigo-500'"
                />
                <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>Pagado {{ debt.paid.formatted }} de {{ debt.original.formatted }}</span>
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ debt.progress }}%</span>
                </div>

                <div v-if="debt.status === 'active'" class="mt-3">
                    <BaseButton variant="secondary" size="sm" block @click="paying = debt">
                        {{ debt.direction === 'i_owe' ? 'Pagar' : 'Cobrar' }} · restan {{ debt.remaining.formatted }}
                    </BaseButton>
                </div>
            </AppCard>
        </li>
    </ul>
    <EmptyState v-else message="Sin deudas registradas. ¡Buen momento para que siga así!" />

    <BottomSheet :open="sheetOpen" title="Nueva deuda" @close="sheetOpen = false">
        <DebtForm @saved="sheetOpen = false" />
    </BottomSheet>

    <BottomSheet
        :open="paying !== null"
        :title="paying ? `${paying.direction === 'i_owe' ? 'Pagar' : 'Cobrar'} “${paying.name}”` : ''"
        @close="paying = null"
    >
        <PayDebtForm v-if="paying" :key="paying.id" :debt="paying" :accounts="accounts" @saved="paying = null" />
    </BottomSheet>

    <ConfirmDialog
        :open="deleting !== null"
        title="Eliminar deuda"
        :message="deleting ? `Se eliminará “${deleting.name}”. Los pagos ya registrados quedan en Movimientos.` : ''"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @cancel="deleting = null"
    />
</template>
