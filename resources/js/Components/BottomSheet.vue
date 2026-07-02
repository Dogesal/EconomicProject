<script setup>
defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-40 bg-slate-900/40 dark:bg-black/60" @click="emit('close')" />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-250 ease-out"
            leave-active-class="transition-transform duration-200 ease-in"
            enter-from-class="translate-y-full"
            leave-to-class="translate-y-full"
        >
            <section
                v-if="open"
                class="fixed inset-x-0 bottom-0 z-50 mx-auto max-w-md rounded-t-2xl bg-white shadow-2xl dark:bg-slate-900"
                role="dialog"
                aria-modal="true"
            >
                <div class="mx-auto mt-2 h-1 w-10 rounded-full bg-slate-200 dark:bg-slate-700" />
                <header class="flex items-center justify-between px-4 pt-3">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h2>
                    <button
                        type="button"
                        class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                        aria-label="Cerrar"
                        @click="emit('close')"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>
                <div class="max-h-[75vh] overflow-y-auto p-4 pb-[calc(env(safe-area-inset-bottom)+1.25rem)]">
                    <slot />
                </div>
            </section>
        </Transition>
    </Teleport>
</template>
