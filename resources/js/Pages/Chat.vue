<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed, onBeforeMount, onMounted, onUnmounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import showdown from "showdown";
import DOMPurify from "dompurify";
import hljs from "highlight.js";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Main from "@/Layouts/Main.vue";
import Prompt from "@/Components/Prompt.vue";
import LoadingDots from "@/Components/LoadingDots.vue";
import UserAvatar from "@/Components/UserAvatar.vue";
import MessageInspector from "@/Components/MessageInspector.vue";

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
const forbidTags = ["img"];

const copiedMessages = ref([]);
const isSendingRequest = ref(false);
const promptComponent = ref();
const mainComponent = ref();
const scrollContainer = ref();
const messages = ref([]);
const inspectedMessage = ref(null);

const timeoutId = ref();

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

    if (page.props.info) {
        timeoutId.value = setTimeout(() => {
            toast.add({
                severity: "info",
                summary: "Info",
                detail: page.props.info,
                life: 5000,
            });
        }, 500);
    }

    scroll();
});

onUnmounted(() => {
    if (timeoutId.value) {
        clearTimeout(timeoutId.value);
    }
});

const handleMessageSubmission = (userMessage) => {
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

            page.props.messages.push({
                agent_message: agent_message,
                conversation_id: page.props.conversation_id,
                created_at: created_at,
                id: id,
                updated_at: updated_at,
                user_message: userMessage,
            });
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

const decodeHtml = (str) => {
    const textarea = document.createElement("textarea");
    textarea.innerHTML = str;
    return textarea.value;
};

// Decode all HTML entities inside a code tag
const decodeHtmlEntitiesInCodeBlocks = (htmlString) => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlString, "text/html");

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

const updateRating = (id, helpful) => {
    if (!page.props.hasPrompt) return;

    // We update the 'helpful' status of a message locally for immediate user feedback,
    // before the server response confirms the update for better user experience by
    // avoiding the network delay. We also save the current value in case the
    // request fails.
    const message = messages.value.find((message) => message.id === id);

    if (message.helpful === helpful) return;

    const tmp = message.helpful;
    message.helpful = helpful;

    window.axios
        .patch("/chat/rating", {
            helpful: helpful,
            message_id: id,
        })
        .then((result) => {
            // ...
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });

            message.helpful = tmp;
        });
};

const copyMessage = (id) => {
    if (copiedMessages.value.includes(id)) return;

    // Accessing the messages property is necessary since the markdown
    // content in our messages ref variable is already parsed.
    const message = page.props.messages.find((message) => message.id === id);

    navigator.clipboard
        .writeText(decodeHtml(message.agent_message))
        .then(() => {
            copiedMessages.value.push(id);

            setTimeout(() => {
                const index = copiedMessages.value.findIndex(
                    (messageId) => messageId === id,
                );
                if (index !== -1) {
                    copiedMessages.value.splice(index, 1);
                }
            }, 3000);
        });
};

const inspectMessage = (id) => {
    inspectedMessage.value = messages.value.find(
        (message) => message.id === id,
    );
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
                ref="scrollContainer"
                class="scroll-container mb-6 flex w-full flex-1 justify-center overflow-y-auto px-4"
            >
                <div class="w-full max-w-[48rem]">
                    <div v-for="(message, index) in messages">
                        <div class="mt-6 flex gap-3">
                            <div>
                                <UserAvatar :label="userAvatarLabel" />
                            </div>
                            <div class="flex w-full min-w-0 flex-col">
                                <div class="font-bold">{{ displayName }}</div>
                                <div class="whitespace-pre-wrap break-words">
                                    {{ message.user_message }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex gap-3">
                            <div>
                                <Avatar
                                    image="/static/img/app-logo.png"
                                    shape="circle"
                                />
                            </div>
                            <div class="flex w-full min-w-0 flex-col">
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
                                <div
                                    v-if="
                                        message.agent_message &&
                                        $page.props.showOptions
                                    "
                                    class="mt-2 flex gap-4"
                                >
                                    <button
                                        @click="copyMessage(message.id)"
                                        v-tooltip.bottom="'Copy'"
                                        :class="
                                            copiedMessages.includes(message.id)
                                                ? 'pi pi-check'
                                                : 'pi pi-copy'
                                        "
                                    />
                                    <button
                                        @click="updateRating(message.id, true)"
                                        v-tooltip.bottom="'Good Response'"
                                        :class="
                                            message.helpful
                                                ? 'pi pi-thumbs-up-fill'
                                                : 'pi pi-thumbs-up'
                                        "
                                    />
                                    <button
                                        @click="updateRating(message.id, false)"
                                        v-tooltip.bottom="'Bad Response'"
                                        :class="
                                            message.helpful === false
                                                ? 'pi pi-thumbs-down-fill'
                                                : 'pi pi-thumbs-down'
                                        "
                                    />
                                    <button
                                        v-if="$page.props.username"
                                        @click="inspectMessage(message.id)"
                                        v-tooltip.bottom="'Inspect'"
                                        class="pi pi-info-circle"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Prompt
                v-if="$page.props.hasPrompt"
                :sending="isSendingRequest"
                @is-submitting="handleMessageSubmission"
                ref="promptComponent"
            />
        </Main>
    </AuthenticatedLayout>

    <MessageInspector
        v-if="$page.props.username"
        :message="inspectedMessage"
        @close="inspectedMessage = null"
    />
</template>
