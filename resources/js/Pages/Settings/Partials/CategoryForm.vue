<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import FormField from '@/Components/FormField.vue';
import SegmentedControl from '@/Components/SegmentedControl.vue';

const props = defineProps({
    /** Existing category (edit mode) or null (create mode). */
    category: { type: Object, default: null },
});

const emit = defineEmits(['saved']);

const isEdit = computed(() => props.category !== null);

const colorOptions = ['#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626', '#9333ea', '#475569'];

const form = useForm({
    name: props.category?.name ?? '',
    type: props.category?.type ?? 'expense',
    color: props.category?.color ?? colorOptions[0],
    icon: props.category?.icon ?? '',
});

const typeOptions = [
    { value: 'expense', label: 'Gasto', activeClass: 'text-rose-600 dark:text-rose-400' },
    { value: 'income', label: 'Ingreso', activeClass: 'text-emerald-600 dark:text-emerald-400' },
];

const submit = () => {
    const request = form.transform((data) => ({ ...data, icon: data.icon || null }));

    if (isEdit.value) {
        request.put(`/categories/${props.category.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    } else {
        request.post('/categories', {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    }
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <SegmentedControl v-if="!isEdit" v-model="form.type" :options="typeOptions" />

        <div class="grid grid-cols-[1fr_5rem] gap-3">
            <FormField label="Nombre" :error="form.errors.name">
                <BaseInput v-model="form.name" type="text" />
            </FormField>
            <FormField label="Icono" :error="form.errors.icon">
                <BaseInput v-model="form.icon" type="text" maxlength="2" placeholder="🍕" class="text-center" />
            </FormField>
        </div>

        <FormField label="Color" :error="form.errors.color">
            <div class="flex gap-2">
                <button
                    v-for="color in colorOptions"
                    :key="color"
                    type="button"
                    class="h-8 w-8 rounded-full transition-transform"
                    :class="form.color === color ? 'scale-110 ring-2 ring-slate-400 ring-offset-2 dark:ring-slate-500 dark:ring-offset-slate-900' : ''"
                    :style="{ backgroundColor: color }"
                    :aria-label="`Color ${color}`"
                    @click="form.color = color"
                />
            </div>
        </FormField>

        <BaseButton type="submit" block :processing="form.processing">
            {{ isEdit ? 'Guardar cambios' : 'Crear categoría' }}
        </BaseButton>
    </form>
</template>
