<script setup>
import { dayLabel } from '@/utils/dates';

defineProps({
    transaction: { type: Object, required: true },
    interactive: { type: Boolean, default: false },
    deletable: { type: Boolean, default: false },
    /** `row` = fila dentro de una lista dividida; `card` = tarjeta suelta (mockup Movimientos). */
    variant: { type: String, default: 'row' },
});

const emit = defineEmits(['select', 'delete']);
</script>

<template>
    <li
        class="flex items-center gap-3"
        :class="[ variant === 'card' ? 'rounded-2xl bg-muted px-4 py-3.5' : 'px-4 py-3.5',
            interactive ? 'cursor-pointer transition-colors active:opacity-70' : '',
        ]"
        @click="interactive && emit('select')"
    >
        <span
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-base"
            :class="variant === 'card' ? 'bg-card' : 'bg-muted'"
            :style="transaction.category?.color ? { color: transaction.category.color } : {}"
        >
            <template v-if="transaction.category?.icon">{{ transaction.category.icon }}</template>
            <template v-else-if="transaction.transferGroupId">⇄</template>
            <template v-else>{{ transaction.isInflow ? '↓' : '↑' }}</template>
        </span>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-ink">
                {{ transaction.description || transaction.category?.name || transaction.typeLabel }}
            </p>
            <p class="truncate text-xs text-ink-faint">
                <span v-if="transaction.category?.name" class="font-semibold uppercase tracking-wide">
                    {{ transaction.category.name }}
                </span>
                <span v-else>{{ transaction.account?.name }}</span>
                · {{ dayLabel(transaction.occurredOn) }}
            </p>
        </div>

        <span class="amount shrink-0 text-sm font-bold" :class="transaction.isInflow ? 'text-pos' : 'text-neg'">
            {{ transaction.isInflow ? '+' : '−' }}{{ transaction.amount.formatted }}
        </span>

        <button
            v-if="deletable"
            type="button"
            class="shrink-0 rounded-full p-1.5 text-ink-faint transition-colors hover:bg-neg-soft hover:text-neg"
            aria-label="Eliminar movimiento"
            @click.stop="emit('delete')"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M14.7 9l-.35 9m-4.7 0L9.3 9m9.97-3.2c.34.05.68.11 1.02.17m-1.02-.17l-1.07 13.87a2.25 2.25 0 01-2.24 2.08H8.34a2.25 2.25 0 01-2.24-2.08L5.03 5.8m14.24 0a48.1 48.1 0 00-3.48-.4m-12.06.57c.34-.06.68-.12 1.02-.17m0 0a48.1 48.1 0 013.48-.4m7.5 0v-.92c0-1.18-.91-2.16-2.09-2.2a51.96 51.96 0 00-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0a48.67 48.67 0 00-7.5 0"
                />
            </svg>
        </button>
    </li>
</template>
