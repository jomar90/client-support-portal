<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import {
    ticketStore,
    type TicketDetail,
    type TicketAdministrator,
    type TicketStatus,
    type TicketResponse,
} from '../stores/ticketStore';
import { authStore } from '../stores/authStore';


const route = useRoute();
const ticketId = Number(route.params.id);

const ticket = ref<TicketDetail | null>(null);
const loading = ref(true);
const errorMessage = ref('');

const administrators = ref<TicketAdministrator[]>([]);
const selectedAdministratorId = ref<number | null>(null);
const assignmentError = ref('');
const assigning = ref(false);

const selectedStatus = ref<TicketStatus>('pending');
const statusError = ref('');
const savingStatus = ref(false);

const responseBody = ref('');
const submittingResponse = ref(false);
const responseError = ref('');
const responseSuccess = ref('');


const editingResponseId = ref<number | null>(null);
const editedResponseBody = ref('');
const savingResponseEdit = ref(false);
const responseEditError = ref('');

function startEditingResponse(
    response: TicketResponse
): void {
    editingResponseId.value = response.id;
    editedResponseBody.value = response.body;
    responseEditError.value = '';
}

function cancelEditingResponse(): void {
    editingResponseId.value = null;
    editedResponseBody.value = '';
    responseEditError.value = '';
}

async function saveEditedResponse(): Promise<void> {
    if (
        !ticket.value
        || editingResponseId.value === null
        || savingResponseEdit.value
        || !authStore.state.user?.admin
    ) {
        return;
    }

    const body = editedResponseBody.value.trim();

    if (!body) {
        responseEditError.value = 'Response cannot be empty.';
        return;
    }

    savingResponseEdit.value = true;
    responseEditError.value = '';

    try {
        const responseId = editingResponseId.value;

        await ticketStore.actions.updateResponse(
            ticket.value.id,
            responseId,
            body
        );

        const response = ticket.value.responses.find(
            (item) => item.id === responseId
        );

        if (response) {
            response.body = body;
        }

        cancelEditingResponse();
    } catch {
        responseEditError.value =
            'Could not confirm the update. Refresh before retrying.';
    } finally {
        savingResponseEdit.value = false;
    }
}


onMounted(async () => {
    try {
        if (!Number.isSafeInteger(ticketId) || ticketId < 1) {
            throw new Error('Invalid ticket ID');
        }

        ticket.value = await ticketStore.actions.getTicket(ticketId);

        selectedStatus.value = ticket.value.status;

        if (authStore.state.user?.admin) {
            try {
                administrators.value =
                    await ticketStore.actions.getAdministrators();
            } catch {
                assignmentError.value = 'Unable to load administrators.';
            }
        }
    } catch {
        errorMessage.value = 'Ticket not found or access denied.';
    } finally {
        loading.value = false;
    }
});


async function assignTicket(): Promise<void> {
    if (!ticket.value || selectedAdministratorId.value === null) {
        return;
    }

    assignmentError.value = '';
    assigning.value = true;

    try {
        await ticketStore.actions.assignTicket(
            ticket.value.id,
            selectedAdministratorId.value
        );

        ticket.value = await ticketStore.actions.getTicket(
            ticket.value.id
        );
    } catch {
        assignmentError.value = 'Unable to assign ticket.';
    } finally {
        assigning.value = false;
    }
}


async function changeStatus(): Promise<void> {
    if (!ticket.value) {
        return;
    }

    statusError.value = '';
    savingStatus.value = true;

    try {
        await ticketStore.actions.updateTicketStatus(
            ticket.value.id,
            selectedStatus.value
        );

        ticket.value = await ticketStore.actions.getTicket(
            ticket.value.id
        );
    } catch {
        statusError.value = 'Unable to update ticket status.';
    } finally {
        savingStatus.value = false;
    }
}

async function submitResponse(): Promise<void> {
    if (
        !ticket.value
        || submittingResponse.value
        || !authStore.state.user?.admin
    ) {
        return;
    }

    const body = responseBody.value.trim();

    responseError.value = '';
    responseSuccess.value = '';

    if (!body) {
        responseError.value = 'Please enter a response.';
        return;
    }

    submittingResponse.value = true;

    try {
        await ticketStore.actions.addResponse(
            ticket.value.id,
            body
        );

        responseBody.value = '';
        responseSuccess.value = 'Response added successfully.';

        try {
            ticket.value = await ticketStore.actions.getTicket(
                ticket.value.id
            );
        } catch {
            responseError.value =
                'Response saved, but the list could not refresh. Reload the page.';
        }
    } catch (error) {
        if (
            axios.isAxiosError<{
                errors?: { body?: string[] };
            }>(error)
            && error.response?.status === 422
        ) {
            responseError.value =
                error.response.data.errors?.body?.[0]
                ?? 'Invalid response.';
        } else {
            responseError.value =
                'Could not confirm the response. Check the ticket before retrying.';
        }
    } finally {
        submittingResponse.value = false;
    }
}




function formatDate(value: string): string {
    return new Date(value).toLocaleString();
}
</script>

<template>
    <main>
        <h1>Ticket Details</h1>

        <p v-if="loading">Loading ticket...</p>

        <p v-else-if="errorMessage" role="alert">
            {{ errorMessage }}
        </p>

        <template v-else-if="ticket">
            <h2>#{{ ticket.id }} — {{ ticket.title }}</h2>

            <p><strong>Description:</strong></p>
            <p>{{ ticket.description }}</p>

            <p>
                <strong>Status:</strong>
                {{ ticket.status.replace('_', ' ') }}
            </p>

            <section v-if="authStore.state.user?.admin">
                <h3>Change Ticket Status</h3>

                <form @submit.prevent="changeStatus">
                    <label for="ticket-status">Status</label>

                    <select id="ticket-status" v-model="selectedStatus">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>

                    <button type="submit" :disabled="savingStatus">
                        {{ savingStatus ? 'Saving...' : 'Update Status' }}
                    </button>
                </form>

                <p v-if="statusError" role="alert">
                    {{ statusError }}
                </p>
            </section>

            <p>
                <strong>Categories:</strong>
                {{ ticket.categories.join(', ') || 'None' }}
            </p>

            <p>
                <strong>Created by:</strong>
                {{ ticket.created_by }}
            </p>

            <p>
                <strong>Assigned to:</strong>
                {{ ticket.assigned_to ?? 'Unassigned' }}
            </p>

            <section v-if="authStore.state.user?.admin">
                <h3>Assign Ticket</h3>

                <form @submit.prevent="assignTicket">
                    <label for="assignee">Administrator</label>

                    <select
                        id="assignee"
                        v-model.number="selectedAdministratorId"
                        required
                    >
                        <option :value="null" disabled>
                            Select administrator
                        </option>

                        <option
                            v-for="admin in administrators"
                            :key="admin.id"
                            :value="admin.id"
                        >
                            {{ admin.first_name }} {{ admin.last_name }}
                        </option>
                    </select>

                    <button
                        type="submit"
                        :disabled="assigning || selectedAdministratorId === null"
                    >
                        {{ assigning ? 'Assigning...' : 'Assign Ticket' }}
                    </button>
                </form>

                <p v-if="assignmentError" role="alert">
                    {{ assignmentError }}
                </p>
            </section>


            <p>
                <strong>Created at:</strong>
                {{ formatDate(ticket.created_at) }}
            </p>

            <p>
                <strong>Last updated:</strong>
                {{ formatDate(ticket.updated_at) }}
            </p>

            <RouterLink
                :to="{
                    name: 'tickets.edit',
                    params: { id: ticket.id }
                }"
            >
                Edit Ticket
            </RouterLink>

            <h3>Responses</h3>

            <section v-if="authStore.state.user?.admin">
                <h4>Add Response</h4>

                <form @submit.prevent="submitResponse">
                    <div>
                        <label for="response-body">
                            Your Response
                        </label>

                        <textarea
                            id="response-body"
                            v-model="responseBody"
                            rows="5"
                            required
                            :disabled="submittingResponse"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        :disabled="submittingResponse"
                    >
                        {{
                            submittingResponse
                                ? 'Submitting...'
                                : 'Add Response'
                        }}
                    </button>
                </form>

                <p v-if="responseError" role="alert">
                    {{ responseError }}
                </p>

                <p v-if="responseSuccess" role="status">
                    {{ responseSuccess }}
                </p>
            </section>


            <p v-if="ticket.responses.length === 0">
                No responses yet.
            </p>

            <section
                v-for="response in ticket.responses"
                :key="response.id"
            >
                <template
                    v-if="
                        editingResponseId === response.id
                        && authStore.state.user?.admin
                    "
                >
                    <form @submit.prevent="saveEditedResponse">
                        <label :for="`edit-response-${response.id}`">
                            Edit Response
                        </label>

                        <textarea
                            :id="`edit-response-${response.id}`"
                            v-model="editedResponseBody"
                            rows="5"
                            required
                            :disabled="savingResponseEdit"
                        ></textarea>

                        <button
                            type="submit"
                            :disabled="savingResponseEdit"
                        >
                            {{
                                savingResponseEdit
                                    ? 'Saving...'
                                    : 'Save Changes'
                            }}
                        </button>

                        <button
                            type="button"
                            :disabled="savingResponseEdit"
                            @click="cancelEditingResponse"
                        >
                            Cancel
                        </button>

                        <p v-if="responseEditError" role="alert">
                            {{ responseEditError }}
                        </p>
                    </form>
                </template>

                <template v-else>
                    <p>{{ response.body }}</p>

                    <button
                        v-if="authStore.state.user?.admin"
                        type="button"
                        @click="startEditingResponse(response)"
                    >
                        Edit Response
                    </button>
                </template>

                <p>
                    By {{ response.created_by }}
                    on {{ formatDate(response.created_at) }}
                </p>

                <hr>
            </section>

        </template>

        <p>
            <RouterLink :to="{ name: 'tickets.index' }">
                Back to Tickets
            </RouterLink>
        </p>
    </main>
</template>
