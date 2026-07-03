<script setup>
defineProps({
    recommendations: { type: Array, required: true },
});

const SEVERITY_CLASSES = {
    danger: 'border-rose-500 bg-rose-50/50 dark:bg-rose-500/5',
    warning: 'border-amber-500 bg-amber-50/50 dark:bg-amber-500/5',
    info: 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-500/5',
};

const SEVERITY_ICONS = {
    danger: 'M12 9v3.75m-9.3 3.4a2 2 0 001.73 3.02h15.14a2 2 0 001.73-3.02L13.73 4.13a2 2 0 00-3.46 0L2.7 16.15zM12 17.25h.01',
    warning: 'M12 9v3.75m0 3.75h.01M12 21a9 9 0 100-18 9 9 0 000 18z',
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};

const SEVERITY_ICON_CLASSES = {
    danger: 'text-rose-500 dark:text-rose-400',
    warning: 'text-amber-500 dark:text-amber-400',
    info: 'text-indigo-500 dark:text-indigo-400',
};
</script>

<template>
    <ul v-if="recommendations.length" class="space-y-2">
        <li
            v-for="(recommendation, index) in recommendations"
            :key="index"
            class="flex gap-3 rounded-xl border border-slate-200 border-l-4 p-3 dark:border-slate-800"
            :class="SEVERITY_CLASSES[recommendation.severity]"
        >
            <svg
                class="mt-0.5 h-5 w-5 shrink-0"
                :class="SEVERITY_ICON_CLASSES[recommendation.severity]"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.8"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" :d="SEVERITY_ICONS[recommendation.severity]" />
            </svg>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ recommendation.title }}</p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ recommendation.message }}</p>
            </div>
        </li>
    </ul>
    <p v-else class="rounded-xl border border-slate-200 py-6 text-center text-sm text-slate-400 dark:border-slate-800 dark:text-slate-500">
        Sin recomendaciones este mes. ¡Todo en orden!
    </p>
</template>
