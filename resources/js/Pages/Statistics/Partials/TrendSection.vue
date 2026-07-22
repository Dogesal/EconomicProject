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

        <div class="mt-4 border-t border-line pt-3">
            <p class="mb-2 text-xs text-ink-faint">Tasa de ahorro</p>
            <div class="flex justify-between gap-2">
                <div v-for="point in trend" :key="`${point.year}-${point.month}`" class="flex flex-1 flex-col items-center gap-0.5">
                    <span
                        class="text-xs font-semibold"
                        :class="savingsRate(point) === null
                                ? 'text-ink-faint'
                                : savingsRate(point) >= 0
                                    ? 'text-pos'
                                    : 'text-neg'"
                    >
                        {{ savingsRate(point) === null ? '—' : `${savingsRate(point)}%` }}
                    </span>
                    <span class="text-[10px] text-ink-faint">{{ point.label }}</span>
                </div>
            </div>
        </div>
    </AppCard>
</template>
