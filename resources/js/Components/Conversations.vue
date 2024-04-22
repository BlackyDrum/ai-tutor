<script setup>
import { router, Link, usePage } from "@inertiajs/vue3";
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

import OverlayPanel from "primevue/overlaypanel";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import VirtualScroller from "primevue/virtualscroller";

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
const scrollContainer = ref();

onMounted(() => {
    scrollTo(getScrollPosition());

    window.addEventListener("click", handleClickOutsideRenameInput, true);
});

onBeforeUnmount(() => {
    router.remember(scrollContainer.value.$el.scrollTop, "scroll-position");

    window.removeEventListener("click", handleClickOutsideRenameInput, true);
});

const getScrollPosition = () => {
    return router.restore("scroll-position") ?? 0;
};

const scrollTo = (pos) => {
    nextTick(() => {
        scrollContainer.value.$el.scrollTo(0, pos);
    });
};

const handleClickOutsideRenameInput = (event) => {
    if (
        showRenameInput.value &&
        !event.target.parentNode.className.includes("rename-input")
    ) {
        renameConversation();
    }
};

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
        message: `This will delete '${selectedConversation.value.name}'`,
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
        renameInput.value.$el.focus();
    });

    conversationOverlayPanel.value.visible = false;
};

const renameConversation = () => {
    if (isSendingRequest.value) return;

    const conversation = page.props.auth.history.find(
        (conversation) =>
            conversation.url_id === selectedConversation.value.url_id,
    );

    if (conversation.name === selectedConversation.value.name) {
        selectedConversation.value = null;
        showRenameInput.value = false;

        return;
    }

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
    <VirtualScroller
        :items="$page.props.auth.history"
        class="scroll-container my-2"
        :itemSize="40"
        ref="scrollContainer"
    >
        <template v-slot:item="{ item, options }">
            <div
                v-if="
                    !showRenameInput ||
                    (selectedConversation &&
                        selectedConversation.url_id !== item.url_id)
                "
                class="group relative flex rounded-lg hover:bg-gray-200 hover:dark:bg-app-dark"
                :class="{
                    'bg-gray-300 dark:bg-[#343537]':
                        item.url_id ===
                        $page.url.slice($page.url.lastIndexOf('/') + 1),

                    'bg-gray-200 dark:bg-app-dark':
                        selectedConversation &&
                        selectedConversation.url_id === item.url_id,
                }"
                v-tooltip="{
                    value: item.name,
                }"
            >
                <Link
                    :href="`/chat/${item.url_id}`"
                    class="my-1 block flex-1 cursor-pointer truncate whitespace-nowrap rounded-lg px-2 py-1"
                >
                    {{ item.name }}
                </Link>
                <button
                    @click="toggleConversationOverlayPanel($event, item)"
                    class="absolute right-2 top-1 block hidden rounded-lg bg-gray-200 p-1 pl-2 group-hover:block dark:bg-app-dark"
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

            <div v-else class="rename-input my-1 block flex-1 rounded-lg py-2">
                <InputText
                    v-model="selectedConversation.name"
                    @keydown.enter="renameConversation"
                    ref="renameInput"
                    class="w-full rounded-lg dark:bg-black dark:text-white"
                />
            </div>
        </template>
    </VirtualScroller>

    <!-- Conversations Overlay Panel -->
    <OverlayPanel
        ref="conversationOverlayPanel"
        class="z-50 border-none font-semibold dark:bg-app-dark"
        @hide="handleConversationOverlayPanelHiding"
    >
        <div
            @click="handleConversationShare"
            class="flex cursor-pointer gap-4 rounded-lg p-2 text-sm hover:bg-gray-700/20 dark:text-white"
        >
            <div>
                <span class="pi pi-share-alt"></span>
            </div>
            <div class="block">Share</div>
        </div>
        <div
            @click="handleRenameConversation"
            class="flex cursor-pointer gap-4 rounded-lg p-2 text-sm hover:bg-gray-700/20 dark:text-white"
        >
            <div>
                <span class="pi pi-pencil"></span>
            </div>
            <div class="block">Rename</div>
        </div>
        <div
            @click="deleteConversation"
            class="flex cursor-pointer gap-4 rounded-lg p-2 text-sm text-red-600 hover:bg-gray-700/20"
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
        class="max-w-[95%] xl:max-w-[35%]"
    >
        <p v-if="selectedConversation.shared_url_id">
            You have shared this chat
            <Link
                :href="getSharedConversationLink"
                class="cursor-pointer underline"
                >before</Link
            >. If you want to update the shared chat content,
            <span
                @click="deleteSharedConversation"
                class="cursor-pointer underline"
                >delete this link</span
            >
            and create a new shared link.
        </p>
        <p v-else>
            Messages you send after creating your link won't be shared. Anyone
            with the URL will be able to view the shared chat.
        </p>
        <div class="mt-3 flex justify-end gap-2">
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
.p-virtualscroller-content {
    max-width: 100%;
}

body.dark .p-overlaypanel:after,
.p-overlaypanel:before {
    border-bottom-color: transparent;
    border-top-color: transparent;
}
</style>
