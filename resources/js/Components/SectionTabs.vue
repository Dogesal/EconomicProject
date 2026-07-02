<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    /** Array of { label, href }. */
    tabs: { type: Array, required: true },
});

const page = usePage();
const currentPath = computed(() => new URL(page.props.ziggy?.location ?? window.location.href).pathname);
</script>

<template>
    <nav
        class="mb-4 grid gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800"
        :style="{ gridTemplateColumns: `repeat(${tabs.length}, 1fr)` }"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            class="rounded-md py-1.5 text-center text-sm font-medium transition-colors"
            :class="
                currentPath.startsWith(tab.href)
                    ? 'bg-white text-indigo-600 shadow dark:bg-slate-900 dark:text-indigo-400'
                    : 'text-slate-500 dark:text-slate-400'
            "
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
