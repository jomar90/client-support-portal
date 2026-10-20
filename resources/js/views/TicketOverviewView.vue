<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';

import { authStore } from '../stores/authStore';

import { onMounted } from 'vue';
import { ticketStore } from '../stores/ticketStore';

onMounted(() => {
    void ticketStore.actions.fetchTickets();
});

function formatDate(value: string): string {
    return new Date(value).toLocaleString();
}

const router = useRouter();

const user = computed(() => authStore.state.user);

const loggingOut = ref(false);
const logoutError = ref('');

async function logout(): Promise<void> {
    logoutError.value = '';
    loggingOut.value = true;

    try {
        await authStore.actions.logout();

        await router.push({
            name: 'login',
        });
    } catch {
        logoutError.value = 'Unable to log out.';
    } finally {
        loggingOut.value = false;
    }
}
</script>

<template>
    <main>
        <header>
            <div>
                <h1>Tickets</h1>

                    <RouterLink :to="{ name: 'tickets.create' }">
                        Create Ticket
                    </RouterLink>

                <p v-if="user">
                    Signed in as
                    {{ user.first_name }}
                    {{ user.last_name }}
                    ({{ user.email }})
                </p>
            </div>

            <button
                type="button"
                :disabled="loggingOut"
                @click="logout"
            >
                {{ loggingOut ? 'Logging out...' : 'Logout' }}
            </button>
        </header>

        <p v-if="logoutError">
            {{ logoutError }}
        </p>


        <p v-if="ticketStore.state.loading">
            Loading tickets...
        </p>

        <p
            v-else-if="ticketStore.state.error"
            role="alert"
        >
            {{ ticketStore.state.error }}
        </p>

        <p v-else-if="ticketStore.state.tickets.length === 0">
            No tickets yet.
        </p>

        <table v-else>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Categories</th>
                    <th>Status</th>
                    <th>Created by</th>
                    <th>Created at</th>
                    <th>Last updated at</th>
                    <th>Assigned to</th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="ticket in ticketStore.state.tickets"
                    :key="ticket.id"
                >
                    <td>{{ ticket.id }}</td>

                    <td>
                        <RouterLink
                            :to="{
                                name: 'tickets.show',
                                params: { id: ticket.id }
                            }"
                        >
                            {{ ticket.title }}
                        </RouterLink>

                        <RouterLink
                            :to="{
                                name: 'tickets.edit',
                                params: { id: ticket.id }
                            }"
                        >
                            Edit
                        </RouterLink>
                    </td>

                    <td>{{ ticket.categories.join(', ') || '—' }}</td>
                    <td>{{ ticket.status }}</td>
                    <td>{{ ticket.created_by }}</td>
                    <td>{{ formatDate(ticket.created_at) }}</td>
                    <td>{{ formatDate(ticket.updated_at) }}</td>
                    <td>{{ ticket.assigned_to ?? '—' }}</td>
                </tr>
            </tbody>
        </table>

    </main>
</template>
