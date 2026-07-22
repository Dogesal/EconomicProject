<script setup>
defineProps({
    recurring: { type: Object, required: true },
});

const emit = defineEmits(['delete']);
</script>

<template>
    <li class="flex items-center justify-between gap-3 rounded-2xl border border-line bg-card p-3 dark:bg-card">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-ink">
                {{ recurring.description || recurring.typeLabel }}
            </p>
            <p class="truncate text-xs text-ink-faint">
                {{ recurring.frequencyLabel }} · próx. {{ recurring.nextRunOn }} · {{ recurring.accountName }}
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <span
                class="text-sm font-semibold"
                :class="recurring.type === 'income' ? 'text-pos' : 'text-neg'"
            >
                {{ recurring.amount.formatted }}
            </span>
            <button
                type="button"
                class="rounded-full p-1.5 text-ink-faint transition-colors hover:bg-neg-soft hover:text-neg dark:text-ink-soft dark:hover:bg-neg/10 dark:hover:text-neg"
                aria-label="Eliminar recurrente"
                @click="emit('delete')"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </li>
</template>
