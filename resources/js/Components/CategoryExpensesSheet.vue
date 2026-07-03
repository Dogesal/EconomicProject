<script setup>
import BottomSheet from '@/Components/BottomSheet.vue';
import TransactionListItem from '@/Components/TransactionListItem.vue';

defineProps({
    open: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    expenses: { type: Object, default: null },
    periodLabel: { type: String, default: '' },
});

const emit = defineEmits(['close']);
</script>

<template>
    <BottomSheet :open="open" :title="expenses?.category ? `${expenses.category.icon ?? ''} ${expenses.category.name}`.trim() : 'Detalle'" @close="emit('close')">
        <div v-if="loading" class="flex justify-center py-10">
            <svg class="h-8 w-8 animate-spin text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
            </svg>
            <span class="sr-only">Cargando…</span>
        </div>

        <template v-else-if="expenses">
            <div class="mb-2 flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ periodLabel }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        {{ expenses.count }} {{ expenses.count === 1 ? 'movimiento' : 'movimientos' }}
                    </p>
                </div>
                <p class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ expenses.total.formatted }}</p>
            </div>

            <ul v-if="expenses.transactions.length" class="-mx-4 divide-y divide-slate-100 dark:divide-slate-800">
                <TransactionListItem v-for="transaction in expenses.transactions" :key="transaction.id" :transaction="transaction" />
            </ul>
            <p v-else class="py-6 text-center text-sm text-slate-400 dark:text-slate-500">
                Sin gastos en esta categoría este mes.
            </p>
        </template>
    </BottomSheet>
</template>
