<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import SectionTabs from '@/Components/SectionTabs.vue';
import MonthOverviewCard from './Partials/MonthOverviewCard.vue';
import RecommendationsList from './Partials/RecommendationsList.vue';
import TopExpensesCard from './Partials/TopExpensesCard.vue';
import TrendSection from './Partials/TrendSection.vue';

const props = defineProps({
    period: { type: Object, default: () => ({ year: 2026, month: 1 }) },
    currency: { type: String, default: 'ARS' },
    overview: { type: Object, required: true },
    habits: { type: Object, required: true },
    recommendations: { type: Array, default: () => [] },
    trend: { type: Array, default: () => [] },
});

const MONTHS = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const periodLabel = computed(() => `${MONTHS[props.period.month]} ${props.period.year}`);
</script>

<template>
    <Head title="Estadísticas" />

    <header class="mb-4">
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Estadísticas</h1>
        <p class="text-xs text-slate-400 dark:text-slate-500">{{ periodLabel }}</p>
    </header>

    <SectionTabs :tabs="[{ label: 'Reportes', href: '/reports' }, { label: 'Estadísticas', href: '/statistics' }]" />

    <section class="mb-6">
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Resumen del mes</h2>
        <MonthOverviewCard :overview="overview" />
    </section>

    <section class="mb-6">
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Recomendaciones</h2>
        <RecommendationsList :recommendations="recommendations" />
    </section>

    <section class="mb-6">
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Top gastos y hábitos</h2>
        <TopExpensesCard :habits="habits" />
    </section>

    <section>
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tendencia (6 meses)</h2>
        <TrendSection :trend="trend" />
    </section>
</template>
