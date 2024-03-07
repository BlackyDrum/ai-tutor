<script setup>
import { router, Link, usePage } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";
import { useToast } from "primevue/usetoast";

import OverlayPanel from "primevue/overlaypanel";
import InputText from "primevue/inputtext";

const page = usePage();
const toast = useToast();

const conversationOverlayPanel = ref();
const selectedConversation = ref(null);
const selectedConversationName = ref(null);
const isDeletingConversation = ref(false);
const isRenamingConversation = ref(false);
const renameInput = ref();
const showRenameInput = ref(false);

const toggleConversationOverlayPanel = (event, conversation) => {
    conversationOverlayPanel.value.toggle(event);

    selectedConversation.value = conversationOverlayPanel.value.visible ? conversation : null;

    showRenameInput.value = false;
};

const deleteConversation = () => {
    if (isDeletingConversation.value || isRenamingConversation.value) return;

    isDeletingConversation.value = true;

    window.axios
        .delete("/chat/conversation", {
            data: {
                conversation_id: selectedConversation.value,
            },
        })
        .then((result) => {
            page.props.auth.history.splice(
                page.props.auth.history.findIndex(
                    (conversation) => conversation.api_id === result.data.id,
                ),
                1,
            );

            if (
                typeof page.props.conversation_id !== "undefined" &&
                result.data.id === page.props.conversation_id
            ) {
                router.get("/");
            }
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            selectedConversation.value = null;

            conversationOverlayPanel.value.visible = false;

            isDeletingConversation.value = false;
        });
};

const handleRenameConversation = () => {
    if (isRenamingConversation.value) return;

    showRenameInput.value = true;

    conversationOverlayPanel.value.visible = false;

    selectedConversationName.value =
        page.props.auth.history[
            page.props.auth.history.findIndex(
                (conversation) =>
                    conversation.api_id === selectedConversation.value,
            )
        ].name;

    nextTick(() => {
        renameInput.value[0].$el.focus();
    });
};

const renameConversation = () => {
    if (isRenamingConversation.value || isDeletingConversation.value) return;

    isRenamingConversation.value = true;

    window.axios
        .patch("/chat/conversation/name", {
            name: selectedConversationName.value,
            conversation_id: selectedConversation.value,
        })
        .then((result) => {
            const index = page.props.auth.history.findIndex(
                (conversation) => conversation.api_id === result.data.id,
            );
            page.props.auth.history[index].name = result.data.name;

            // Move the renamed conversation to the top
            page.props.auth.history.unshift(
                page.props.auth.history.splice(index, 1)[0],
            );
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            selectedConversation.value = null;

            showRenameInput.value = false;

            isRenamingConversation.value = false;
        });
};
</script>

<template>
    <div
        v-for="(conversation, index) in $page.props.auth.history"
        :key="conversation.api_id"
        class="my-2"
    >
        <div
            v-if="
                !showRenameInput || selectedConversation !== conversation.api_id
            "
            class="relative flex group rounded-lg hover:bg-app-dark"
            :class="{
                'bg-app-dark':
                    conversation.api_id ===
                    $page.url.slice($page.url.lastIndexOf('/') + 1),
            }"
        >
            <Link
                :href="`/chat/${conversation.api_id}`"
                class="block flex-1 my-1 px-2 py-1 whitespace-nowrap truncate rounded-lg cursor-pointer"
            >
                {{ conversation.name }}
            </Link>
            <button
                @click="toggleConversationOverlayPanel($event, conversation.api_id)"
                class="block absolute right-2 top-1 p-1 pl-2 rounded-lg hidden bg-app-dark group-hover:block"
            >
                <span class="pi pi-ellipsis-h"></span>
            </button>
        </div>

        <div v-else class="block flex-1 my-1 py-2 rounded-lg">
            <InputText
                v-model="selectedConversationName"
                @keydown.enter="renameConversation"
                ref="renameInput"
                class="w-full rounded-lg text-white bg-black max-xl:w-3/4"
            />
        </div>
    </div>

    <!-- Conversations Overlay Panel -->
    <OverlayPanel
        ref="conversationOverlayPanel"
        class="font-semibold bg-app-dark border-none z-50"
    >
        <div
            @click="handleRenameConversation"
            class="flex gap-4 p-2 text-sm text-white cursor-pointer rounded-lg hover:bg-gray-700/20"
        >
            <div>
                <span class="pi pi-pencil"></span>
            </div>
            <div class="block">Rename</div>
        </div>
        <div
            @click="deleteConversation"
            class="flex gap-4 p-2 text-sm text-red-600 cursor-pointer rounded-lg hover:bg-gray-700/20"
        >
            <div>
                <span
                    :class="
                        isDeletingConversation
                            ? 'pi pi-spin pi-spinner'
                            : 'pi pi-trash'
                    "
                ></span>
            </div>
            <div class="block">Delete chat</div>
        </div>
    </OverlayPanel>
</template>

<style>
.p-overlaypanel:after,
.p-overlaypanel:before {
    display: none;
}
</style>
