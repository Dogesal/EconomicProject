<script setup>
import AppCard from '@/Components/AppCard.vue';
import MonthlyBarChart from '@/Pages/Reports/Partials/MonthlyBarChart.vue';

defineProps({
    trend: { type: Array, required: true },
});

const savingsRate = (point) => (point.income.decimal > 0 ? Math.round((point.net.decimal / point.income.decimal) * 100) : null);
</script>

<template>
    <AppCard>
        <MonthlyBarChart :points="trend" />

        <div class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-800">
            <p class="mb-2 text-xs text-slate-400 dark:text-slate-500">Tasa de ahorro</p>
            <div class="flex justify-between gap-2">
                <div v-for="point in trend" :key="`${point.year}-${point.month}`" class="flex flex-1 flex-col items-center gap-0.5">
                    <span
                        class="text-xs font-semibold"
                        :class="
                            savingsRate(point) === null
                                ? 'text-slate-300 dark:text-slate-600'
                                : savingsRate(point) >= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-rose-600 dark:text-rose-400'
                        "
                    >
                        {{ savingsRate(point) === null ? '—' : `${savingsRate(point)}%` }}
                    </span>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ point.label }}</span>
                </div>
            </div>
        </div>
    </AppCard>
</template>
