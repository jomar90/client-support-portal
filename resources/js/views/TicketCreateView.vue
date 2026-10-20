
<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import {
    ticketStore,
    type TicketCategory,
} from '../stores/ticketStore';

interface ErrorResponse {
    message?: string;
    errors?: Record<string, string[]>;
}

const router = useRouter();

const title = ref('');
const description = ref('');
const errorMessage = ref('');
const submitting = ref(false);

const categories = ref<TicketCategory[]>([]);
const selectedCategoryIds = ref<number[]>([]);
const categoriesLoading = ref(true);
const categoriesError = ref('');

onMounted(async () => {
    try {
        categories.value =
            await ticketStore.actions.getSelectableCategories();
    } catch {
        categoriesError.value =
            'Unable to load categories.';
    } finally {
        categoriesLoading.value = false;
    }
});

async function submit(): Promise<void> {
    if (submitting.value) return;

    errorMessage.value = '';
    submitting.value = true;

    try {
        await ticketStore.actions.createTicket({
            title: title.value,
            description: description.value,
            category_ids: selectedCategoryIds.value,
        });

        await router.push({ name: 'tickets.index' });
    } catch (error) {
        if (axios.isAxiosError<ErrorResponse>(error)) {
            errorMessage.value =
                error.response?.data.errors?.title?.[0]
                ?? error.response?.data.errors?.description?.[0]
                ?? error.response?.data.errors?.category_ids?.[0]
                ?? error.response?.data.message
                ?? 'Unable to create ticket.';
        } else {
            errorMessage.value = 'Unable to create ticket.';
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <main>
        <h1>Create Ticket</h1>

        <form @submit.prevent="submit">
            <div>
                <label for="title">Title</label>

                <input
                    id="title"
                    v-model="title"
                    type="text"
                    maxlength="255"
                    required
                >
            </div>

            <div>
                <label for="description">Description</label>

                <textarea
                    id="description"
                    v-model="description"
                    rows="6"
                    required
                ></textarea>
            </div>

            <fieldset>
                <legend>Categories (optional)</legend>

                <p v-if="categoriesLoading">
                    Loading categories...
                </p>

                <p
                    v-else-if="categoriesError"
                    role="alert"
                >
                    {{ categoriesError }}
                </p>

                <p v-else-if="categories.length === 0">
                    No categories available.
                </p>

                <div
                    v-for="category in categories"
                    :key="category.id"
                >
                    <label>
                        <input
                            v-model="selectedCategoryIds"
                            type="checkbox"
                            :value="category.id"
                        >
                        {{ category.title }}
                    </label>
                </div>
            </fieldset>

            <p v-if="errorMessage" role="alert">
                {{ errorMessage }}
            </p>

            <button
                type="submit"
                :disabled="submitting"
            >
                {{
                    submitting
                        ? 'Creating...'
                        : 'Create Ticket'
                }}
            </button>
        </form>

        <p>
            <RouterLink :to="{ name: 'tickets.index' }">
                Back to Tickets
            </RouterLink>
        </p>
    </main>
</template>
