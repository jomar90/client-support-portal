import { storeModuleFactory } from './storeModuleFactory';
import http from '../services/http';

export interface TicketListItem {
    id: number;
    title: string;
    categories: string[];
    status: string;
    created_by: string;
    created_at: string;
    updated_at: string;
    assigned_to: string | null;
}

export interface CreateTicketData {
    title: string;
    description: string;
    category_ids?: number[];
}

export interface TicketCategory {
    id: number;
    title: string;
}

export interface EditableTicket {
    id: number;
    title: string;
    description: string;
}

export interface TicketResponse {
    id: number;
    body: string;
    created_by: string;
    created_at: string;
    updated_at: string;
}

export interface TicketDetail extends EditableTicket {
    status: TicketStatus;
    categories: string[];
    created_by: string;
    assigned_to: string | null;
    created_at: string;
    updated_at: string;
    responses: TicketResponse[];
}

export interface TicketAdministrator {
    id: number;
    first_name: string;
    last_name: string;
}

interface TicketState {
    tickets: TicketListItem[];
    loading: boolean;
    error: string | null;
}

export type TicketStatus =
    | 'pending'
    | 'in_progress'
    | 'resolved';

const { state, actions } = storeModuleFactory<
    TicketState,
    {
        fetchTickets: () => Promise<void>;
        createTicket: (data: CreateTicketData) => Promise<number>;
        getTicket: (id: number) => Promise<TicketDetail>;
        updateTicket: (id: number, data: CreateTicketData) => Promise<void>;
        getAdministrators: () => Promise<TicketAdministrator[]>;
        assignTicket: (ticketId: number, administratorId: number) => Promise<void>;
        updateTicketStatus: (
            ticketId: number,
            status: TicketStatus
        ) => Promise<void>;
        addResponse: (
            ticketId: number,
            body: string
        ) => Promise<void>;
        updateResponse: (
            ticketId: number,
            responseId: number,
            body: string
        ) => Promise<void>;
        getSelectableCategories: () => Promise<TicketCategory[]>;
    }
>(
    {
        tickets: [],
        loading: false,
        error: null,
    },
    (state) => {
        const fetchTickets = async (): Promise<void> => {
            state.loading = true;
            state.error = null;

            try {
                const response = await http.get<TicketListItem[]>(
                    '/api/tickets'
                );

                state.tickets = response.data;
            } catch {
                state.error = 'Unable to load tickets.';
                state.tickets = [];
            } finally {
                state.loading = false;
            }
        };
        const createTicket = async (
            data: CreateTicketData
        ): Promise<number> => {
            const response = await http.post<{
                id: number;
                message: string;
            }>('/api/tickets', data);

            return response.data.id;
        };

        const getSelectableCategories = async (): Promise<TicketCategory[]> => {
            const response = await http.get<TicketCategory[]>(
                '/api/ticket-categories'
            );

            return response.data;
        };

        const getTicket = async (id: number): Promise<TicketDetail> => {
            const response = await http.get<TicketDetail>(
                `/api/tickets/${id}`
            );

            return response.data;
        };

        const updateTicket = async (
            id: number,
            data: CreateTicketData
        ): Promise<void> => {
            await http.patch(`/api/tickets/${id}`, data);
        };

        const updateTicketStatus = async (
            ticketId: number,
            status: TicketStatus
        ): Promise<void> => {
            await http.patch(`/api/tickets/${ticketId}/status`, {
                status,
            });
        };

        const addResponse = async (
            ticketId: number,
            body: string
        ): Promise<void> => {
            await http.post(
                `/api/tickets/${ticketId}/responses`,
                { body }
            );
        };

        const updateResponse = async (
            ticketId: number,
            responseId: number,
            body: string
        ): Promise<void> => {
            await http.patch(
                `/api/tickets/${ticketId}/responses/${responseId}`,
                { body }
            );
        };

        const getAdministrators = async (): Promise<TicketAdministrator[]> => {
            const response = await http.get<TicketAdministrator[]>(
                '/api/administrators'
            );

            return response.data;
        };

        const assignTicket = async (
            ticketId: number,
            administratorId: number
        ): Promise<void> => {
            await http.patch(`/api/tickets/${ticketId}/assignment`, {
                assigned_to: administratorId,
            });
        };

        return {
            fetchTickets,
            createTicket,
            getSelectableCategories,
            getTicket,
            updateTicket,
            getAdministrators,
            assignTicket,
            updateTicketStatus,
            addResponse,
            updateResponse,
        };
    }
);

export const ticketStore = {
    state,
    actions,
};
