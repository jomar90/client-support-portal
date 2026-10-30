<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { categoryStore } from '../../categories/store';
import type { TicketFormData } from '../types';
import FormError from '../../../components/FormError.vue';

const props = defineProps<{
    ticket: TicketFormData;
    busy: boolean;
}>();
const emit = defineEmits<{
    submit: [data: TicketFormData];
}>();
// Copy the values so editing the form does not change the loaded ticket.
const form = ref<TicketFormData>({
    title: props.ticket.title,
    description: props.ticket.description,
    category_ids: [...props.ticket.category_ids],
});
const categories = categoryStore.getters.all;
// A successful empty list is different from a failed request.
const loaded = ref(false); // Only successful loading allows submission.
const loading = ref(true); // Controls the loading message while waiting.

async function loadCategories() {
    categoryStore.setters.setAll([]); // Do not show choices left over from a previous form.
    try {
        await categoryStore.actions.getAll();
        loaded.value = true;
    } catch {
        // Displayed by the shared error service.
    } finally {
        loading.value = false;
    }
}

onMounted(loadCategories);

function handleSubmit() {
    emit('submit', form.value);
}
</script>
<template>
    <form @submit.prevent="handleSubmit">
        <label for="title">Title</label>
        <input id="title" v-model="form.title" maxlength="255" required>
        <FormError name="title" />
        <label for="description">Description</label>
        <textarea id="description" v-model="form.description" rows="6" required></textarea>
        <FormError name="description" />
        <fieldset>
            <legend>Categories (optional)</legend>
            <p v-if="loading">Loading categories…</p>
            <p v-else-if="!loaded">Categories could not be loaded. Reload the page to try again.</p>
            <label v-for="category in categories" :key="category.id" class="check">
                <input v-model="form.category_ids" type="checkbox" :value="category.id"> {{ category.title }}
            </label>
            <p v-if="loaded && categories.length === 0">No categories available.</p>
            <FormError name="category_ids" />
        </fieldset>
        <button :disabled="busy || !loaded">Save ticket</button>
        <RouterLink :to="{ name: 'tickets.overview' }">Cancel</RouterLink>
    </form>
</template>
