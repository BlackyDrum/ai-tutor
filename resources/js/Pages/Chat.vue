<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed, onBeforeMount, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import showdown from "showdown";
import DOMPurify from "dompurify";
import hljs from "highlight.js";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Main from "@/Layouts/Main.vue";
import Prompt from "@/Components/Prompt.vue";
import LoadingDots from "@/Components/LoadingDots.vue";
import UserAvatar from "@/Components/UserAvatar.vue";

import Message from "primevue/message";
import Avatar from "primevue/avatar";

defineProps({
    conversation_id: String,
    conversation_name: String,
});

const appName = import.meta.env.VITE_APP_NAME;

const page = usePage();
const toast = useToast();

let converter = new showdown.Converter();
const forbidTags = ["a", "img"];

const isSendingRequest = ref(false);
const promptComponent = ref();
const mainComponent = ref();
const scrollContainer = ref();
const messages = ref([]);

onBeforeMount(() => {
    messages.value = JSON.parse(JSON.stringify(page.props.messages));

    messages.value.map((message) => {
        message.agent_message = processAgentMessage(message.agent_message);
    });
});

onMounted(() => {
    if (page.props.hasPrompt) {
        promptComponent.value.focusInput();
    }

    scroll();
});

const handleCreateConversation = (userMessage) => {
    if (
        userMessage.length === 0 ||
        isSendingRequest.value ||
        !page.props.hasPrompt
    )
        return;

    isSendingRequest.value = true;

    // We need to use a promise here, because we have to wait for the messages
    // prop to fully re-render, in order to scroll to the bottom.
    // Another option would be to use a timeout.
    new Promise((resolve, reject) => {
        messages.value.push({
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
            const lastMessage = messages.value[messages.value.length - 1];

            const { agent_message, created_at, id, updated_at } = result.data;

            Object.assign(lastMessage, {
                created_at,
                id,
                updated_at,
            });

            lastMessage.agent_message = processAgentMessage(agent_message);

            if (typeof result.data.info !== "undefined") {
                toast.add({
                    severity: "info",
                    summary: "Info",
                    detail: result.data.info,
                    life: 5000,
                });
            }
        })
        .then(() => {
            scroll();
        })
        .catch((error) => {
            messages.value[messages.value.length - 1].error =
                error.response.data.message ?? error.response.data;
        })
        .finally(() => {
            isSendingRequest.value = false;

            promptComponent.value.focusInput();

            scroll();
        });
};

const scroll = () => {
    scrollContainer.value.scrollTo(0, scrollContainer.value.scrollHeight);
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

const processAgentMessage = (message) => {
    const element = document.createElement("div");

    element.innerHTML = decodeHtmlEntitiesInCodeBlocks(
        DOMPurify.sanitize(converter.makeHtml(message), {
            FORBID_TAGS: forbidTags,
        }),
    );

    element.querySelectorAll("pre code").forEach((block) => {
        hljs.highlightElement(block);
    });

    return element.innerHTML;
};

const userAvatarLabel = computed(() => {
    if (page.props.hasPrompt) return undefined;
    else if (page.props.username) return page.props.username[0].toUpperCase();
    else return "A";
});

const displayName = computed(() => {
    if (page.props.hasPrompt) return "You";
    else if (page.props.username) return page.props.username;
    else return "Anonymous";
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="page.props.conversation_name" />

        <Main ref="mainComponent">
            <div
                id="scroll-container"
                ref="scrollContainer"
                class="w-full flex flex-1 justify-center mb-6 px-4 overflow-y-auto"
            >
                <div class="w-full max-w-[48rem]">
                    <div v-for="(message, index) in messages">
                        <div class="flex gap-3 mt-6">
                            <div>
                                <UserAvatar :label="userAvatarLabel" />
                            </div>
                            <div class="flex flex-col min-w-0 w-full">
                                <div class="font-bold">{{ displayName }}</div>
                                <div class="break-words whitespace-pre-wrap">
                                    {{ message.user_message }}
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <div>
                                <Avatar
                                    image="/static/img/app-logo.png"
                                    shape="circle"
                                />
                            </div>
                            <div class="flex flex-col min-w-0 w-full">
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
                                    class="prose break-words dark:prose-invert"
                                    v-if="typeof message.error === 'undefined'"
                                    v-html="message.agent_message"
                                ></div>
                                <div v-else>
                                    <Message
                                        severity="error"
                                        :closable="false"
                                        >{{ message.error }}</Message
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Prompt
                v-if="$page.props.hasPrompt"
                :sending="isSendingRequest"
                @is-submitting="handleCreateConversation"
                ref="promptComponent"
            />
        </Main>
    </AuthenticatedLayout>
</template>
