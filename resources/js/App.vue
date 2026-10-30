<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { authStore } from './domains/auth/store';
import ErrorMessage from './components/ErrorMessage.vue';

const router = useRouter();
const user = authStore.getters.user;
const busy = ref(false);

async function logout() {
    busy.value = true;
    try {
        await authStore.actions.logout();
        await router.push({ name: 'auth.login' });
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <header>
        <strong>Client Support Portal</strong>
        <nav v-if="user" aria-label="Main navigation">
            <RouterLink :to="{ name: 'tickets.overview' }">Tickets</RouterLink>
            <RouterLink v-if="user.admin" :to="{ name: 'categories.overview' }">Categories</RouterLink>
            <RouterLink v-if="user.admin" :to="{ name: 'users.overview' }">Users</RouterLink>
            <span>{{ user.first_name }} {{ user.last_name }}</span>
            <button :disabled="busy" @click="logout">Log out</button>
        </nav>
    </header>
    <main>
        <ErrorMessage />
        <RouterView :key="$route.fullPath" />
    </main>
</template>
