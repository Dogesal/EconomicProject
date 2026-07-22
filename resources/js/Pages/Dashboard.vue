<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppCard from '@/Components/AppCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import TransactionListItem from '@/Components/TransactionListItem.vue';
import BudgetHighlights from './Dashboard/Partials/BudgetHighlights.vue';
import GoalsDebtsCard from './Dashboard/Partials/GoalsDebtsCard.vue';
import MonthSummaryCard from './Dashboard/Partials/MonthSummaryCard.vue';
import NetBalanceCard from './Dashboard/Partials/NetBalanceCard.vue';
import UpcomingRecurringList from './Dashboard/Partials/UpcomingRecurringList.vue';

const props = defineProps({
    appName: { type: String, default: 'Mi Economía' },
    displayCurrency: { type: String, default: 'ARS' },
    totals: { type: Array, default: () => [] },
    convertedTotal: { type: Object, default: null },
    netBalance: { type: Object, default: null },
    accounts: { type: Array, default: () => [] },
    recentTransactions: { type: Array, default: () => [] },
    monthSummary: { type: Object, default: null },
    topSpending: { type: Array, default: () => [] },
    budgets: { type: Array, default: () => [] },
    goals: { type: Array, default: () => [] },
    debtSummary: { type: Object, default: () => ({ iOwe: [], owedToMe: [], overdueCount: 0 }) },
    upcomingRecurring: { type: Array, default: () => [] },
});

/** Acciones rápidas del mockup: cuatro atajos con acento propio. */
const quickActions = [
    { label: 'gasto', href: '/transactions?new=1', tint: 'bg-accent-500', icon: 'M12 5v14M5 12h14' },
    { label: 'cuentas', href: '/accounts', tint: 'bg-brand-500', icon: 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 6h16.5A1.5 1.5 0 0121.75 7.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9A1.5 1.5 0 013.75 6z' },
    { label: 'presupuesto', href: '/budgets', tint: 'bg-lilac-500', icon: 'M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6zM13.5 3v7.5H21A7.5 7.5 0 0013.5 3z' },
    { label: 'metas', href: '/goals', tint: 'bg-gold-500', icon: 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22M21.75 9V4.5m0 0h-4.5' },
];

const hasMonthActivity = computed(
    () => props.monthSummary && (props.monthSummary.income.minorUnits !== 0 || props.monthSummary.expense.minorUnits !== 0),
);

/** Meta destacada para la tarjeta inferior del mockup. */
const featuredGoal = computed(() => props.goals.find((goal) => goal.status === 'active') ?? props.goals[0] ?? null);
</script>

<template>
    <Head title="Inicio" />

    <header class="mb-5 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm text-ink-soft">¡Hola, bienvenido!</p>
            <h1 class="truncate text-xl font-bold tracking-tight text-ink">{{ appName }}</h1>
        </div>
        <Link
            href="/settings"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-card text-ink-soft shadow-sm shadow-black/5"
            aria-label="Ajustes"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M14.857 17.082a23.85 23.85 0 005.454-1.31A8.97 8.97 0 0118 9.75V9A6 6 0 006 9v.75a8.97 8.97 0 01-2.31 6.022c1.733.64 3.56 1.085 5.454 1.31m5.713 0a24.3 24.3 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                />
            </svg>
        </Link>
    </header>

    <section class="rounded-3xl bg-brand-500 p-6 text-white shadow-lg shadow-brand-500/20">
        <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-white/80">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9v3"
                />
            </svg>
            Balance total ({{ displayCurrency }})
        </p>
        <p v-if="convertedTotal" class="amount mt-2 text-4xl font-bold leading-none">{{ convertedTotal.formatted }}</p>
        <p v-else class="mt-2 text-2xl font-bold">Sin cuentas aún</p>
        <div v-if="totals.length > 1" class="mt-3 flex flex-wrap gap-x-3 text-xs text-white/70">
            <span v-for="total in totals" :key="total.currency" class="amount">{{ total.formatted }}</span>
        </div>
        <Link
            href="/accounts"
            class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold transition-colors hover:bg-white/25"
        >
            Gestionar cuentas
            <span aria-hidden="true">→</span>
        </Link>
    </section>

    <section v-if="hasMonthActivity" class="mt-4 grid grid-cols-2 gap-3">
        <AppCard tone="muted">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-pos-soft text-pos">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 5L5 19m0 0h9m-9 0v-9" />
                </svg>
            </span>
            <p class="mt-3 text-xs font-medium lowercase text-ink-soft">ingresos</p>
            <p class="amount mt-1 truncate text-lg font-bold text-pos">+{{ monthSummary.income.formatted }}</p>
        </AppCard>
        <AppCard tone="muted">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-neg-soft text-neg">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 19L19 5m0 0h-9m9 0v9" />
                </svg>
            </span>
            <p class="mt-3 text-xs font-medium lowercase text-ink-soft">gastos</p>
            <p class="amount mt-1 truncate text-lg font-bold text-neg">−{{ monthSummary.expense.formatted }}</p>
        </AppCard>
    </section>

    <section class="mt-6">
        <h2 class="mb-3 text-lg font-bold text-ink">Acciones rápidas</h2>
        <div class="grid grid-cols-4 gap-3">
            <Link v-for="action in quickActions" :key="action.href" :href="action.href" class="flex flex-col items-center gap-2">
                <span
                    class="flex h-16 w-full items-center justify-center rounded-2xl text-white transition-transform active:scale-95"
                    :class="action.tint"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="action.icon" />
                    </svg>
                </span>
                <span class="w-full truncate text-center text-xs text-ink-soft">{{ action.label }}</span>
            </Link>
        </div>
    </section>

    <section class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-bold text-ink">Movimientos recientes</h2>
            <Link href="/transactions" class="flex items-center gap-1 text-sm font-semibold text-accent-500">
                Ver todo
                <span aria-hidden="true">→</span>
            </Link>
        </div>
        <AppCard v-if="recentTransactions.length" :padded="false" class="overflow-hidden">
            <ul class="divide-y divide-line">
                <TransactionListItem v-for="tx in recentTransactions" :key="tx.id" :transaction="tx" />
            </ul>
        </AppCard>
        <EmptyState v-else message="Sin movimientos todavía." />
    </section>

    <section v-if="featuredGoal" class="mt-6">
        <AppCard tone="muted">
            <span class="inline-flex rounded-full bg-accent-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-accent-600">
                Meta de ahorro
            </span>
            <p class="mt-3 text-base font-bold text-ink">{{ featuredGoal.name }}</p>
            <p class="mt-0.5 text-sm text-ink-soft">
                Llevás el {{ Math.round(featuredGoal.progress) }}% de tu meta ({{ featuredGoal.current.formatted }} de
                {{ featuredGoal.target.formatted }}).
            </p>
            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-line">
                <div
                    class="h-full rounded-full bg-accent-500 transition-all duration-500"
                    :style="{ width: `${Math.min(100, featuredGoal.progress)}%` }"
                />
            </div>
        </AppCard>
    </section>

    <NetBalanceCard :net-balance="netBalance" />

    <MonthSummaryCard :summary="monthSummary" :top-spending="topSpending" />

    <BudgetHighlights :budgets="budgets" />

    <section class="mt-6">
        <h2 class="mb-3 text-lg font-bold text-ink">Cuentas</h2>
        <div v-if="accounts.length" class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-1">
            <AppCard v-for="account in accounts" :key="account.id" class="relative w-40 shrink-0 snap-start overflow-hidden">
                <span class="absolute inset-y-0 left-0 w-1.5" :style="{ backgroundColor: account.color || '#3c5e4d' }" />
                <p class="truncate text-sm font-semibold text-ink">{{ account.name }}</p>
                <p class="text-xs text-ink-faint">{{ account.typeLabel }}</p>
                <p class="amount mt-2 truncate font-bold text-ink">{{ account.currentBalance.formatted }}</p>
            </AppCard>
        </div>
        <p v-else class="text-sm text-ink-faint">Todavía no cargaste cuentas.</p>
    </section>

    <GoalsDebtsCard :goals="goals" :debt-summary="debtSummary" />

    <UpcomingRecurringList :recurring="upcomingRecurring" />
</template>
