<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { userStore } from '../store';
import type { ManagedUser } from '../types';

const users = userStore.getters.all;
const loading = ref(true);
const busy = ref(false);
const message = ref('');

onMounted(async () => {
    userStore.setters.setAll([]);
    try {
        await userStore.actions.getAll();
    } catch {
        // Displayed by the shared error service.
    } finally {
        loading.value = false;
    }
});

async function deleteUser(user: ManagedUser) {
    if (!window.confirm(`Delete ${user.first_name} ${user.last_name}? Their resolved tickets will also be deleted. Responses and notes they wrote on other tickets are kept and shown as 'Deleted user'. This cannot be undone.`)) return;
    busy.value = true;
    message.value = '';
    try {
        await userStore.actions.delete(user.id);
        message.value = 'User deleted.';
    } catch {
        // Includes the backend's unresolved-ticket and own-account messages.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Users</h1>
    <p v-if="message" role="status">{{ message }}</p>
    <p v-if="loading">Loading users…</p>
    <div v-else class="table-scroll">
        <table>
            <thead><tr><th>First name</th><th>Last name</th><th>Email address</th><th>Role</th><th>Phone number</th><th>Actions</th></tr></thead>
            <tbody>
                <tr v-for="user in users" :key="user.id">
                    <td>{{ user.first_name }}</td><td>{{ user.last_name }}</td><td>{{ user.email }}</td>
                    <td>{{ user.admin ? 'Administrator' : 'User' }}</td><td>{{ user.phone_number || '—' }}</td>
                    <td>
                        <RouterLink :to="{ name: 'users.edit', params: { id: user.id } }">Edit</RouterLink>
                        <button :disabled="busy" @click="deleteUser(user)">Delete</button>
                    </td>
                </tr>
                <tr v-if="users.length === 0"><td colspan="6">No users to display.</td></tr>
            </tbody>
        </table>
    </div>
</template>
