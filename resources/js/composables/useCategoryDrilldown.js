import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Shared state for the category expenses bottom sheet: opens the sheet and
 * lazily fetches the optional `categoryExpenses` prop via a partial reload.
 * Remembers the drilled category so the list can be refreshed after editing
 * or deleting one of its transactions.
 */
export function useCategoryDrilldown() {
    const open = ref(false);
    const loading = ref(false);
    const categoryId = ref(null);

    const load = () => {
        loading.value = true;

        router.reload({
            only: ['categoryExpenses'],
            data: { drill_category: categoryId.value },
            onFinish: () => {
                loading.value = false;
            },
        });
    };

    const show = (id) => {
        categoryId.value = id;
        open.value = true;
        load();
    };

    const refresh = () => {
        if (categoryId.value) {
            load();
        }
    };

    const close = () => {
        open.value = false;
    };

    return { open, loading, show, refresh, close };
}
