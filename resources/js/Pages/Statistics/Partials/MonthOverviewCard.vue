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
                <dt class="text-xs text-ink-faint">Gastado este mes</dt>
                <dd class="mt-0.5 text-lg font-bold text-ink">{{ overview.totalExpense.formatted }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink-faint">Promedio diario</dt>
                <dd class="mt-0.5 text-lg font-bold text-ink">{{ overview.averageDailyExpense.formatted }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink-faint">vs. mes anterior</dt>
                <dd
                    v-if="overview.changePercentage !== null"
                    class="mt-0.5 text-lg font-bold"
                    :class="overview.changePercentage > 0 ? 'text-neg' : 'text-pos'"
                >
                    {{ overview.changePercentage > 0 ? '▲' : '▼' }} {{ Math.abs(overview.changePercentage) }}%
                </dd>
                <dd v-else class="mt-0.5 text-lg font-bold text-ink-faint">Sin datos</dd>
            </div>
            <div>
                <dt class="text-xs text-ink-faint">Proyección fin de mes</dt>
                <dd class="mt-0.5 text-lg font-bold" :class="overview.projectedExpense ? 'text-ink' : 'text-ink-faint'">
                    {{ overview.projectedExpense?.formatted ?? '—' }}
                </dd>
            </div>
        </dl>
        <p v-if="overview.isCurrentMonth" class="mt-3 text-xs text-ink-faint">
            Basado en {{ overview.daysElapsed }} de {{ overview.daysInMonth }} días del mes.
        </p>
    </AppCard>
</template>
