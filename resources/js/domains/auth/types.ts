export interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    admin: boolean;
}
export interface ResetPasswordData {
    email: string;
    token: string;
    password: string;
    password_confirmation: string;
}
export interface RegistrationData {
    first_name: string;
    last_name: string;
    email: string;
    phone_number: string;
    password: string;
    password_confirmation: string;
}
