<script setup>
import { router, Link, usePage } from "@inertiajs/vue3";
import { computed, nextTick, ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

import OverlayPanel from "primevue/overlaypanel";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";
import Button from "primevue/button";

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

const conversationOverlayPanel = ref();
const selectedConversation = ref(null);
const isDeletingConversation = ref(false);
const isRenamingConversation = ref(false);
const isSharingConversation = ref(false);
const isDeletingSharedConversation = ref(false);
const renameInput = ref();
const showRenameInput = ref(false);
const showConversationShareDialog = ref(false);

const toggleConversationOverlayPanel = (event, conversation) => {
    if (isSendingRequest.value) return;

    conversationOverlayPanel.value.toggle(event);

    selectedConversation.value = conversationOverlayPanel.value.visible
        ? JSON.parse(JSON.stringify(conversation))
        : null;

    showRenameInput.value = false;
};

const handleConversationOverlayPanelHiding = () => {
    if (
        selectedConversation.value &&
        (showRenameInput.value ||
            showConversationShareDialog.value ||
            isDeletingConversation.value)
    )
        return;

    selectedConversation.value = null;
};

const deleteConversation = () => {
    if (isSendingRequest.value) return;

    isDeletingConversation.value = true;

    conversationOverlayPanel.value.visible = false;

    confirm.require({
        message: `This will delete ${selectedConversation.value.name}`,
        header: "Delete conversation?",
        icon: "pi pi-info-circle",
        rejectLabel: "Cancel",
        acceptLabel: "Delete",
        rejectClass: "p-button-secondary p-button-outlined",
        acceptClass: "p-button-danger",
        acceptIcon: "pi pi-trash",
        accept: () => {
            window.axios
                .delete("/conversation", {
                    data: {
                        conversation_id: selectedConversation.value.url_id,
                    },
                })
                .then((result) => {
                    page.props.auth.history.splice(
                        page.props.auth.history.findIndex(
                            (conversation) =>
                                conversation.url_id === result.data.id,
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
                        detail:
                            error.response.data.message ?? error.response.data,
                        life: 5000,
                    });
                })
                .finally(() => {
                    selectedConversation.value = null;

                    conversationOverlayPanel.value.visible = false;

                    isDeletingConversation.value = false;
                });
        },
        reject: () => {
            selectedConversation.value = null;

            isDeletingConversation.value = false;
        },
        onHide: () => {
            selectedConversation.value = null;

            isDeletingConversation.value = false;
        },
    });
};

const handleRenameConversation = () => {
    if (isSendingRequest.value) return;

    showRenameInput.value = true;

    nextTick(() => {
        renameInput.value[0].$el.focus();
    });

    conversationOverlayPanel.value.visible = false;
};

const renameConversation = () => {
    if (isSendingRequest.value) return;

    isRenamingConversation.value = true;

    window.axios
        .patch("/conversation/name", {
            name: selectedConversation.value.name,
            conversation_id: selectedConversation.value.url_id,
        })
        .then((result) => {
            const index = page.props.auth.history.findIndex(
                (conversation) => conversation.url_id === result.data.id,
            );

            // Also change browsers title if current conversation was renamed
            if (
                page.props.auth.history[index].url_id ===
                page.props.conversation_id
            ) {
                page.props.conversation_name = result.data.name;
            }

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

const handleConversationShare = () => {
    conversationOverlayPanel.value.visible = false;

    showConversationShareDialog.value = true;
};

const handleConversationShareDialogHide = () => {
    selectedConversation.value = null;
};

const createShareLink = () => {
    if (isSendingRequest.value) return;

    isSharingConversation.value = true;

    window.axios
        .post("/share", {
            conversation_id: selectedConversation.value.url_id,
        })
        .then((result) => {
            const id = result.data.shared_url_id;

            navigator.clipboard
                .writeText(
                    window.location.protocol +
                        "//" +
                        window.location.host +
                        `/share/${id}`,
                )
                .then(() => {
                    toast.add({
                        severity: "success",
                        summary: "Success",
                        detail: "Copied shared conversation URL to clipboard",
                        life: 5000,
                    });

                    router.reload({
                        only: ["auth"],
                    });
                })
                .finally(() => {
                    selectedConversation.value = null;

                    showConversationShareDialog.value = false;
                });
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
            isSharingConversation.value = false;
        });
};

const deleteSharedConversation = () => {
    if (isSendingRequest.value) return;

    isDeletingSharedConversation.value = true;

    window.axios
        .delete("/share", {
            data: {
                conversation_id: selectedConversation.value.url_id,
            },
        })
        .then((result) => {
            selectedConversation.value = null;

            showConversationShareDialog.value = false;

            router.reload({
                only: ["auth"],
            });
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
            isDeletingSharedConversation.value = false;
        });
};

const isSendingRequest = computed(() => {
    return (
        isSharingConversation.value ||
        isRenamingConversation.value ||
        isDeletingConversation.value ||
        isDeletingSharedConversation.value
    );
});

const getSharedConversationLink = computed(() => {
    return `${window.location.protocol}//${window.location.host}/share/${selectedConversation.value.shared_url_id}`;
});
</script>

<template>
    <div
        v-for="(conversation, index) in $page.props.auth.history"
        :key="conversation.url_id"
        class="my-2"
    >
        <div
            v-if="
                !showRenameInput ||
                (selectedConversation &&
                    selectedConversation.url_id !== conversation.url_id)
            "
            class="relative flex group rounded-lg hover:bg-app-dark"
            :class="{
                'bg-[#343537]':
                    conversation.url_id ===
                    $page.url.slice($page.url.lastIndexOf('/') + 1),

                'bg-app-dark':
                    selectedConversation &&
                    selectedConversation.url_id === conversation.url_id,
            }"
            v-tooltip="{
                value: conversation.name,
                showDelay: 50,
            }"
        >
            <Link
                :href="`/chat/${conversation.url_id}`"
                class="block flex-1 my-1 px-2 py-1 whitespace-nowrap truncate rounded-lg cursor-pointer"
            >
                {{ conversation.name }}
            </Link>
            <button
                @click="toggleConversationOverlayPanel($event, conversation)"
                class="block absolute right-2 top-1 p-1 pl-2 rounded-lg hidden bg-app-dark group-hover:block"
            >
                <span
                    :class="
                        isSendingRequest
                            ? 'pi pi-spin pi-spinner'
                            : 'pi pi-ellipsis-h'
                    "
                ></span>
            </button>
        </div>

        <div v-else class="block flex-1 my-1 py-2 rounded-lg">
            <InputText
                v-model="selectedConversation.name"
                @keydown.enter="renameConversation"
                ref="renameInput"
                class="w-full rounded-lg text-white bg-black"
            />
        </div>
    </div>

    <!-- Conversations Overlay Panel -->
    <OverlayPanel
        ref="conversationOverlayPanel"
        class="font-semibold bg-app-dark border-none z-50"
        @hide="handleConversationOverlayPanelHiding"
    >
        <div
            @click="handleConversationShare"
            class="flex gap-4 p-2 text-sm text-white cursor-pointer rounded-lg hover:bg-gray-700/20"
        >
            <div>
                <span class="pi pi-share-alt"></span>
            </div>
            <div class="block">Share</div>
        </div>
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
                <span class="pi pi-trash"></span>
            </div>
            <div class="block">Delete chat</div>
        </div>
    </OverlayPanel>

    <!-- Conversation Share Dialog -->
    <Dialog
        v-model:visible="showConversationShareDialog"
        @hide="handleConversationShareDialogHide"
        :draggable="false"
        modal
        header="Share link to conversation"
        class="xl:max-w-[35%] max-w-[95%]"
    >
        <p v-if="selectedConversation.shared_url_id">
            You have shared this chat
            <Link
                :href="getSharedConversationLink"
                class="underline cursor-pointer"
                >before</Link
            >. If you want to update the shared chat content,
            <span
                @click="deleteSharedConversation"
                class="underline cursor-pointer"
                >delete this link</span
            >
            and create a new shared link.
        </p>
        <p v-else>
            Messages you send after creating your link won't be shared. Anyone
            with the URL will be able to view the shared chat.
        </p>
        <div class="flex justify-end gap-2 mt-3">
            <Button
                @click="createShareLink"
                :icon="
                    isSharingConversation || isDeletingSharedConversation
                        ? 'pi pi-spin pi-spinner'
                        : 'pi pi-link'
                "
                label="Share link"
                :disabled="selectedConversation.shared_url_id"
            />
        </div>
    </Dialog>
</template>

<style>
.p-overlaypanel:after,
.p-overlaypanel:before {
    border-bottom-color: var(--app-dark);
}
</style>
