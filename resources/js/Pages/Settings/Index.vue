<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppCard from '@/Components/AppCard.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import BottomSheet from '@/Components/BottomSheet.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import EmptyState from '@/Components/EmptyState.vue';
import SegmentedControl from '@/Components/SegmentedControl.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import CategoryForm from './Partials/CategoryForm.vue';
import RecurringForm from './Partials/RecurringForm.vue';
import RecurringListItem from './Partials/RecurringListItem.vue';

const props = defineProps({
    displayCurrency: { type: String, default: 'PEN' },
    appLockEnabled: { type: Boolean, default: false },
    currencies: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    recurring: { type: Array, default: () => [] },
});

const page = usePage();

const theme = computed({
    get: () => page.props.theme ?? 'system',
    set: (value) => {
        router.put('/settings/theme', { theme: value }, { preserveScroll: true });
    },
});

const themeOptions = [
    { value: 'system', label: 'Sistema' },
    { value: 'light', label: 'Claro' },
    { value: 'dark', label: 'Oscuro' },
];

const lockEnabled = computed({
    get: () => props.appLockEnabled,
    set: (enabled) => {
        router.put('/settings/lock', { enabled }, { preserveScroll: true });
    },
});

const currencyForm = useForm({ display_currency: props.displayCurrency });
const saveCurrency = () => currencyForm.put('/settings/currency', { preserveScroll: true });

const sheetOpen = ref(false);
const deleting = ref(null);
const deleteProcessing = ref(false);

const confirmDelete = () => {
    deleteProcessing.value = true;
    router.delete(`/recurring/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
            deleting.value = null;
        },
    });
};

// Category management state.
const categorySheetOpen = ref(false);
const editingCategory = ref(null);
const deletingCategory = ref(null);
const categoryDeleteProcessing = ref(false);

const expenseCategories = computed(() => props.categories.filter((c) => c.type === 'expense'));
const incomeCategories = computed(() => props.categories.filter((c) => c.type === 'income'));

const openCategoryCreate = () => {
    editingCategory.value = null;
    categorySheetOpen.value = true;
};

const openCategoryEdit = (category) => {
    editingCategory.value = category;
    categorySheetOpen.value = true;
};

const closeCategorySheet = () => {
    categorySheetOpen.value = false;
    editingCategory.value = null;
};

const confirmCategoryDelete = () => {
    categoryDeleteProcessing.value = true;
    router.delete(`/categories/${deletingCategory.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            categoryDeleteProcessing.value = false;
            deletingCategory.value = null;
        },
    });
};
</script>

<template>
    <Head title="Ajustes" />

    <header class="mb-4">
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Ajustes</h1>
    </header>

    <AppCard class="mb-4">
        <h2 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Moneda de visualización</h2>
        <p class="mb-3 text-xs text-slate-400 dark:text-slate-500">
            Los totales del inicio se convierten a esta moneda cuando hay tasa de cambio.
        </p>
        <div class="flex gap-2">
            <BaseSelect v-model="currencyForm.display_currency" class="flex-1">
                <option v-for="c in currencies" :key="c.code" :value="c.code">{{ c.code }} — {{ c.name }}</option>
            </BaseSelect>
            <BaseButton :processing="currencyForm.processing" @click="saveCurrency">Guardar</BaseButton>
        </div>
    </AppCard>

    <AppCard class="mb-4">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tema</h2>
        <p class="mb-3 mt-0.5 text-xs text-slate-400 dark:text-slate-500">
            Con “Sistema” la app sigue el modo claro/oscuro del celular.
        </p>
        <SegmentedControl v-model="theme" :options="themeOptions" />
    </AppCard>

    <AppCard class="mb-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Bloqueo con biometría</h2>
                <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Pedir huella o Face ID al abrir la app.</p>
            </div>
            <ToggleSwitch v-model="lockEnabled" />
        </div>
    </AppCard>

    <AppCard class="mb-6">
        <h2 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Respaldo</h2>
        <p class="mb-3 text-xs text-slate-400 dark:text-slate-500">Descargá una copia de todos tus datos (archivo SQLite).</p>
        <a
            href="/settings/backup"
            class="inline-block rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-700 dark:bg-slate-200 dark:text-slate-900 dark:hover:bg-slate-300"
        >
            Descargar respaldo
        </a>
    </AppCard>

    <section class="mb-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Categorías</h2>
            <BaseButton size="sm" @click="openCategoryCreate">Nueva</BaseButton>
        </div>

        <AppCard v-if="categories.length" :padded="false" class="overflow-hidden">
            <div v-for="(group, index) in [
                { label: 'Gastos', items: expenseCategories },
                { label: 'Ingresos', items: incomeCategories },
            ]" :key="group.label">
                <p
                    v-if="group.items.length"
                    class="bg-slate-50 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:bg-slate-800/60 dark:text-slate-500"
                    :class="index > 0 ? 'border-t border-slate-100 dark:border-slate-800' : ''"
                >
                    {{ group.label }}
                </p>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li
                        v-for="category in group.items"
                        :key="category.id"
                        class="flex cursor-pointer items-center gap-3 px-4 py-2.5 transition-colors active:bg-slate-50 dark:active:bg-slate-800"
                        @click="openCategoryEdit(category)"
                    >
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm"
                            :style="{ backgroundColor: (category.color || '#64748b') + '22', color: category.color || '#64748b' }"
                        >
                            {{ category.icon || '●' }}
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800 dark:text-slate-200">
                            {{ category.name }}
                        </span>
                        <button
                            type="button"
                            class="shrink-0 rounded-full p-1.5 text-slate-300 transition-colors hover:bg-rose-50 hover:text-rose-500 dark:text-slate-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                            aria-label="Eliminar categoría"
                            @click.stop="deletingCategory = category"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </li>
                </ul>
            </div>
        </AppCard>
        <EmptyState v-else message="Sin categorías. Creá la primera." />
    </section>

    <section>
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Transacciones recurrentes</h2>
            <BaseButton size="sm" :disabled="!accounts.length" @click="sheetOpen = true">Nueva</BaseButton>
        </div>

        <ul v-if="recurring.length" class="space-y-2">
            <RecurringListItem v-for="rec in recurring" :key="rec.id" :recurring="rec" @delete="deleting = rec" />
        </ul>
        <EmptyState v-else message="Sin transacciones recurrentes." />
    </section>

    <BottomSheet :open="sheetOpen" title="Nueva recurrente" @close="sheetOpen = false">
        <RecurringForm :accounts="accounts" :categories="categories" @saved="sheetOpen = false" />
    </BottomSheet>

    <BottomSheet
        :open="categorySheetOpen"
        :title="editingCategory ? 'Editar categoría' : 'Nueva categoría'"
        @close="closeCategorySheet"
    >
        <CategoryForm :key="editingCategory?.id ?? 'create'" :category="editingCategory" @saved="closeCategorySheet" />
    </BottomSheet>

    <ConfirmDialog
        :open="deletingCategory !== null"
        title="Eliminar categoría"
        :message="deletingCategory ? `Se eliminará “${deletingCategory.name}” junto con sus presupuestos; los movimientos quedarán sin categoría.` : ''"
        :processing="categoryDeleteProcessing"
        @confirm="confirmCategoryDelete"
        @cancel="deletingCategory = null"
    />

    <ConfirmDialog
        :open="deleting !== null"
        title="Eliminar recurrente"
        :message="deleting ? `Se dejará de generar “${deleting.description || deleting.typeLabel}”.` : ''"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @cancel="deleting = null"
    />
</template>
