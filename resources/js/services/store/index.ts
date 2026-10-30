import { computed, shallowRef } from 'vue';
import { getRequest } from '../http';

export const storeModuleFactory = <T>(moduleName: string) => {
    const state = shallowRef<T[]>([]);

    const getters = {
        all: computed(() => state.value),
    };

    const setters = {
        setAll: (items: T[]) => {
            state.value = items;
        },
    };

    const actions = {
        getAll: async () => {
            const { data } = await getRequest<T[]>(`/api/${moduleName}`);
            setters.setAll(data);
        },
    };

    return { getters, setters, actions };
};
