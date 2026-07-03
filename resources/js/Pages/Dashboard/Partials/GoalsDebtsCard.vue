<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppCard from '@/Components/AppCard.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

const props = defineProps({
    goals: { type: Array, default: () => [] },
    debtSummary: { type: Object, default: () => ({ iOwe: [], owedToMe: [], overdueCount: 0 }) },
});

const hasDebts = computed(
    () => props.debtSummary.iOwe.length > 0 || props.debtSummary.owedToMe.length > 0
);
</script>

<template>
    <section v-if="goals.length || hasDebts" class="mt-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Metas y deudas</h2>
            <Link href="/goals" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Ver todo</Link>
        </div>
        <AppCard>
            <ul v-if="goals.length" class="space-y-3">
                <li v-for="goal in goals" :key="goal.id">
                    <div class="mb-1 flex items-center justify-between gap-2 text-xs">
                        <span class="truncate font-medium text-slate-700 dark:text-slate-300">{{ goal.name }}</span>
                        <span class="shrink-0 text-slate-400 dark:text-slate-500">
                            {{ goal.current.formatted }} de {{ goal.target.formatted }}
                        </span>
                    </div>
                    <ProgressBar :percentage="goal.progress" bar-class="bg-indigo-500" />
                </li>
            </ul>

            <div
                v-if="hasDebts"
                class="flex flex-wrap items-center gap-2"
                :class="goals.length ? 'mt-3 border-t border-slate-100 pt-3 dark:border-slate-800' : ''"
            >
                <span
                    v-for="total in debtSummary.iOwe"
                    :key="`iowe-${total.currency}`"
                    class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-600 dark:bg-rose-500/10 dark:text-rose-400"
                >
                    Debo {{ total.formatted }}
                </span>
                <span
                    v-for="total in debtSummary.owedToMe"
                    :key="`owed-${total.currency}`"
                    class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                >
                    Me deben {{ total.formatted }}
                </span>
                <span
                    v-if="debtSummary.overdueCount > 0"
                    class="rounded-full bg-rose-500 px-2.5 py-1 text-xs font-semibold text-white"
                >
                    {{ debtSummary.overdueCount }} vencida{{ debtSummary.overdueCount > 1 ? 's' : '' }}
                </span>
            </div>
        </AppCard>
    </section>
</template>
