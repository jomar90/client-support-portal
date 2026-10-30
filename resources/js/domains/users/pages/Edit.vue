<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { userStore } from '../store';
import type { UserFormData } from '../types';
import FormError from '../../../components/FormError.vue';

const route = useRoute();
const router = useRouter();
const id = Number(route.params.id);
const form = ref<UserFormData | null>(null);
const loading = ref(true);
const busy = ref(false);

onMounted(async () => {
    try {
        const user = await userStore.actions.getById(id);
        form.value = {
            first_name: user.first_name, last_name: user.last_name,
            email: user.email, phone_number: user.phone_number,
        };
    } catch {
        // Displayed by the shared error service.
    } finally {
        loading.value = false;
    }
});

async function updateUser() {
    if (!form.value) return;
    busy.value = true;
    try {
        await userStore.actions.update(id, form.value);
        await router.push({ name: 'users.overview' });
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Edit user</h1>
    <p v-if="loading">Loading user…</p>
    <form v-if="form" @submit.prevent="updateUser">
        <label for="first-name">First name</label>
        <input id="first-name" v-model="form.first_name" maxlength="255" required>
        <FormError name="first_name" />
        <label for="last-name">Last name</label>
        <input id="last-name" v-model="form.last_name" maxlength="255" required>
        <FormError name="last_name" />
        <label for="email">Email address</label>
        <input id="email" v-model="form.email" type="email" maxlength="255" required>
        <FormError name="email" />
        <label for="phone-number">Phone number (optional)</label>
        <input id="phone-number" v-model="form.phone_number" type="tel" maxlength="255">
        <FormError name="phone_number" />
        <button :disabled="busy">Save user</button>
    </form>
    <RouterLink :to="{ name: 'users.overview' }">Back to users</RouterLink>
</template>
