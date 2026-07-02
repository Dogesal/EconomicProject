<script setup>
import { usePage } from '@inertiajs/vue3';
import { onUnmounted, ref, watch } from 'vue';

const page = usePage();
const toast = ref(null); // { type: 'success' | 'error', message }
let hideTimer = null;

const show = (type, message) => {
    toast.value = { type, message };
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => (toast.value = null), 3000);
};

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            show('success', flash.success);
        } else if (flash?.error) {
            show('error', flash.error);
        }
    },
    { deep: true },
);

onUnmounted(() => clearTimeout(hideTimer));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            leave-active-class="transition duration-200"
            enter-from-class="-translate-y-2 opacity-0"
            leave-to-class="-translate-y-2 opacity-0"
        >
            <div
                v-if="toast"
                class="fixed inset-x-0 top-[calc(env(safe-area-inset-top)+0.75rem)] z-[60] mx-auto w-fit max-w-[90%] px-4"
                role="status"
                aria-live="polite"
            >
                <div
                    class="flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium shadow-lg"
                    :class="
                        toast.type === 'success'
                            ? 'bg-emerald-600 text-white dark:bg-emerald-500'
                            : 'bg-rose-600 text-white dark:bg-rose-500'
                    "
                >
                    <svg v-if="toast.type === 'success'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <svg v-else class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3l9.5 16.5h-19L12 3z" />
                    </svg>
                    <span class="truncate">{{ toast.message }}</span>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
