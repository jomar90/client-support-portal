<script setup lang="ts">
import { ref } from 'vue';
import { ticketStore } from '../store';
import type { TicketResponse } from '../types';
import { getErrorByProperty } from '../../../services/error';

const props = defineProps<{
    ticketId: number;
    responses: TicketResponse[];
    isAdmin: boolean;
}>();
const emit = defineEmits<{
    updated: [];
}>();
const body = ref('');
const editingId = ref<number | null>(null);
const editingBody = ref('');
const busy = ref(false);

// Notes and responses both send a field called "body" and share one error service.
// Each form keeps its own copy of the body errors, so they only show under the form that failed.
const bodyErrors = getErrorByProperty('body');
const formErrors = ref<string[]>([]);

function startEdit(response: TicketResponse) {
    formErrors.value = [];
    editingId.value = response.id;
    editingBody.value = response.body;
}

function cancelEdit() {
    formErrors.value = [];
    editingId.value = null;
}

async function createResponse() {
    busy.value = true;
    formErrors.value = [];
    try {
        await ticketStore.actions.respond(props.ticketId, body.value);
        body.value = '';
        emit('updated');
    } catch {
        // The general message is displayed by the shared error service.
        formErrors.value = bodyErrors.value;
    } finally {
        busy.value = false;
    }
}

async function updateResponse() {
    if (editingId.value === null) {
        return;
    }
    busy.value = true;
    formErrors.value = [];
    try {
        await ticketStore.actions.updateResponse(props.ticketId, editingId.value, editingBody.value);
        editingId.value = null;
        emit('updated');
    } catch {
        // The general message is displayed by the shared error service.
        formErrors.value = bodyErrors.value;
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <section>
        <h2>Responses</h2>
        <p v-if="responses.length === 0">No responses yet.</p>
        <article v-for="response in responses" :key="response.id">
            <p><strong>{{ response.created_by }}</strong> — {{ new Date(response.created_at).toLocaleString() }}</p>
            <small v-if="response.updated_at !== response.created_at">Updated {{ new Date(response.updated_at).toLocaleString() }}</small>
            <form v-if="isAdmin && editingId === response.id" @submit.prevent="updateResponse">
                <label :for="`response-${response.id}`">Edit response</label>
                <textarea :id="`response-${response.id}`" v-model="editingBody" rows="4" required></textarea>
                <p v-for="error in formErrors" :key="error" class="error">{{ error }}</p>
                <button :disabled="busy">Save response</button>
                <button type="button" :disabled="busy" @click="cancelEdit">Cancel</button>
            </form>
            <template v-else>
                <p class="multiline">{{ response.body }}</p>
                <button v-if="isAdmin" :disabled="busy" @click="startEdit(response)">Edit response</button>
            </template>
        </article>
        <form v-if="isAdmin && editingId === null" @submit.prevent="createResponse">
            <h3>Add response</h3>
            <label for="new-response">Response</label>
            <textarea id="new-response" v-model="body" rows="4" required></textarea>
            <p v-for="error in formErrors" :key="error" class="error">{{ error }}</p>
            <button :disabled="busy">Send response and notify owner</button>
        </form>
    </section>
</template>
