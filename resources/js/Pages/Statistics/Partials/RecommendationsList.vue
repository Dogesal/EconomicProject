<script setup>
defineProps({
    recommendations: { type: Array, required: true },
});

const SEVERITY_CLASSES = {
    danger: 'border-neg bg-neg-soft/50 dark:bg-neg/5',
    warning: 'border-gold-500 bg-gold-100/60',
    info: 'border-brand-500 bg-brand-50/50 /5',
};

const SEVERITY_ICONS = {
    danger: 'M12 9v3.75m-9.3 3.4a2 2 0 001.73 3.02h15.14a2 2 0 001.73-3.02L13.73 4.13a2 2 0 00-3.46 0L2.7 16.15zM12 17.25h.01',
    warning: 'M12 9v3.75m0 3.75h.01M12 21a9 9 0 100-18 9 9 0 000 18z',
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};

const SEVERITY_ICON_CLASSES = {
    danger: 'text-neg',
    warning: 'text-gold-600',
    info: 'text-brand-500 ',
};
</script>

<template>
    <ul v-if="recommendations.length" class="space-y-2">
        <li
            v-for="(recommendation, index) in recommendations"
            :key="index"
            class="flex gap-3 rounded-2xl border border-line border-l-4 p-3"
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
                <p class="text-sm font-semibold text-ink">{{ recommendation.title }}</p>
                <p class="mt-0.5 text-xs text-ink-soft">{{ recommendation.message }}</p>
            </div>
        </li>
    </ul>
    <p v-else class="rounded-2xl border border-line py-6 text-center text-sm text-ink-faint dark:text-ink-soft">
        Sin recomendaciones este mes. ¡Todo en orden!
    </p>
</template>
