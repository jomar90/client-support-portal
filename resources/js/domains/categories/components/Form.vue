<script setup lang="ts">
import { ref } from 'vue';
import type { CategoryFormData } from '../types';
import FormError from '../../../components/FormError.vue';
defineProps<{
    busy: boolean;
}>();
const emit = defineEmits<{
    submit: [data: CategoryFormData];
}>();
const form = ref<CategoryFormData>({ title: '' });

function handleSubmit() {
    emit('submit', form.value);
}
</script>
<template>
    <form @submit.prevent="handleSubmit">
        <label for="title">Title</label>
        <input id="title" v-model="form.title" maxlength="255" required>
        <FormError name="title" />
        <button :disabled="busy">Save category</button>
        <RouterLink :to="{ name: 'categories.overview' }">Cancel</RouterLink>
    </form>
</template>
