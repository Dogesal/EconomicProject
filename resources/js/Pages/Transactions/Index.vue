<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import BottomSheet from '@/Components/BottomSheet.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import EmptyState from '@/Components/EmptyState.vue';
import TransactionListItem from '@/Components/TransactionListItem.vue';
import TransactionForm from './Partials/TransactionForm.vue';
import TransferForm from './Partials/TransferForm.vue';
import { monthKey, monthLabel } from '@/utils/dates';

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

const search = ref(props.filters.search ?? '');
const activeCategory = ref(props.filters.category_id ?? '');

/** Un solo punto de entrada a la navegación con filtros, para no duplicar opciones. */
const applyFilters = (overrides = {}) => {
    const query = {
        ...props.filters,
        search: search.value || undefined,
        category_id: activeCategory.value || undefined,
        ...overrides,
    };

    router.get('/transactions', query, { preserveState: true, preserveScroll: true, replace: true });
};

// El buscador dispara en cada tecla: se espera a que el usuario frene para
// no lanzar una visita por carácter.
let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 350);
});

const selectCategory = (categoryId) => {
    activeCategory.value = categoryId;
    applyFilters({ category_id: categoryId || undefined });
};

// "Cargar más" acumula páginas en el cliente: la página 1 (o un cambio de
// filtro) reinicia la lista, las siguientes se agregan al final.
const items = ref([...props.transactions.data]);

watch(
    () => props.transactions,
    (paginator) => {
        items.value = paginator.current_page > 1 ? [...items.value, ...paginator.data] : [...paginator.data];
    },
);

/** Movimientos agrupados por mes, como los encabezados del diseño. */
const groups = computed(() => {
    const buckets = new Map();

    for (const tx of items.value) {
        const key = monthKey(tx.occurredOn);

        if (!buckets.has(key)) {
            buckets.set(key, { key, label: monthLabel(tx.occurredOn), items: [] });
        }

        buckets.get(key).items.push(tx);
    }

    return [...buckets.values()];
});

const nextPageUrl = computed(() => props.transactions.next_page_url ?? null);

const loadMore = () => {
    router.get(nextPageUrl.value, {}, { preserveScroll: true, preserveState: true, only: ['transactions'] });
};

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

onMounted(() => {
    // Home-screen quick action "Nuevo gasto" lands here with ?new=1.
    if (new URL(window.location.href).searchParams.get('new') === '1' && props.accounts.length) {
        openCreate();
    }
});
</script>

<template>
    <Head title="Movimientos" />

    <header class="mb-4 flex items-center justify-between gap-2">
        <h1 class="text-2xl font-bold tracking-tight text-ink">Movimientos</h1>
        <BaseButton variant="secondary" size="sm" :disabled="accounts.length < 2" @click="activeSheet = 'transfer'">
            Transferir
        </BaseButton>
    </header>

    <div class="relative mb-4">
        <svg
            class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
        </svg>
        <input
            v-model="search"
            type="search"
            placeholder="Buscar por concepto…"
            class="w-full rounded-full bg-card py-3 pl-11 pr-4 text-sm text-ink shadow-sm shadow-black/5 placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-brand-500/40"
        />
    </div>

    <div class="-mx-4 mb-5 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <button
            type="button"
            class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors"
            :class="activeCategory ? 'bg-card text-ink-soft ring-1 ring-line' : 'bg-brand-500 text-white'"
            @click="selectCategory('')"
        >
            Todos
        </button>
        <button
            v-for="category in categories"
            :key="category.id"
            type="button"
            class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors"
            :class="activeCategory === category.id ? 'bg-brand-500 text-white' : 'bg-card text-ink-soft ring-1 ring-line'"
            @click="selectCategory(category.id)"
        >
            <span v-if="category.icon" class="mr-1">{{ category.icon }}</span>{{ category.name }}
        </button>
    </div>

    <p v-if="!accounts.length" class="mb-4 rounded-2xl bg-gold-100 p-3 text-sm text-gold-600">
        Primero creá una cuenta para registrar movimientos.
    </p>

    <template v-if="items.length">
        <section v-for="group in groups" :key="group.key" class="mb-6">
            <h2 class="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-ink-soft">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
                    />
                </svg>
                {{ group.label }}
            </h2>
            <ul class="space-y-2.5">
                <TransactionListItem
                    v-for="tx in group.items"
                    :key="tx.id"
                    :transaction="tx"
                    variant="card"
                    :interactive="!tx.transferGroupId"
                    deletable
                    @select="openEdit(tx)"
                    @delete="deleting = tx"
                />
            </ul>
        </section>

        <div v-if="nextPageUrl" class="pb-2 text-center">
            <button type="button" class="text-sm font-bold text-brand-500" @click="loadMore">Cargar más</button>
        </div>
    </template>
    <EmptyState v-else :message="search || activeCategory ? 'Ningún movimiento coincide con el filtro.' : 'Sin movimientos todavía.'" />

    <BottomSheet
        :open="activeSheet === 'create' || activeSheet === 'edit'"
        :title="editing ? 'Editar movimiento' : 'Nuevo movimiento'"
        @close="closeSheet"
    >
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
