<script setup>
import AppCard from '@/Components/AppCard.vue';

defineProps({
    recurring: { type: Array, default: () => [] },
});
</script>

<template>
    <section v-if="recurring.length" class="mt-6">
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Próximos recurrentes
        </h2>
        <AppCard :padded="false" class="overflow-hidden">
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <li v-for="item in recurring" :key="item.id" class="flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">
                            {{ item.description || item.typeLabel }}
                        </p>
                        <p class="truncate text-xs text-slate-400 dark:text-slate-500">
                            {{ item.frequencyLabel }} · próx. {{ item.nextRunOn }} · {{ item.accountName }}
                        </p>
                    </div>
                    <span
                        class="shrink-0 text-sm font-semibold"
                        :class="item.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                    >
                        {{ item.amount.formatted }}
                    </span>
                </li>
            </ul>
        </AppCard>
    </section>
</template>
