<script setup>
import { usePage } from "@inertiajs/vue3";
import { ref, nextTick } from "vue";

import InputText from "primevue/inputtext";

defineProps({
    sending: Boolean,
});

const appName = import.meta.env.VITE_APP_NAME;

const emit = defineEmits(["isSubmitting"]);

const page = usePage();

const userMessage = ref("");
const input = ref();

const handleSubmit = () => {
    emit("isSubmitting", userMessage.value);

    userMessage.value = "";
};

const focusInput = () => {
    nextTick(() => {
        if (input.value && input.value.$el) {
            input.value.$el.focus();
        }
    });
};
defineExpose({
    focusInput,
});
</script>

<template>
    <div class="w-full text-center">
        <form @submit.prevent="handleSubmit">
            <InputText
                v-model="userMessage"
                :disabled="sending"
                ref="input"
                class="w-1/2 h-14 rounded-lg dark:text-white dark:bg-app-light max-xl:w-3/4"
                placeholder="Type your Message..."
            />
        </form>
    </div>
    <div class="my-2 text-center text-xs">
        {{ appName }} can make mistakes. Please contact
        <a class="underline" href="mailto:remmy@fh-aachen.de"
            >remmy@fh-aachen.de</a
        >
        for technical assistance.
    </div>
</template>
