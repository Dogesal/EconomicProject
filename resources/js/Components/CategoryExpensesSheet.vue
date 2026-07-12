<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import BottomSheet from '@/Components/BottomSheet.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import TransactionListItem from '@/Components/TransactionListItem.vue';
import TransactionForm from '@/Pages/Transactions/Partials/TransactionForm.vue';

defineProps({
    open: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    expenses: { type: Object, default: null },
    periodLabel: { type: String, default: '' },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

// `changed` avisa al padre para refrescar el drill-down tras editar/borrar.
const emit = defineEmits(['close', 'changed']);

const editing = ref(null);
const deleting = ref(null);
const deleteProcessing = ref(false);

const onSaved = () => {
    editing.value = null;
    emit('changed');
};

const confirmDelete = () => {
    deleteProcessing.value = true;
    router.delete(`/transactions/${deleting.value.id}`, {
        preserveScroll: true,
        onSuccess: () => emit('changed'),
        onFinish: () => {
            deleteProcessing.value = false;
            deleting.value = null;
        },
    });
};
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
                <TransactionListItem
                    v-for="transaction in expenses.transactions"
                    :key="transaction.id"
                    :transaction="transaction"
                    interactive
                    deletable
                    @select="editing = transaction"
                    @delete="deleting = transaction"
                />
            </ul>
            <p v-else class="py-6 text-center text-sm text-slate-400 dark:text-slate-500">
                Sin gastos en esta categoría este mes.
            </p>
        </template>
    </BottomSheet>

    <BottomSheet :open="editing !== null" title="Editar movimiento" @close="editing = null">
        <TransactionForm
            v-if="editing"
            :key="editing.id"
            :accounts="accounts"
            :categories="categories"
            :transaction="editing"
            @saved="onSaved"
        />
    </BottomSheet>

    <ConfirmDialog
        :open="deleting !== null"
        title="Eliminar movimiento"
        :message="deleting ? `Se eliminará “${deleting.description || deleting.category?.name || deleting.typeLabel}” y se recalculará el saldo.` : ''"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @cancel="deleting = null"
    />
</template>
