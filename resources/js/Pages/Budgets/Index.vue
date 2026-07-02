<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppCard from '@/Components/AppCard.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BottomSheet from '@/Components/BottomSheet.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import BudgetForm from './Partials/BudgetForm.vue';

const props = defineProps({
    period: { type: Object, default: () => ({ year: 2026, month: 1 }) },
    currency: { type: String, default: 'PEN' },
    consumption: { type: Array, default: () => [] },
    expenseCategories: { type: Array, default: () => [] },
});

const MONTHS = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const periodLabel = computed(() => `${MONTHS[props.period.month]} ${props.period.year}`);

const sheetOpen = ref(false);
const deleting = ref(null);
const deleteProcessing = ref(false);

const barClass = (row) => (row.isOverBudget ? 'bg-rose-500' : row.percentage >= 80 ? 'bg-amber-500' : 'bg-emerald-500');

const confirmDelete = () => {
    deleteProcessing.value = true;
    router.delete(`/budgets/${deleting.value.budgetId}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
            deleting.value = null;
        },
    });
};
</script>

<template>
    <Head title="Presupuestos" />

    <header class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Presupuestos</h1>
            <p class="text-xs text-slate-400 dark:text-slate-500">{{ periodLabel }}</p>
        </div>
        <BaseButton size="sm" :disabled="!expenseCategories.length" @click="sheetOpen = true">Nuevo</BaseButton>
    </header>

    <ul v-if="consumption.length" class="space-y-3">
        <li v-for="row in consumption" :key="row.budgetId">
            <AppCard>
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-800 dark:text-slate-200">
                        <span v-if="row.category.icon" class="shrink-0">{{ row.category.icon }}</span>
                        <span class="truncate">{{ row.category.name }}</span>
                    </span>
                    <button
                        type="button"
                        class="shrink-0 rounded-full p-1.5 text-slate-300 transition-colors hover:bg-rose-50 hover:text-rose-500 dark:text-slate-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                        aria-label="Eliminar presupuesto"
                        @click="deleting = row"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <ProgressBar :percentage="row.percentage" :bar-class="barClass(row)" />
                <div class="mt-2 flex items-center justify-between text-xs">
                    <span :class="row.isOverBudget ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400'">
                        {{ row.spent.formatted }} de {{ row.budgeted.formatted }}
                    </span>
                    <span class="text-slate-400 dark:text-slate-500">{{ row.percentage }}%</span>
                </div>
                <p v-if="row.isOverBudget" class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">
                    Excedido por {{ row.remaining.formatted.replace('-', '') }}
                </p>
            </AppCard>
        </li>
    </ul>
    <EmptyState v-else :message="`No hay presupuestos para ${periodLabel}.`" />

    <BottomSheet :open="sheetOpen" title="Nuevo presupuesto" @close="sheetOpen = false">
        <BudgetForm :expense-categories="expenseCategories" :currency="currency" :period="period" @saved="sheetOpen = false" />
    </BottomSheet>

    <ConfirmDialog
        :open="deleting !== null"
        title="Eliminar presupuesto"
        :message="deleting ? `Se eliminará el presupuesto de “${deleting.category.name}”.` : ''"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @cancel="deleting = null"
    />
</template>
