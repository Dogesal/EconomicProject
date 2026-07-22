<script setup>
import AppCard from '@/Components/AppCard.vue';

defineProps({
    recurring: { type: Array, default: () => [] },
});
</script>

<template>
    <section v-if="recurring.length" class="mt-6">
        <h2 class="mb-2 text-lg font-bold text-ink">
            Próximos recurrentes
        </h2>
        <AppCard :padded="false" class="overflow-hidden">
            <ul class="divide-y divide-line">
                <li v-for="item in recurring" :key="item.id" class="flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-ink">
                            {{ item.description || item.typeLabel }}
                        </p>
                        <p class="truncate text-xs text-ink-faint">
                            {{ item.frequencyLabel }} · próx. {{ item.nextRunOn }} · {{ item.accountName }}
                        </p>
                    </div>
                    <span
                        class="shrink-0 text-sm font-semibold"
                        :class="item.type === 'income' ? 'text-pos' : 'text-neg'"
                    >
                        {{ item.amount.formatted }}
                    </span>
                </li>
            </ul>
        </AppCard>
    </section>
</template>
