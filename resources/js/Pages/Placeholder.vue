<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Próximamente' },
});
</script>

<template>
    <Head :title="title" />

    <header class="mb-4">
        <h1 class="text-xl font-bold text-slate-900">{{ title }}</h1>
    </header>

    <div class="rounded-xl border border-dashed border-slate-300 p-10 text-center">
        <p class="text-sm text-slate-500">Esta sección se construye en la próxima fase. 🚧</p>
    </div>
</template>
