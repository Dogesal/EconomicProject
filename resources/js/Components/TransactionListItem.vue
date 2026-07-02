<script setup>
defineProps({
    transaction: { type: Object, required: true },
    interactive: { type: Boolean, default: false },
    deletable: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'delete']);
</script>

<template>
    <li
        class="flex items-center gap-3 px-4 py-3"
        :class="interactive ? 'cursor-pointer transition-colors active:bg-slate-50 dark:active:bg-slate-800' : ''"
        @click="interactive && emit('select')"
    >
        <span
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
            :class="
                transaction.isInflow
                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
                    : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'
            "
        >
            <template v-if="transaction.category?.icon">{{ transaction.category.icon }}</template>
            <template v-else-if="transaction.transferGroupId">⇄</template>
            <template v-else>{{ transaction.isInflow ? '↓' : '↑' }}</template>
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">
                {{ transaction.description || transaction.category?.name || transaction.typeLabel }}
            </p>
            <p class="truncate text-xs text-slate-400 dark:text-slate-500">
                {{ transaction.account?.name }} · {{ transaction.occurredOn }}
            </p>
        </div>
        <span
            class="shrink-0 text-sm font-semibold"
            :class="transaction.isInflow ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
        >
            {{ transaction.isInflow ? '+' : '−' }}{{ transaction.amount.formatted }}
        </span>
        <button
            v-if="deletable"
            type="button"
            class="shrink-0 rounded-full p-1.5 text-slate-300 transition-colors hover:bg-rose-50 hover:text-rose-500 dark:text-slate-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
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
