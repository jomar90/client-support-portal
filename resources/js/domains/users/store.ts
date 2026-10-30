import { storeModuleFactory } from '../../services/store';
import { getRequest, patchRequest, deleteRequest } from '../../services/http';
import type { ManagedUser, UserFormData } from './types';

const collection = storeModuleFactory<ManagedUser>('users');
export const userStore = {
    getters: collection.getters,
    setters: collection.setters,
    actions: {
        getAll: collection.actions.getAll,
        async getById(id: number) {
            const { data } = await getRequest<ManagedUser>(`/api/users/${id}`);
            return data;
        },
        async update(id: number, form: UserFormData) {
            await patchRequest(`/api/users/${id}`, form);
        },
        async delete(id: number) {
            await deleteRequest(`/api/users/${id}`);
            collection.setters.setAll(collection.getters.all.value.filter(user => user.id !== id));
        },
    },
};
