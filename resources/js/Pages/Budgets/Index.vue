<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppCard from '@/Components/AppCard.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BottomSheet from '@/Components/BottomSheet.vue';
import CategoryExpensesSheet from '@/Components/CategoryExpensesSheet.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import EmptyState from '@/Components/EmptyState.vue';
import BudgetForm from './Partials/BudgetForm.vue';
import { useCategoryDrilldown } from '@/composables/useCategoryDrilldown';

const props = defineProps({
    period: { type: Object, default: () => ({ year: 2026, month: 1 }) },
    currency: { type: String, default: 'PEN' },
    consumption: { type: Array, default: () => [] },
    expenseCategories: { type: Array, default: () => [] },
    categoryExpenses: { type: Object, default: null },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const MONTHS = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const periodLabel = computed(() => `${MONTHS[props.period.month]} ${props.period.year}`);

const sheetOpen = ref(false);
const deleting = ref(null);
const deleteProcessing = ref(false);
const drilldown = useCategoryDrilldown();

/**
 * Totales del encabezado. El símbolo se toma de un importe ya formateado por el
 * backend para no duplicar la lógica de moneda en el cliente.
 */
const totals = computed(() => {
    const sum = (key) => props.consumption.reduce((carry, row) => carry + row[key].decimal, 0);
    const prefix = (props.consumption[0]?.budgeted.formatted ?? '').match(/^[^\d-]*/)[0];
    const format = (value) =>
        `${prefix}${value.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    return { budgeted: format(sum('budgeted')), spent: format(sum('spent')) };
});

const barClass = (row) => (row.isOverBudget ? 'bg-neg' : row.percentage >= 80 ? 'bg-gold-500' : 'bg-brand-500');

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

    <header class="mb-5 flex items-center justify-between gap-2">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-ink">Presupuesto</h1>
            <p class="text-sm text-ink-soft">{{ periodLabel }}</p>
        </div>
        <BaseButton size="sm" :disabled="!expenseCategories.length" @click="sheetOpen = true">+ Nuevo</BaseButton>
    </header>

    <section v-if="consumption.length" class="mb-6 grid grid-cols-2 gap-3">
        <AppCard tone="muted" class="text-center">
            <span class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-card text-brand-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9v3"
                    />
                </svg>
            </span>
            <p class="mt-2 text-[11px] font-bold uppercase tracking-wide text-ink-soft">Presupuesto total</p>
            <p class="amount mt-1 truncate text-lg font-bold text-ink">{{ totals.budgeted }}</p>
        </AppCard>
        <AppCard tone="muted" class="text-center">
            <span class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-card text-accent-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22M21.75 9V4.5m0 0h-4.5"
                    />
                </svg>
            </span>
            <p class="mt-2 text-[11px] font-bold uppercase tracking-wide text-ink-soft">Gasto mensual</p>
            <p class="amount mt-1 truncate text-lg font-bold text-ink">{{ totals.spent }}</p>
        </AppCard>
    </section>

    <h2 class="mb-3 text-lg font-bold text-ink">Gastos por categoría</h2>

    <ul v-if="consumption.length" class="space-y-3">
        <li v-for="row in consumption" :key="row.budgetId">
            <AppCard
                tone="muted"
                class="cursor-pointer transition-opacity active:opacity-70"
                @click="drilldown.show(row.category.id)"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-base text-white"
                        :style="{ backgroundColor: row.category.color || '#3c5e4d' }"
                    >
                        {{ row.category.icon || '•' }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-ink">{{ row.category.name }}</p>
                        <p class="amount truncate text-xs text-ink-soft">
                            {{ row.spent.formatted }} de {{ row.budgeted.formatted }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="amount text-sm font-bold" :class="row.isOverBudget ? 'text-neg' : 'text-ink'">
                            {{ row.remaining.formatted.replace('-', '−') }}
                        </p>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-ink-faint">restante</p>
                    </div>
                    <button
                        type="button"
                        class="-mr-1 shrink-0 rounded-full p-1 text-ink-faint transition-colors hover:bg-neg-soft hover:text-neg"
                        aria-label="Eliminar presupuesto"
                        @click.stop="deleting = row"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-card">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="barClass(row)"
                        :style="{ width: `${Math.min(100, row.percentage)}%` }"
                    />
                </div>

                <p
                    v-if="row.isOverBudget"
                    class="mt-2 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-neg"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m0 3.75h.008M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    Límite alcanzado
                </p>
            </AppCard>
        </li>
    </ul>
    <EmptyState v-else :message="`No hay presupuestos para ${periodLabel}.`" />

    <BottomSheet :open="sheetOpen" title="Nuevo presupuesto" @close="sheetOpen = false">
        <BudgetForm :expense-categories="expenseCategories" :currency="currency" :period="period" @saved="sheetOpen = false" />
    </BottomSheet>

    <CategoryExpensesSheet
        :open="drilldown.open.value"
        :loading="drilldown.loading.value"
        :expenses="categoryExpenses"
        :period-label="periodLabel"
        :accounts="accounts"
        :categories="categories"
        @close="drilldown.close()"
        @changed="drilldown.refresh()"
    />

    <ConfirmDialog
        :open="deleting !== null"
        title="Eliminar presupuesto"
        :message="deleting ? `Se eliminará el presupuesto de “${deleting.category.name}”.` : ''"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @cancel="deleting = null"
    />
</template>
