<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppCard from '@/Components/AppCard.vue';
import CategoryExpensesSheet from '@/Components/CategoryExpensesSheet.vue';
import SectionTabs from '@/Components/SectionTabs.vue';
import CategorySpendingList from './Partials/CategorySpendingList.vue';
import MonthlyBarChart from './Partials/MonthlyBarChart.vue';
import { useCategoryDrilldown } from '@/composables/useCategoryDrilldown';

const props = defineProps({
    period: { type: Object, default: () => ({ year: 2026, month: 1 }) },
    currency: { type: String, default: 'ARS' },
    spendingByCategory: { type: Array, default: () => [] },
    monthlyEvolution: { type: Array, default: () => [] },
    netWorth: { type: Array, default: () => [] },
    categoryExpenses: { type: Object, default: null },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const MONTHS = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const periodLabel = computed(() => `${MONTHS[props.period.month]} ${props.period.year}`);

const drilldown = useCategoryDrilldown();
</script>

<template>
    <Head title="Reportes" />

    <header class="mb-4">
        <h1 class="text-2xl font-bold tracking-tight text-ink">Reportes</h1>
    </header>

    <SectionTabs :tabs="[{ label: 'Reportes', href: '/reports' }, { label: 'Estadísticas', href: '/statistics' }]" />

    <section class="mb-6 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-500 p-5 text-white">
        <p class="text-xs font-bold uppercase tracking-widest opacity-80">Patrimonio neto</p>
        <div class="mt-1 space-y-0.5">
            <p v-for="nw in netWorth" :key="nw.currency" class="amount text-3xl font-bold">{{ nw.formatted }}</p>
            <p v-if="!netWorth.length" class="text-2xl font-bold">—</p>
        </div>
    </section>

    <section class="mb-6">
        <h2 class="mb-2 text-lg font-bold text-ink">Evolución (6 meses)</h2>
        <AppCard>
            <MonthlyBarChart :points="monthlyEvolution" />
        </AppCard>
    </section>

    <section>
        <h2 class="mb-2 text-lg font-bold text-ink">
            Gasto por categoría (este mes)
        </h2>
        <AppCard>
            <CategorySpendingList :rows="spendingByCategory" @select="drilldown.show($event)" />
        </AppCard>
    </section>

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
</template>
