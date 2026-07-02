<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import BottomSheet from '@/Components/BottomSheet.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AccountForm from './Partials/AccountForm.vue';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    accountTypes: { type: Array, default: () => [] },
});

const sheetOpen = ref(false);
const editing = ref(null);
const deleting = ref(null);
const deleteProcessing = ref(false);

const openCreate = () => {
    editing.value = null;
    sheetOpen.value = true;
};

const openEdit = (account) => {
    editing.value = account;
    sheetOpen.value = true;
};

const closeSheet = () => {
    sheetOpen.value = false;
    editing.value = null;
};

const confirmDelete = () => {
    deleteProcessing.value = true;
    router.delete(`/accounts/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
            deleting.value = null;
        },
    });
};
</script>

<template>
    <Head title="Cuentas" />

    <header class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Cuentas</h1>
        <BaseButton size="sm" @click="openCreate">Nueva</BaseButton>
    </header>

    <ul v-if="accounts.length" class="space-y-3">
        <li
            v-for="account in accounts"
            :key="account.id"
            class="flex cursor-pointer items-center gap-3 overflow-hidden rounded-xl border border-slate-200 bg-white p-4 transition-colors active:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:active:bg-slate-800"
            @click="openEdit(account)"
        >
            <span class="h-10 w-1.5 shrink-0 rounded-full" :style="{ backgroundColor: account.color || '#4f46e5' }" />
            <div class="min-w-0 flex-1">
                <p class="truncate font-medium text-slate-800 dark:text-slate-200">{{ account.name }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ account.typeLabel }} · {{ account.currency }}</p>
            </div>
            <div class="shrink-0 text-right">
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ account.currentBalance.formatted }}</p>
                <button
                    type="button"
                    class="text-xs font-medium text-rose-500 dark:text-rose-400"
                    @click.stop="deleting = account"
                >
                    Eliminar
                </button>
            </div>
        </li>
    </ul>
    <EmptyState v-else message="Creá tu primera cuenta para empezar." />

    <BottomSheet :open="sheetOpen" :title="editing ? 'Editar cuenta' : 'Nueva cuenta'" @close="closeSheet">
        <AccountForm :key="editing?.id ?? 'create'" :account-types="accountTypes" :account="editing" @saved="closeSheet" />
    </BottomSheet>

    <ConfirmDialog
        :open="deleting !== null"
        title="Eliminar cuenta"
        :message="deleting ? `Se eliminará “${deleting.name}” con todos sus movimientos.` : ''"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @cancel="deleting = null"
    />
</template>
