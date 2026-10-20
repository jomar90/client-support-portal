
<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { categoryStore } from '../stores/categoryStore';

const router = useRouter();

const title = ref('');
const saving = ref(false);
const errorMessage = ref('');

const submitCategory = async (): Promise<void> => {
    if (saving.value) return;

    saving.value = true;
    errorMessage.value = '';

    try {
        await categoryStore.actions.createCategory(
            title.value.trim(),
        );

        await router.push({
            name: 'categories.index',
        });
    } catch {
        errorMessage.value =
            'Could not create category. Please check the title.';
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <main>
        <h1>Create Category</h1>

        <RouterLink :to="{ name: 'categories.index' }">
            Back to categories
        </RouterLink>

        <form @submit.prevent="submitCategory">
            <div>
                <label for="category-title">
                    Category Title
                </label>

                <input
                    id="category-title"
                    v-model="title"
                    type="text"
                    required
                    maxlength="255"
                />
            </div>

            <p v-if="errorMessage" role="alert">
                {{ errorMessage }}
            </p>

            <button type="submit" :disabled="saving">
                {{ saving ? 'Saving...' : 'Create Category' }}
            </button>
        </form>
    </main>
</template>
