<script setup>
import AppCard from '@/Components/AppCard.vue';
import TransactionListItem from '@/Components/TransactionListItem.vue';

defineProps({
    habits: { type: Object, required: true },
});
</script>

<template>
    <AppCard>
        <template v-if="habits.topExpenses.length">
            <ul class="-mx-4 divide-y divide-line">
                <TransactionListItem v-for="transaction in habits.topExpenses" :key="transaction.id" :transaction="transaction" />
            </ul>

            <dl class="mt-3 space-y-2 border-t border-line pt-3 text-sm">
                <div v-if="habits.dominantCategory" class="flex items-center justify-between gap-2">
                    <dt class="text-ink-soft">Categoría dominante</dt>
                    <dd class="font-medium text-ink">
                        {{ habits.dominantCategory.categoryName }} ({{ habits.dominantCategory.percentage }}%)
                    </dd>
                </div>
                <div v-if="habits.topWeekday" class="flex items-center justify-between gap-2">
                    <dt class="text-ink-soft">Día con más gastos</dt>
                    <dd class="font-medium text-ink">
                        {{ habits.topWeekday.label }} ({{ habits.topWeekday.total.formatted }})
                    </dd>
                </div>
            </dl>
        </template>
        <p v-else class="py-6 text-center text-sm text-ink-faint">Sin gastos este mes.</p>
    </AppCard>
</template>
