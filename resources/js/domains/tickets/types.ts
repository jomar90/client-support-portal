export type TicketStatus = 'pending' | 'in_progress' | 'resolved';
export interface Ticket {
    id: number;
    title: string;
    categories: string[];
    status: TicketStatus;
    created_by: string;
    created_at: string;
    updated_at: string;
    assigned_to: string | null;
}
export interface TicketResponse {
    id: number;
    body: string;
    created_by: string;
    created_at: string;
    updated_at: string;
}
export interface TicketDetail extends Ticket {
    description: string;
    category_ids: number[];
    assigned_to_id: number | null;
    responses: TicketResponse[];
    notes?: TicketNote[];
}
export interface TicketNote {
    id: number;
    body: string;
    created_by: string;
    created_at: string;
    updated_at: string;
}
export interface TicketFormData {
    title: string;
    description: string;
    category_ids: number[];
}

// PATCH may send only the fields being changed.
export interface TicketUpdateData {
    title?: string;
    description?: string;
    category_ids?: number[];
    status?: TicketStatus;
    assigned_to?: number;
}
