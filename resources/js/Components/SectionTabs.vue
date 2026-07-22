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
    <nav class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors"
            :class="currentPath.startsWith(tab.href) ? 'bg-brand-500 text-white' : 'bg-card text-ink-soft ring-1 ring-line'"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
