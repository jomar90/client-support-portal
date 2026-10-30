<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Form from '../components/Form.vue';
import { categoryStore } from '../store';
import type { CategoryFormData } from '../types';

const router = useRouter();
const busy = ref(false);

async function createCategory(form: CategoryFormData) {
    busy.value = true;
    try {
        await categoryStore.actions.create(form);
        await router.push({ name: 'categories.overview' });
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Create category</h1>
    <Form :busy="busy" @submit="createCategory" />
</template>
