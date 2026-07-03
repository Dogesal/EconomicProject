<script setup>
import AppCard from '@/Components/AppCard.vue';

defineProps({
    overview: { type: Object, required: true },
});
</script>

<template>
    <AppCard>
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-xs text-slate-400 dark:text-slate-500">Gastado este mes</dt>
                <dd class="mt-0.5 text-lg font-bold text-slate-900 dark:text-slate-100">{{ overview.totalExpense.formatted }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 dark:text-slate-500">Promedio diario</dt>
                <dd class="mt-0.5 text-lg font-bold text-slate-900 dark:text-slate-100">{{ overview.averageDailyExpense.formatted }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 dark:text-slate-500">vs. mes anterior</dt>
                <dd
                    v-if="overview.changePercentage !== null"
                    class="mt-0.5 text-lg font-bold"
                    :class="overview.changePercentage > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                >
                    {{ overview.changePercentage > 0 ? '▲' : '▼' }} {{ Math.abs(overview.changePercentage) }}%
                </dd>
                <dd v-else class="mt-0.5 text-lg font-bold text-slate-400 dark:text-slate-500">Sin datos</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 dark:text-slate-500">Proyección fin de mes</dt>
                <dd class="mt-0.5 text-lg font-bold" :class="overview.projectedExpense ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500'">
                    {{ overview.projectedExpense?.formatted ?? '—' }}
                </dd>
            </div>
        </dl>
        <p v-if="overview.isCurrentMonth" class="mt-3 text-xs text-slate-400 dark:text-slate-500">
            Basado en {{ overview.daysElapsed }} de {{ overview.daysInMonth }} días del mes.
        </p>
    </AppCard>
</template>
