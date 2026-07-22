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
        ? 'text-neg'
        : 'text-pos'
);
</script>

<template>
    <section v-if="hasActivity" class="mt-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-lg font-bold text-ink">Este mes</h2>
            <Link href="/reports" class="text-sm font-semibold text-accent-500">Ver reportes</Link>
        </div>
        <AppCard>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div>
                    <p class="text-xs text-ink-faint">Ingresos</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-pos">
                        {{ summary.income.formatted }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-ink-faint">Gastos</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-neg">
                        {{ summary.expense.formatted }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-ink-faint">Neto</p>
                    <p class="mt-0.5 truncate text-sm font-semibold" :class="netClass">{{ summary.net.formatted }}</p>
                </div>
            </div>

            <ul v-if="topSpending.length" class="mt-3 space-y-1.5 border-t border-line pt-3">
                <li v-for="row in topSpending" :key="row.categoryId" class="flex items-center justify-between gap-2 text-xs">
                    <span class="flex min-w-0 items-center gap-2 text-ink-soft">
                        <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: row.color || '#6366f1' }" />
                        <span class="truncate">{{ row.categoryName }}</span>
                    </span>
                    <span class="shrink-0 text-ink-soft">
                        {{ row.total.formatted }} · {{ row.percentage }}%
                    </span>
                </li>
            </ul>
        </AppCard>
    </section>
</template>
