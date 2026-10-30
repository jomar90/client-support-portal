import { storeModuleFactory } from '../../services/store';
import { getRequest, postRequest, patchRequest, deleteRequest } from '../../services/http';
import type { Ticket, TicketDetail, TicketFormData, TicketUpdateData } from './types';

const collection = storeModuleFactory<Ticket>('tickets');
export const ticketStore = {
    getters: collection.getters,
    setters: collection.setters,
    actions: {
        getAll: collection.actions.getAll,
        async getById(id: number) {
            const { data } = await getRequest<TicketDetail>(`/api/tickets/${id}`);
            return data;
        },
        async create(form: TicketFormData) {
            const { data } = await postRequest<{ id: number }>('/api/tickets', form);
            return data.id;
        },
        async update(id: number, form: TicketUpdateData) {
            await patchRequest(`/api/tickets/${id}`, form);
        },
        async respond(id: number, body: string) {
            await postRequest(`/api/tickets/${id}/responses`, { body });
        },
        async updateResponse(id: number, responseId: number, body: string) {
            await patchRequest(`/api/tickets/${id}/responses/${responseId}`, { body });
        },
        async createNote(id: number, body: string) {
            await postRequest(`/api/tickets/${id}/notes`, { body });
        },
        async updateNote(id: number, noteId: number, body: string) {
            await patchRequest(`/api/tickets/${id}/notes/${noteId}`, { body });
        },
        async deleteNote(id: number, noteId: number) {
            await deleteRequest(`/api/tickets/${id}/notes/${noteId}`);
        },
    },
};
