<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppCard from '@/Components/AppCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import TransactionListItem from '@/Components/TransactionListItem.vue';
import BudgetHighlights from './Dashboard/Partials/BudgetHighlights.vue';
import GoalsDebtsCard from './Dashboard/Partials/GoalsDebtsCard.vue';
import MonthSummaryCard from './Dashboard/Partials/MonthSummaryCard.vue';
import NetBalanceCard from './Dashboard/Partials/NetBalanceCard.vue';
import UpcomingRecurringList from './Dashboard/Partials/UpcomingRecurringList.vue';

defineProps({
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
</script>

<template>
    <Head title="Inicio" />

    <header class="mb-5">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ appName }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Tu economía, en una sola app.</p>
    </header>

    <section
        class="rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-500 p-6 text-white shadow-lg shadow-indigo-200 dark:from-indigo-700 dark:to-indigo-600 dark:shadow-indigo-950/40"
    >
        <p class="text-sm/6 opacity-80">Saldo total ({{ displayCurrency }})</p>
        <p v-if="convertedTotal" class="mt-1 text-3xl font-bold leading-tight">{{ convertedTotal.formatted }}</p>
        <p v-else class="mt-1 text-3xl font-bold">Sin cuentas aún</p>
        <div v-if="totals.length > 1" class="mt-2 flex flex-wrap gap-x-3 text-xs opacity-70">
            <span v-for="total in totals" :key="total.currency">{{ total.formatted }}</span>
        </div>
        <Link
            href="/accounts"
            class="mt-4 inline-block rounded-full bg-white/20 px-4 py-1.5 text-sm font-medium transition-colors hover:bg-white/30"
        >
            Gestionar cuentas
        </Link>
    </section>

    <NetBalanceCard :net-balance="netBalance" />

    <MonthSummaryCard :summary="monthSummary" :top-spending="topSpending" />

    <BudgetHighlights :budgets="budgets" />

    <section class="mt-6">
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Cuentas</h2>
        <div v-if="accounts.length" class="-mx-1 flex snap-x gap-3 overflow-x-auto px-1 pb-1">
            <AppCard
                v-for="account in accounts"
                :key="account.id"
                class="relative w-40 shrink-0 snap-start overflow-hidden"
            >
                <span
                    class="absolute inset-y-0 left-0 w-1"
                    :style="{ backgroundColor: account.color || '#4f46e5' }"
                />
                <p class="truncate text-sm font-medium text-slate-700 dark:text-slate-300">{{ account.name }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ account.typeLabel }}</p>
                <p class="mt-2 truncate font-semibold text-slate-900 dark:text-slate-100">{{ account.currentBalance.formatted }}</p>
            </AppCard>
        </div>
        <p v-else class="text-sm text-slate-400 dark:text-slate-500">Todavía no cargaste cuentas.</p>
    </section>

    <GoalsDebtsCard :goals="goals" :debt-summary="debtSummary" />

    <UpcomingRecurringList :recurring="upcomingRecurring" />

    <section class="mt-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Últimos movimientos</h2>
            <Link href="/transactions" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Ver todos</Link>
        </div>
        <AppCard v-if="recentTransactions.length" :padded="false" class="overflow-hidden">
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <TransactionListItem v-for="tx in recentTransactions" :key="tx.id" :transaction="tx" />
            </ul>
        </AppCard>
        <EmptyState v-else message="Sin movimientos todavía." />
    </section>
</template>
