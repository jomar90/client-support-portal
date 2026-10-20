
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { categoryStore } from '../stores/categoryStore';

import axios from 'axios';


const loading = ref(true);
const errorMessage = ref('');

const deletingId = ref<number | null>(null);
const deleteError = ref('');
const successMessage = ref('');


onMounted(async () => {
    try {
        await categoryStore.actions.fetchCategories();
    } catch {
        errorMessage.value = 'Could not load categories.';
    } finally {
        loading.value = false;
    }
});


const deleteCategory = async (
    id: number,
    title: string,
): Promise<void> => {
    if (deletingId.value !== null) return;

    const confirmed = window.confirm(
        `Are you sure you want to delete "${title}"?`,
    );

    if (!confirmed) return;

    deletingId.value = id;
    deleteError.value = '';
    successMessage.value = '';

    try {
        await categoryStore.actions.deleteCategory(id);

        await categoryStore.actions.fetchCategories();

        successMessage.value = 'Category deleted successfully.';
    } catch (error) {
        if (
            axios.isAxiosError<{ message?: string }>(error)
            && error.response?.status === 409
        ) {
            deleteError.value =
                error.response.data.message
                ?? 'This category is associated with tickets.';
        } else {
            deleteError.value = 'Could not delete category.';
        }
    } finally {
        deletingId.value = null;
    }
};

</script>

<template>
    <main>
        <h1>Category Overview</h1>

        <RouterLink to="/tickets">
            Back to tickets
        </RouterLink>

        <RouterLink :to="{ name: 'categories.create' }">
            Create Category
        </RouterLink>


        <p v-if="deleteError" role="alert">
            {{ deleteError }}
        </p>

        <p v-if="successMessage" role="status">
            {{ successMessage }}
        </p>


        <p v-if="loading">Loading categories...</p>

        <p v-else-if="errorMessage">
            {{ errorMessage }}
        </p>


        <table v-else>
            <thead>
                <tr>
                    <th>Category Title</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="category in categoryStore.state.categories"
                    :key="category.id"
                >
                    <td>{{ category.title }}</td>

                    <td>
                        <button
                            type="button"
                            :disabled="deletingId !== null"
                            @click="deleteCategory(
                                category.id,
                                category.title
                            )"
                        >
                            {{
                                deletingId === category.id
                                    ? 'Deleting...'
                                    : 'Delete'
                            }}
                        </button>
                    </td>
                </tr>

                <tr
                    v-if="categoryStore.state.categories.length === 0"
                >
                    <td colspan="2">No categories found.</td>
                </tr>
            </tbody>
        </table>

    </main>
</template>
