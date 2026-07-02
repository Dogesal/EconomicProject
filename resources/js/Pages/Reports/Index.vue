<script setup>
import { Head } from '@inertiajs/vue3';
import AppCard from '@/Components/AppCard.vue';
import CategorySpendingList from './Partials/CategorySpendingList.vue';
import MonthlyBarChart from './Partials/MonthlyBarChart.vue';

defineProps({
    period: { type: Object, default: () => ({ year: 2026, month: 1 }) },
    currency: { type: String, default: 'ARS' },
    spendingByCategory: { type: Array, default: () => [] },
    monthlyEvolution: { type: Array, default: () => [] },
    netWorth: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Reportes" />

    <header class="mb-4">
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Reportes</h1>
    </header>

    <section class="mb-6 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-700 p-5 text-white dark:from-slate-800 dark:to-slate-900 dark:ring-1 dark:ring-slate-700">
        <p class="text-xs opacity-70">Patrimonio neto</p>
        <div class="mt-1 space-y-0.5">
            <p v-for="nw in netWorth" :key="nw.currency" class="text-2xl font-bold">{{ nw.formatted }}</p>
            <p v-if="!netWorth.length" class="text-2xl font-bold">—</p>
        </div>
    </section>

    <section class="mb-6">
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Evolución (6 meses)</h2>
        <AppCard>
            <MonthlyBarChart :points="monthlyEvolution" />
        </AppCard>
    </section>

    <section>
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Gasto por categoría (este mes)
        </h2>
        <AppCard>
            <CategorySpendingList :rows="spendingByCategory" />
        </AppCard>
    </section>
</template>
