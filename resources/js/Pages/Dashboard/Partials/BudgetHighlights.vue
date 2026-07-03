<script setup>
import { Link } from '@inertiajs/vue3';
import AppCard from '@/Components/AppCard.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

defineProps({
    budgets: { type: Array, default: () => [] },
});

const barClass = (row) => (row.isOverBudget ? 'bg-rose-500' : row.percentage >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
</script>

<template>
    <section v-if="budgets.length" class="mt-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Presupuestos</h2>
            <Link href="/budgets" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Ver todos</Link>
        </div>
        <AppCard>
            <ul class="space-y-3">
                <li v-for="row in budgets" :key="row.budgetId">
                    <div class="mb-1 flex items-center justify-between gap-2 text-xs">
                        <span class="flex min-w-0 items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                            <span v-if="row.category.icon" class="shrink-0">{{ row.category.icon }}</span>
                            <span class="truncate">{{ row.category.name }}</span>
                        </span>
                        <span
                            class="shrink-0"
                            :class="row.isOverBudget ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-slate-400 dark:text-slate-500'"
                        >
                            {{ row.spent.formatted }} de {{ row.budgeted.formatted }}
                        </span>
                    </div>
                    <ProgressBar :percentage="row.percentage" :bar-class="barClass(row)" />
                </li>
            </ul>
        </AppCard>
    </section>
</template>
