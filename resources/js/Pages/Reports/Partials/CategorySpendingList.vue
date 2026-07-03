<script setup>
import ProgressBar from '@/Components/ProgressBar.vue';

defineProps({
    rows: { type: Array, required: true },
});

const emit = defineEmits(['select']);
</script>

<template>
    <ul class="space-y-1">
        <li v-for="row in rows" :key="row.categoryId">
            <button
                type="button"
                class="-mx-2 block w-[calc(100%+1rem)] rounded-lg px-2 py-1 text-left transition-colors active:bg-slate-50 dark:active:bg-slate-800"
                @click="emit('select', row.categoryId)"
            >
                <div class="mb-1 flex items-center justify-between text-sm">
                    <span class="text-slate-700 dark:text-slate-300">{{ row.categoryName }}</span>
                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ row.total.formatted }}</span>
                </div>
                <ProgressBar :percentage="row.percentage" :color="row.color || '#6366f1'" />
            </button>
        </li>
        <li v-if="!rows.length" class="py-6 text-center text-sm text-slate-400 dark:text-slate-500">
            Sin gastos categorizados este mes.
        </li>
    </ul>
</template>
