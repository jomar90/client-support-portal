import { computed, ref } from 'vue';

interface ErrorBag {
    [field: string]: string[];
}

const errors = ref<ErrorBag>({});
const message = ref('');

export const getMessage = computed(() => message.value);

export function getErrorByProperty(field: string) {
    return computed(() => {
        const messages: string[] = [];

        for (const name in errors.value) {
            // category_ids.0 is an error for the first selected category.
            if (name === field || name.startsWith(field + '.')) {
                for (const error of errors.value[name]) {
                    messages.push(error);
                }
            }
        }

        return messages;
    });
}

export function clearErrors() {
    errors.value = {};
    message.value = '';
}

export function setErrors(newMessage: string, bag: ErrorBag = {}) {
    message.value = newMessage;
    errors.value = bag;
}
