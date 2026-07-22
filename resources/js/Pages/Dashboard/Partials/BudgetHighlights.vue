<script setup>
import { Link } from '@inertiajs/vue3';
import AppCard from '@/Components/AppCard.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

defineProps({
    budgets: { type: Array, default: () => [] },
});

const barClass = (row) => (row.isOverBudget ? 'bg-neg' : row.percentage >= 80 ? 'bg-gold-500' : 'bg-pos');
</script>

<template>
    <section v-if="budgets.length" class="mt-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-lg font-bold text-ink">Presupuestos</h2>
            <Link href="/budgets" class="text-sm font-semibold text-accent-500">Ver todos</Link>
        </div>
        <AppCard>
            <ul class="space-y-3">
                <li v-for="row in budgets" :key="row.budgetId">
                    <div class="mb-1 flex items-center justify-between gap-2 text-xs">
                        <span class="flex min-w-0 items-center gap-1.5 font-medium text-ink-soft">
                            <span v-if="row.category.icon" class="shrink-0">{{ row.category.icon }}</span>
                            <span class="truncate">{{ row.category.name }}</span>
                        </span>
                        <span
                            class="shrink-0"
                            :class="row.isOverBudget ? 'font-semibold text-neg' : 'text-ink-faint'"
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
