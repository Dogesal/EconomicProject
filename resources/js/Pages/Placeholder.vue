<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Próximamente' },
});
</script>

<template>
    <Head :title="title" />

    <header class="mb-4">
        <h1 class="text-2xl font-bold tracking-tight text-ink">{{ title }}</h1>
    </header>

    <div class="rounded-2xl border border-dashed border-line p-10 text-center">
        <p class="text-sm text-ink-soft">Esta sección se construye en la próxima fase. 🚧</p>
    </div>
</template>
