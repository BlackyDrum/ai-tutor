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
    if (userMessage.value.trim().length === 0) return;

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
        <form class="relative mx-auto w-full max-w-[48rem] max-lg:max-w-[95%]">
            <Textarea
                v-model="userMessage"
                :disabled="sending"
                @keydown.enter="handleSubmitEnter"
                @input="handleInput"
                ref="input"
                rows="1"
                class="w-full resize-none overflow-y-auto rounded-lg py-4 pr-12 lg:pr-20 dark:bg-app-light dark:text-white"
                placeholder="Type your Message..."
            />
            <Button
                type="button"
                @click="handleSubmitButton"
                :disabled="sending || userMessage.trim().length === 0"
                class="absolute bottom-5 right-4 rounded-lg border border-black bg-white p-0.5 text-black disabled:opacity-10 dark:border-white"
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
    <div class="p-1 pb-2 text-center text-xs">
        <strong
            >{{
                $page.component === "Home"
                    ? $page.props.auth.user.context_title
                    : $page.props.current_module
            }}
        </strong>
        <span v-if="$page.props.data_from">
            - Data updated at
            {{ new Date($page.props.data_from).toDateString() }}</span
        >
        <span v-else-if="$page.component !== 'Home'">
            - Date not available</span
        >
    </div>
</template>
