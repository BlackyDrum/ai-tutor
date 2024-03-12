<script setup>
import { usePage } from "@inertiajs/vue3";
import { ref, nextTick } from "vue";

import Button from "primevue/button";
import Textarea from "primevue/textarea";

defineProps({
    sending: Boolean,
});

const appName = import.meta.env.VITE_APP_NAME;

const emit = defineEmits(["is-submitting"]);

const page = usePage();

const userMessage = ref("");
const input = ref();

const handleSubmitEnter = (event) => {
    if (event.shiftKey || window.mobileCheck()) {
        return;
    }

    handleSubmit();
};

const handleSubmitButton = () => {
    handleSubmit();
};

const handleSubmit = () => {
    emit("is-submitting", userMessage.value);

    userMessage.value = "";

    const textareaElement = input.value.$el;
    textareaElement.style.height = "auto";
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

const handleInput = () => {
    nextTick(() => {
        const textareaElement = input.value.$el;
        if (!textareaElement) return;

        textareaElement.style.height = "auto";

        const maxHeight = 200; // Maximum height in pixels
        textareaElement.style.height = `${Math.min(textareaElement.scrollHeight + 2, maxHeight)}px`;
    });
};
</script>

<template>
    <div class="w-full text-center">
        <form class="relative w-full max-w-[48rem] max-xl:max-w-[40rem] max-lg:max-w-[95%] mx-auto">
            <Textarea
                v-model="userMessage"
                :disabled="sending"
                @keydown.enter="handleSubmitEnter"
                @input="handleInput"
                ref="input"
                rows="1"
                class="w-full lg:pr-20 pr-12 py-4 rounded-lg resize-none overflow-y-auto dark:text-white dark:bg-app-light"
                placeholder="Type your Message..."
            />
            <Button
                type="button"
                @click="handleSubmitButton"
                :disabled="sending"
                class="absolute bottom-5 right-4 rounded-lg text-black border border-black bg-white p-0.5 disabled:opacity-10 dark:border-white"
                data-testid="send-button"
            >
                <span class="" data-state="closed"
                    ><svg
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <path
                            d="M7 11L12 6L17 11M12 18V7"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        ></path></svg
                ></span>
            </Button>
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
