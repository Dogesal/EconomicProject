import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Shared state for the category expenses bottom sheet: opens the sheet and
 * lazily fetches the optional `categoryExpenses` prop via a partial reload.
 */
export function useCategoryDrilldown() {
    const open = ref(false);
    const loading = ref(false);

    const show = (categoryId) => {
        open.value = true;
        loading.value = true;

        router.reload({
            only: ['categoryExpenses'],
            data: { drill_category: categoryId },
            onFinish: () => {
                loading.value = false;
            },
        });
    };

    const close = () => {
        open.value = false;
    };

    return { open, loading, show, close };
}
