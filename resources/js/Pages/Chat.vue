<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import showdown from "showdown";
import DOMPurify from "dompurify";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Main from "@/Layouts/Main.vue";
import Prompt from "@/Components/Prompt.vue";
import LoadingDots from "@/Components/LoadingDots.vue";
import UserAvatar from "@/Components/UserAvatar.vue";

import Message from "primevue/message";
import Avatar from "primevue/avatar";

defineProps({
    messages: Array,
    conversation_id: String,
});

const appName = import.meta.env.VITE_APP_NAME;

const page = usePage();
const toast = useToast();

let converter = new showdown.Converter();

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

// Decode all HTML entities inside a code tag
const decodeHtmlEntitiesInCodeBlocks = (htmlString) => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlString, "text/html");

    const decodeHtml = (str) => {
        const textarea = document.createElement("textarea");
        textarea.innerHTML = str;
        return textarea.value;
    };

    const codeElements = doc.querySelectorAll("code");
    codeElements.forEach((code) => {
        Array.from(code.childNodes).forEach((node) => {
            if (node.nodeType === Node.TEXT_NODE) {
                node.nodeValue = decodeHtml(node.nodeValue);
            }
        });
    });

    const serializer = new XMLSerializer();
    const serialized = serializer.serializeToString(doc);
    return serialized.substring(
        serialized.indexOf("<body>") + 6,
        serialized.indexOf("</body>"),
    );
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
                    <div class="flex gap-3 mt-6">
                        <div class="max-md:hidden">
                            <UserAvatar />
                        </div>
                        <div class="flex flex-col">
                            <div class="font-bold">You</div>
                            <div>
                                {{ message.user_message }}
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <div class="max-md:hidden">
                            <Avatar
                                image="/static/img/app-logo.png"
                                shape="circle"
                            />
                        </div>
                        <div class="flex flex-col w-full">
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
                            <div
                                class="prose dark:prose-invert"
                                v-if="typeof message.error === 'undefined'"
                                v-html="
                                    decodeHtmlEntitiesInCodeBlocks(
                                        DOMPurify.sanitize(
                                            converter.makeHtml(
                                                message.agent_message,
                                            ),
                                        ),
                                    )
                                "
                            ></div>
                            <div v-else>
                                <Message severity="error" :closable="false">{{
                                    message.error
                                }}</Message>
                            </div>
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
