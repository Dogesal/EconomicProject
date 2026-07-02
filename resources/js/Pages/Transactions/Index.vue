<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppCard from '@/Components/AppCard.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BottomSheet from '@/Components/BottomSheet.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import EmptyState from '@/Components/EmptyState.vue';
import TransactionListItem from '@/Components/TransactionListItem.vue';
import TransactionForm from './Partials/TransactionForm.vue';
import TransferForm from './Partials/TransferForm.vue';

const props = defineProps({
    transactions: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const activeSheet = ref(null); // 'create' | 'edit' | 'transfer' | null
const editing = ref(null);
const deleting = ref(null);
const deleteProcessing = ref(false);

const openCreate = () => {
    editing.value = null;
    activeSheet.value = 'create';
};

const openEdit = (tx) => {
    // Transfer legs are linked pairs; editing one side would desync them.
    if (tx.transferGroupId) {
        return;
    }
    editing.value = tx;
    activeSheet.value = 'edit';
};

const closeSheet = () => {
    activeSheet.value = null;
    editing.value = null;
};

const confirmDelete = () => {
    deleteProcessing.value = true;
    router.delete(`/transactions/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
            deleting.value = null;
        },
    });
};
</script>

<template>
    <Head title="Movimientos" />

    <header class="mb-4 flex items-center justify-between gap-2">
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Movimientos</h1>
        <div class="flex gap-2">
            <BaseButton variant="secondary" size="sm" :disabled="accounts.length < 2" @click="activeSheet = 'transfer'">
                Transferir
            </BaseButton>
            <BaseButton size="sm" :disabled="!accounts.length" @click="openCreate">Nuevo</BaseButton>
        </div>
    </header>

    <p
        v-if="!accounts.length"
        class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"
    >
        Primero creá una cuenta para registrar movimientos.
    </p>

    <AppCard v-if="transactions.data.length" :padded="false" class="overflow-hidden">
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <TransactionListItem
                v-for="tx in transactions.data"
                :key="tx.id"
                :transaction="tx"
                :interactive="!tx.transferGroupId"
                deletable
                @select="openEdit(tx)"
                @delete="deleting = tx"
            />
        </ul>
    </AppCard>
    <EmptyState v-else message="Sin movimientos todavía." />

    <BottomSheet :open="activeSheet === 'create' || activeSheet === 'edit'" :title="editing ? 'Editar movimiento' : 'Nuevo movimiento'" @close="closeSheet">
        <TransactionForm
            :key="editing?.id ?? 'create'"
            :accounts="accounts"
            :categories="categories"
            :transaction="editing"
            @saved="closeSheet"
        />
    </BottomSheet>

    <BottomSheet :open="activeSheet === 'transfer'" title="Transferir entre cuentas" @close="closeSheet">
        <TransferForm :accounts="accounts" @saved="closeSheet" />
    </BottomSheet>

    <ConfirmDialog
        :open="deleting !== null"
        title="Eliminar movimiento"
        :message="deleting ? `Se eliminará “${deleting.description || deleting.typeLabel}” y se recalculará el saldo.` : ''"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @cancel="deleting = null"
    />
</template>
