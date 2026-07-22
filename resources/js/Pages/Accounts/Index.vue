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
        <h1 class="text-2xl font-bold tracking-tight text-ink">Cuentas</h1>
        <BaseButton size="sm" @click="openCreate">Nueva</BaseButton>
    </header>

    <ul v-if="accounts.length" class="space-y-3">
        <li
            v-for="account in accounts"
            :key="account.id"
            class="flex cursor-pointer items-center gap-3 overflow-hidden rounded-2xl bg-card p-4 transition-colors active:bg-muted"
            @click="openEdit(account)"
        >
            <span class="h-10 w-1.5 shrink-0 rounded-full" :style="{ backgroundColor: account.color || '#4f46e5' }" />
            <div class="min-w-0 flex-1">
                <p class="truncate font-medium text-ink">{{ account.name }}</p>
                <p class="text-xs text-ink-faint">{{ account.typeLabel }} · {{ account.currency }}</p>
            </div>
            <div class="shrink-0 text-right">
                <p class="amount font-bold text-ink">{{ account.currentBalance.formatted }}</p>
                <button
                    type="button"
                    class="text-xs font-medium text-neg"
                    @click.stop="deleting = account"
                >
                    Eliminar
                </button>
            </div>
        </li>
    </ul>
    <EmptyState v-else message="Creá tu primera cuenta para empezar." />

    <section v-if="archivedAccounts.length" class="mt-8">
        <h2 class="mb-3 text-sm font-semibold text-ink-soft">Archivadas</h2>
        <ul class="space-y-3">
            <li
                v-for="account in archivedAccounts"
                :key="account.id"
                class="flex items-center gap-3 overflow-hidden rounded-2xl border border-dashed border-line bg-muted p-4 dark:bg-card/50"
            >
                <span class="h-10 w-1.5 shrink-0 rounded-full opacity-50" :style="{ backgroundColor: account.color || '#4f46e5' }" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-ink-soft">{{ account.name }}</p>
                    <p class="text-xs text-ink-faint">{{ account.typeLabel }} · {{ account.currency }} · archivada</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="font-semibold text-ink-soft">{{ account.currentBalance.formatted }}</p>
                    <button
                        type="button"
                        class="text-xs font-medium text-brand-500"
                        @click="restore(account)"
                    >
                        Restaurar
                    </button>
                </div>
            </li>
        </ul>
        <p class="mt-2 text-xs text-ink-faint">
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
