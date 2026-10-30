export interface ManagedUser {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone_number: string | null;
    admin: boolean;
}

export interface UserFormData {
    first_name: string;
    last_name: string;
    email: string;
    phone_number: string | null;
}
