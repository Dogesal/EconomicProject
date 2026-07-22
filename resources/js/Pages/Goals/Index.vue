<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppCard from '@/Components/AppCard.vue';
import AmountDialog from '@/Components/AmountDialog.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BottomSheet from '@/Components/BottomSheet.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import SectionTabs from '@/Components/SectionTabs.vue';
import GoalForm from './Partials/GoalForm.vue';

const props = defineProps({
    goals: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
});

const tabs = [
    { label: 'Metas', href: '/goals' },
    { label: 'Deudas', href: '/debts' },
];

const sheetOpen = ref(false);
const deleting = ref(null);
const deleteProcessing = ref(false);

// Amount dialog state for contribute/withdraw against a specific goal.
const amountAction = ref(null); // { goal, mode: 'contribute' | 'withdraw' }
const amountProcessing = ref(false);
const amountError = ref('');

const amountTitle = computed(() => {
    if (!amountAction.value) {
        return '';
    }

    const { goal, mode } = amountAction.value;

    return mode === 'contribute' ? `Aportar a “${goal.name}”` : `Retirar de “${goal.name}”`;
});

// Caps: withdrawals up to what was saved; contributions to a linked goal
// up to the account balance (the server enforces both anyway).
const amountMax = computed(() => {
    if (!amountAction.value) {
        return null;
    }

    const { goal, mode } = amountAction.value;

    if (mode === 'withdraw') {
        return goal.current.decimal;
    }

    const account = props.accounts.find((a) => a.id === goal.accountId);

    return account ? account.currentBalance.decimal : null;
});

const amountMaxHint = computed(() => {
    if (!amountAction.value || amountMax.value === null) {
        return '';
    }

    return amountAction.value.mode === 'withdraw'
        ? `Ahorrado: ${amountAction.value.goal.current.formatted}`
        : `Disponible en la cuenta: ${props.accounts.find((a) => a.id === amountAction.value.goal.accountId)?.currentBalance.formatted ?? ''}`;
});

const openAmountDialog = (goal, mode) => {
    amountError.value = '';
    amountAction.value = { goal, mode };
};

const submitAmount = (amount) => {
    const { goal, mode } = amountAction.value;

    amountProcessing.value = true;
    router.post(`/goals/${goal.id}/${mode}`, { amount }, {
        preserveScroll: true,
        onSuccess: () => {
            amountAction.value = null;
        },
        onError: (errors) => {
            amountError.value = errors.amount ?? Object.values(errors)[0] ?? '';
        },
        onFinish: () => {
            amountProcessing.value = false;
        },
    });
};

const confirmDelete = () => {
    deleteProcessing.value = true;
    router.delete(`/goals/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
            deleting.value = null;
        },
    });
};
</script>

<template>
    <Head title="Metas" />

    <header class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-ink">Metas de ahorro</h1>
        <BaseButton size="sm" @click="sheetOpen = true">Nueva</BaseButton>
    </header>

    <SectionTabs :tabs="tabs" />

    <ul v-if="goals.length" class="space-y-3">
        <li v-for="goal in goals" :key="goal.id">
            <AppCard>
                <div class="mb-2 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-ink">{{ goal.name }}</p>
                        <p class="text-xs text-ink-faint">
                            {{ goal.statusLabel }}<span v-if="goal.targetDate"> · meta {{ goal.targetDate }}</span>
                            <span v-if="goal.accountName"> · 🏦 {{ goal.accountName }}</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-full p-1.5 text-ink-faint transition-colors hover:bg-neg-soft hover:text-neg dark:text-ink-soft dark:hover:bg-neg/10 dark:hover:text-neg"
                        aria-label="Eliminar meta"
                        @click="deleting = goal"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <ProgressBar
                    :percentage="goal.progress"
                    :bar-class="goal.status === 'completed' ? 'bg-pos' : 'bg-brand-500'"
                />
                <div class="mt-2 flex items-center justify-between text-xs text-ink-soft">
                    <span>{{ goal.current.formatted }} / {{ goal.target.formatted }}</span>
                    <span class="font-semibold text-brand-500">{{ goal.progress }}%</span>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <BaseButton variant="secondary" size="sm" @click="openAmountDialog(goal, 'contribute')">Aportar</BaseButton>
                    <BaseButton variant="ghost" size="sm" @click="openAmountDialog(goal, 'withdraw')">Retirar</BaseButton>
                </div>
            </AppCard>
        </li>
    </ul>
    <EmptyState v-else message="Creá tu primera meta de ahorro." />

    <BottomSheet :open="sheetOpen" title="Nueva meta" @close="sheetOpen = false">
        <GoalForm :accounts="accounts" @saved="sheetOpen = false" />
    </BottomSheet>

    <AmountDialog
        :open="amountAction !== null"
        :title="amountTitle"
        :currency="amountAction?.goal.current.currency ?? ''"
        :confirm-label="amountAction?.mode === 'withdraw' ? 'Retirar' : 'Aportar'"
        :processing="amountProcessing"
        :error="amountError"
        :max="amountMax"
        :max-hint="amountMaxHint"
        @submit="submitAmount"
        @cancel="amountAction = null"
    />

    <ConfirmDialog
        :open="deleting !== null"
        title="Eliminar meta"
        :message="deleting ? `Se eliminará “${deleting.name}” y su historial de aportes.` : ''"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @cancel="deleting = null"
    />
</template>
