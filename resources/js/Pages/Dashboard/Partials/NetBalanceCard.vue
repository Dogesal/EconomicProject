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
            <h2 class="text-lg font-bold text-ink">Balance real</h2>
            <Link href="/debts" class="text-sm font-semibold text-accent-500">Ver deudas</Link>
        </div>
        <AppCard>
            <div class="flex items-center justify-between gap-2 text-xs font-medium">
                <span class="flex items-center gap-1.5 text-pos">
                    <span class="h-2 w-2 shrink-0 rounded-full bg-pos" />
                    Tengo {{ netBalance.available.formatted }}
                </span>
                <span class="flex items-center gap-1.5 text-neg">
                    Debo {{ netBalance.debts.formatted }}
                    <span class="h-2 w-2 shrink-0 rounded-full bg-neg" />
                </span>
            </div>

            <div class="mt-2 flex h-2.5 w-full overflow-hidden rounded-full bg-muted">
                <div class="h-full bg-pos transition-all" :style="{ width: availableWidth }" />
                <div class="h-full flex-1 bg-neg" />
            </div>

            <div class="mt-3 flex items-center justify-between gap-2 border-t border-line pt-3">
                <p class="text-xs text-ink-faint">
                    {{ netIsNegative ? 'Tus deudas superan tu dinero' : 'Te queda tras pagar tus deudas' }}
                </p>
                <p
                    class="shrink-0 text-base font-bold"
                    :class="netIsNegative ? 'text-neg' : 'text-pos'"
                >
                    {{ netBalance.net.formatted }}
                </p>
            </div>
        </AppCard>
    </section>
</template>
