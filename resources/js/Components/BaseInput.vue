<script setup>
defineProps({
    type: { type: String, default: 'text' },
});

const model = defineModel({ type: [String, Number], default: '' });
</script>

<template>
    <input
        v-model="model"
        :type="type"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-indigo-400"
    />
</template>
