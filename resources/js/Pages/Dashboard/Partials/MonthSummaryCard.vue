<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppCard from '@/Components/AppCard.vue';

const props = defineProps({
    summary: { type: Object, default: null },
    topSpending: { type: Array, default: () => [] },
});

const hasActivity = computed(
    () => props.summary && (props.summary.income.minorUnits !== 0 || props.summary.expense.minorUnits !== 0)
);

const netClass = computed(() =>
    props.summary.net.minorUnits < 0
        ? 'text-rose-600 dark:text-rose-400'
        : 'text-emerald-600 dark:text-emerald-400'
);
</script>

<template>
    <section v-if="hasActivity" class="mt-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Este mes</h2>
            <Link href="/reports" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Ver reportes</Link>
        </div>
        <AppCard>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Ingresos</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ summary.income.formatted }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Gastos</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-rose-600 dark:text-rose-400">
                        {{ summary.expense.formatted }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Neto</p>
                    <p class="mt-0.5 truncate text-sm font-semibold" :class="netClass">{{ summary.net.formatted }}</p>
                </div>
            </div>

            <ul v-if="topSpending.length" class="mt-3 space-y-1.5 border-t border-slate-100 pt-3 dark:border-slate-800">
                <li v-for="row in topSpending" :key="row.categoryId" class="flex items-center justify-between gap-2 text-xs">
                    <span class="flex min-w-0 items-center gap-2 text-slate-600 dark:text-slate-300">
                        <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: row.color || '#6366f1' }" />
                        <span class="truncate">{{ row.categoryName }}</span>
                    </span>
                    <span class="shrink-0 text-slate-500 dark:text-slate-400">
                        {{ row.total.formatted }} · {{ row.percentage }}%
                    </span>
                </li>
            </ul>
        </AppCard>
    </section>
</template>
