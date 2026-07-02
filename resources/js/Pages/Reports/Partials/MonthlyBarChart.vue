<script setup>
import { computed } from 'vue';

const props = defineProps({
    points: { type: Array, required: true },
});

const maxFlow = computed(() => {
    const values = props.points.flatMap((p) => [p.income.decimal, p.expense.decimal]);

    return Math.max(1, ...values);
});

const heightPct = (money) => `${Math.round((money.decimal / maxFlow.value) * 100)}%`;
</script>

<template>
    <div>
        <div class="flex items-end justify-between gap-2" style="height: 140px">
            <div
                v-for="point in points"
                :key="`${point.year}-${point.month}`"
                class="flex flex-1 flex-col items-center justify-end gap-1"
            >
                <div class="flex h-full w-full items-end justify-center gap-0.5">
                    <div
                        class="w-1/2 rounded-t bg-emerald-400 dark:bg-emerald-500"
                        :style="{ height: heightPct(point.income) }"
                        :title="point.income.formatted"
                    />
                    <div
                        class="w-1/2 rounded-t bg-rose-400 dark:bg-rose-500"
                        :style="{ height: heightPct(point.expense) }"
                        :title="point.expense.formatted"
                    />
                </div>
                <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ point.label }}</span>
            </div>
        </div>
        <div class="mt-3 flex justify-center gap-4 text-[11px] text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-400 dark:bg-emerald-500" /> Ingresos</span>
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-400 dark:bg-rose-500" /> Gastos</span>
        </div>
    </div>
</template>
