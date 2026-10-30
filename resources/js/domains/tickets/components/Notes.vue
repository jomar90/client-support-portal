<script setup lang="ts">
import { ref } from 'vue';
import { ticketStore } from '../store';
import type { TicketNote } from '../types';
import { getErrorByProperty } from '../../../services/error';

const props = defineProps<{ ticketId: number; notes: TicketNote[] }>();
const emit = defineEmits<{ updated: [] }>();
const body = ref('');
const editingId = ref<number | null>(null);
const editingBody = ref('');
const busy = ref(false);

// Notes and responses both send a field called "body" and share one error service.
// Each form keeps its own copy of the body errors, so they only show under the form that failed.
const bodyErrors = getErrorByProperty('body');
const formErrors = ref<string[]>([]);

function startEdit(note: TicketNote) {
    formErrors.value = [];
    editingId.value = note.id;
    editingBody.value = note.body;
}

function cancelEdit() {
    formErrors.value = [];
    editingId.value = null;
}

async function createNote() {
    busy.value = true;
    formErrors.value = [];
    try {
        await ticketStore.actions.createNote(props.ticketId, body.value);
        body.value = '';
        emit('updated');
    } catch {
        // The general message is displayed by the shared error service.
        formErrors.value = bodyErrors.value;
    } finally {
        busy.value = false;
    }
}

async function updateNote() {
    if (editingId.value === null) return;
    busy.value = true;
    formErrors.value = [];
    try {
        await ticketStore.actions.updateNote(props.ticketId, editingId.value, editingBody.value);
        editingId.value = null;
        emit('updated');
    } catch {
        // The general message is displayed by the shared error service.
        formErrors.value = bodyErrors.value;
    } finally {
        busy.value = false;
    }
}

async function deleteNote(note: TicketNote) {
    if (!window.confirm('Delete this internal note?')) return;
    busy.value = true;
    try {
        await ticketStore.actions.deleteNote(props.ticketId, note.id);
        if (editingId.value === note.id) editingId.value = null;
        emit('updated');
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <section>
        <h2>Internal notes</h2>
        <p>Only administrators can see these notes.</p>
        <p v-if="notes.length === 0">No notes yet.</p>
        <article v-for="note in notes" :key="note.id">
            <p><strong>{{ note.created_by }}</strong> — {{ new Date(note.created_at).toLocaleString() }}</p>
            <small v-if="note.updated_at !== note.created_at">Updated {{ new Date(note.updated_at).toLocaleString() }}</small>
            <form v-if="editingId === note.id" @submit.prevent="updateNote">
                <label :for="`note-${note.id}`">Edit note</label>
                <textarea :id="`note-${note.id}`" v-model="editingBody" rows="4" required></textarea>
                <p v-for="error in formErrors" :key="error" class="error">{{ error }}</p>
                <button :disabled="busy">Save note</button>
                <button type="button" :disabled="busy" @click="cancelEdit">Cancel</button>
            </form>
            <template v-else>
                <p class="multiline">{{ note.body }}</p>
                <button :disabled="busy" @click="startEdit(note)">Edit note</button>
                <button :disabled="busy" @click="deleteNote(note)">Delete note</button>
            </template>
        </article>
        <form v-if="editingId === null" @submit.prevent="createNote">
            <h3>Add internal note</h3>
            <label for="new-note">Note</label>
            <textarea id="new-note" v-model="body" rows="4" required></textarea>
            <p v-for="error in formErrors" :key="error" class="error">{{ error }}</p>
            <button :disabled="busy">Add note</button>
        </form>
    </section>
</template>
