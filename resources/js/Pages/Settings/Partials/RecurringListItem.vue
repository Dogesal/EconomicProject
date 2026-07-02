<script setup>
defineProps({
    recurring: { type: Object, required: true },
});

const emit = defineEmits(['delete']);
</script>

<template>
    <li class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">
                {{ recurring.description || recurring.typeLabel }}
            </p>
            <p class="truncate text-xs text-slate-400 dark:text-slate-500">
                {{ recurring.frequencyLabel }} · próx. {{ recurring.nextRunOn }} · {{ recurring.accountName }}
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <span
                class="text-sm font-semibold"
                :class="recurring.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
            >
                {{ recurring.amount.formatted }}
            </span>
            <button
                type="button"
                class="rounded-full p-1.5 text-slate-300 transition-colors hover:bg-rose-50 hover:text-rose-500 dark:text-slate-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
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
