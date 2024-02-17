<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Main from "@/Layouts/Main.vue";
import Prompt from "@/Components/Prompt.vue";
import LoadingDots from "@/Components/LoadingDots.vue";

import Message from "primevue/message";

defineProps({
    messages: Array,
    conversation_id: String,
});

const appName = import.meta.env.VITE_APP_NAME;

const page = usePage();
const toast = useToast();

const isSendingRequest = ref(false);
const promptComponent = ref();

onMounted(() => {
    scroll();

    promptComponent.value.focusInput();
});

const handleCreateConversation = (userMessage) => {
    if (userMessage.length === 0 || isSendingRequest.value) return;

    isSendingRequest.value = true;

    // We need to use a promise here, because we have to wait for the messages
    // prop to fully re-render, in order to scroll to the bottom.
    // Another option would be to use a timeout.
    new Promise((resolve, reject) => {
        page.props.messages.push({
            agent_message: "",
            conversation_id: page.props.conversation_id,
            created_at: null,
            id: null,
            updated_at: null,
            user_message: userMessage,
        });
        resolve();
    }).then(() => {
        scroll();
    });

    window.axios
        .post("/chat/chat-agent", {
            message: userMessage,
            conversation_id: page.props.conversation_id,
        })
        .then((result) => {
            const lastMessage =
                page.props.messages[page.props.messages.length - 1];
            const { agent_message, created_at, id, updated_at } = result.data;

            Object.assign(lastMessage, {
                agent_message,
                created_at,
                id,
                updated_at,
            });
        })
        .then(() => {
            scroll();
        })
        .catch((error) => {
            page.props.messages[page.props.messages.length - 1].error =
                error.response.data.message || error.response.data;
        })
        .finally(() => {
            isSendingRequest.value = false;

            promptComponent.value.focusInput();

            scroll();
        });
};

const scroll = () => {
    document
        .getElementById("scroll-container")
        .scrollTo(0, document.getElementById("scroll-container").scrollHeight);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Chat" />

        <Main>
            <div
                id="scroll-container"
                class="w-full flex-1 pb-8 px-4 overflow-y-auto max-w-[48rem] max-xl:max-w-[40rem] max-lg:max-w-[35rem] max-md:max-w-[25rem] max-md:max-w-[20rem]"
            >
                <div v-for="(message, index) in messages" :key="message.id">
                    <div class="flex flex-col mt-6">
                        <div class="font-bold">You</div>
                        <div>
                            {{ message.user_message }}
                        </div>
                    </div>
                    <div class="flex flex-col mt-6">
                        <div class="font-bold">
                            {{ appName }}
                        </div>
                        <div
                            v-if="
                                isSendingRequest &&
                                index === messages.length - 1
                            "
                        >
                            <LoadingDots />
                        </div>
                        <div v-if="typeof message.error === 'undefined'">
                            {{ message.agent_message }}
                        </div>
                        <div v-else>
                            <Message severity="error" :closable="false">{{
                                message.error
                            }}</Message>
                        </div>
                    </div>
                </div>
            </div>
            <Prompt
                :sending="isSendingRequest"
                @isSubmitting="handleCreateConversation"
                ref="promptComponent"
            />
        </Main>
    </AuthenticatedLayout>
</template>
