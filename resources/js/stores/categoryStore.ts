
import http from '../services/http';
import { storeModuleFactory } from './storeModuleFactory';

export interface Category {
    id: number;
    title: string;
}

interface CategoryState {
    categories: Category[];
}

const { state, actions } = storeModuleFactory<
    CategoryState,
    {
        fetchCategories: () => Promise<void>;
        createCategory: (title: string) => Promise<Category>;
        deleteCategory: (id: number) => Promise<void>;
    }
>(
    {
        categories: [],
    },
    (state) => {
        const fetchCategories = async (): Promise<void> => {
            const response = await http.get<Category[]>(
                '/api/categories',
            );

            state.categories = response.data;
        };

        const createCategory = async (
            title: string,
        ): Promise<Category> => {
            const response = await http.post<Category>(
                '/api/categories',
                { title },
            );

            return response.data;
        };

        const deleteCategory = async (
            id: number,
        ): Promise<void> => {
            await http.delete(`/api/categories/${id}`);
        };

        return {
            fetchCategories,
            createCategory,
            deleteCategory,
        };
    },
);

export const categoryStore = {
    state,
    actions,
};
