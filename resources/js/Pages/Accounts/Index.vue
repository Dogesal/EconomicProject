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
    archivedAccounts: { type: Array, default: () => [] },
    accountTypes: { type: Array, default: () => [] },
});

const sheetOpen = ref(false);
const editing = ref(null);
const deleting = ref(null);
const deleteProcessing = ref(false);

const restore = (account) => {
    router.post(`/accounts/${account.id}/restore`, {}, { preserveScroll: true });
};

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

    <section v-if="archivedAccounts.length" class="mt-8">
        <h2 class="mb-3 text-sm font-semibold text-slate-500 dark:text-slate-400">Archivadas</h2>
        <ul class="space-y-3">
            <li
                v-for="account in archivedAccounts"
                :key="account.id"
                class="flex items-center gap-3 overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/50"
            >
                <span class="h-10 w-1.5 shrink-0 rounded-full opacity-50" :style="{ backgroundColor: account.color || '#4f46e5' }" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-slate-600 dark:text-slate-300">{{ account.name }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ account.typeLabel }} · {{ account.currency }} · archivada</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="font-semibold text-slate-500 dark:text-slate-400">{{ account.currentBalance.formatted }}</p>
                    <button
                        type="button"
                        class="text-xs font-medium text-indigo-600 dark:text-indigo-400"
                        @click="restore(account)"
                    >
                        Restaurar
                    </button>
                </div>
            </li>
        </ul>
        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
            Las cuentas archivadas conservan su historial y no se pueden editar. Restáuralas para volver a usarlas.
        </p>
    </section>

    <BottomSheet :open="sheetOpen" :title="editing ? 'Editar cuenta' : 'Nueva cuenta'" @close="closeSheet">
        <AccountForm :key="editing?.id ?? 'create'" :account-types="accountTypes" :account="editing" @saved="closeSheet" />
    </BottomSheet>

    <ConfirmDialog
        :open="deleting !== null"
        title="Eliminar cuenta"
        :message="deleting ? `Si “${deleting.name}” tiene movimientos se archivará (conserva su historial); si está vacía se eliminará.` : ''"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @cancel="deleting = null"
    />
</template>
