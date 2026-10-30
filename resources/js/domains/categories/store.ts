import { storeModuleFactory } from '../../services/store';
import { deleteRequest, postRequest } from '../../services/http';
import type { Category, CategoryFormData } from './types';

const collection = storeModuleFactory<Category>('categories');
export const categoryStore = {
    getters: collection.getters,
    setters: collection.setters,
    actions: {
        getAll: collection.actions.getAll,
        async create(form: CategoryFormData) {
            await postRequest('/api/categories', form);
        },
        async delete(id: number) {
            await deleteRequest(`/api/categories/${id}`);
            const remainingCategories = collection.getters.all.value.filter(category => category.id !== id);
            collection.setters.setAll(remainingCategories);
        },
    },
};
