<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { categoryStore } from '../store';
import type { Category } from '../types';

const categories = categoryStore.getters.all;
const loading = ref(true);
const busy = ref(false);
const message = ref('');

async function loadCategories() {
    categoryStore.setters.setAll([]);
    try {
        await categoryStore.actions.getAll();
    } catch {
        // Displayed by the shared error service.
    } finally {
        loading.value = false;
    }
}

onMounted(loadCategories);

async function deleteCategory(category: Category) {
    if (!window.confirm(`Delete category "${category.title}"?`)) {
        return;
    }
    busy.value = true;
    message.value = '';
    try {
        await categoryStore.actions.delete(category.id);
        message.value = 'Category deleted.';
    } catch {
        // Includes the backend's protected-deletion message.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Categories</h1>
    <RouterLink :to="{ name: 'categories.create' }">Create category</RouterLink>
    <p v-if="message" role="status">{{ message }}</p>
    <p v-if="loading">Loading categories…</p>
    <table v-else>
        <thead>
            <tr>
                <th>Title</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="category in categories" :key="category.id">
                <td>{{ category.title }}</td>
                <td>
                    <button :disabled="busy" @click="deleteCategory(category)">Delete</button>
                </td>
            </tr>
            <tr v-if="categories.length === 0">
                <td colspan="2">No categories to display.</td>
            </tr>
        </tbody>
    </table>
</template>
