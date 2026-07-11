<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppCard from '@/Components/AppCard.vue';

const props = defineProps({
    /** { available: MoneyData, debts: MoneyData, net: MoneyData } | null */
    netBalance: { type: Object, default: null },
});

/**
 * Green segment width: money on hand as a share of money + debt, so the red
 * remainder (flex-1) is always the debt share. Negative balances clamp to 0.
 */
const availableWidth = computed(() => {
    const available = Math.max(props.netBalance?.available.decimal ?? 0, 0);
    const debts = Math.max(props.netBalance?.debts.decimal ?? 0, 0);
    const total = available + debts;

    return total > 0 ? `${(available / total) * 100}%` : '0%';
});

const netIsNegative = computed(() => (props.netBalance?.net.minorUnits ?? 0) < 0);
</script>

<template>
    <section v-if="netBalance" class="mt-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Balance real</h2>
            <Link href="/debts" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Ver deudas</Link>
        </div>
        <AppCard>
            <div class="flex items-center justify-between gap-2 text-xs font-medium">
                <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                    <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500" />
                    Tengo {{ netBalance.available.formatted }}
                </span>
                <span class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400">
                    Debo {{ netBalance.debts.formatted }}
                    <span class="h-2 w-2 shrink-0 rounded-full bg-rose-500" />
                </span>
            </div>

            <div class="mt-2 flex h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div class="h-full bg-emerald-500 transition-all" :style="{ width: availableWidth }" />
                <div class="h-full flex-1 bg-rose-500" />
            </div>

            <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                <p class="text-xs text-slate-400 dark:text-slate-500">
                    {{ netIsNegative ? 'Tus deudas superan tu dinero' : 'Te queda tras pagar tus deudas' }}
                </p>
                <p
                    class="shrink-0 text-base font-bold"
                    :class="netIsNegative ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                >
                    {{ netBalance.net.formatted }}
                </p>
            </div>
        </AppCard>
    </section>
</template>
