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
            <ul class="-mx-4 divide-y divide-slate-100 dark:divide-slate-800">
                <TransactionListItem v-for="transaction in habits.topExpenses" :key="transaction.id" :transaction="transaction" />
            </ul>

            <dl class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
                <div v-if="habits.dominantCategory" class="flex items-center justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Categoría dominante</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200">
                        {{ habits.dominantCategory.categoryName }} ({{ habits.dominantCategory.percentage }}%)
                    </dd>
                </div>
                <div v-if="habits.topWeekday" class="flex items-center justify-between gap-2">
                    <dt class="text-slate-500 dark:text-slate-400">Día con más gastos</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200">
                        {{ habits.topWeekday.label }} ({{ habits.topWeekday.total.formatted }})
                    </dd>
                </div>
            </dl>
        </template>
        <p v-else class="py-6 text-center text-sm text-slate-400 dark:text-slate-500">Sin gastos este mes.</p>
    </AppCard>
</template>
