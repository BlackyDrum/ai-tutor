<script setup>
import { router, Link, usePage } from "@inertiajs/vue3";
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import UserAvatar from "@/Components/UserAvatar.vue";
import ProfileItems from "@/Components/ProfileItems.vue";

import ScrollPanel from "primevue/scrollpanel";
import OverlayPanel from "primevue/overlaypanel";
import InputText from "primevue/inputtext";

const appName = import.meta.env.VITE_APP_NAME;

const page = usePage();
const toast = useToast();

const showProfileOP = ref(false);
const historyOP = ref();
const selectedConversation = ref(null);
const showResponsiveNavBar = ref(true);
const isDeletingConversation = ref(false);
const scrollPanel = ref();
const renameInput = ref();
const showRenameInput = ref(false);
const isRenamingConversation = ref(false);
const selectedConversationName = ref(null);

onMounted(() => {
    const scrollPosition = router.restore("scroll-position") ?? 0;
    scrollTo(scrollPosition);

    handleResize();

    window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
    router.remember(
        scrollPanel.value.$el.children[0].children[0].scrollTop,
        "scroll-position",
    );

    window.removeEventListener("resize", handleResize);
});

const scrollTo = (pos) => {
    scrollPanel.value.$el.children[0].children[0].scrollTo(0, pos);
};

const handleResize = () => {
    if (window.innerWidth <= 768) {
        showResponsiveNavBar.value = false;
    }
};

const toggleHistoryOverlayPanel = (event, conversation) => {
    historyOP.value.toggle(event);

    selectedConversation.value = historyOP.value.visible ? conversation : null;

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

            historyOP.value.visible = false;

            isDeletingConversation.value = false;
        });
};

const handleRenameConversation = () => {
    if (isRenamingConversation.value) return;

    showRenameInput.value = true;

    historyOP.value.visible = false;

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
        @click="showResponsiveNavBar = !showResponsiveNavBar"
        class="fixed right-0 mr-3 z-50 mt-3 p-2 rounded-full cursor-pointer dark:text-white hover:bg-gray-800/50"
    >
        <span class="pi pi-bars"></span>
    </div>

    <div
        class="h-dvh w-[200px] lg:w-[260px] z-20 flex-shrink-0 bg-black text-white max-sm:fixed"
        :class="{ hidden: !showResponsiveNavBar }"
    >
        <nav class="h-full w-full p-2">
            <div class="h-full w-full flex flex-col">
                <Link
                    href="/"
                    class="block flex p-2 rounded-lg cursor-pointer hover:bg-app-light"
                >
                    <div>
                        <ApplicationLogo class="w-8" />
                    </div>
                    <div class="self-center ml-3">
                        {{ appName }}
                    </div>
                    <div class="self-center ml-auto">
                        <span class="pi pi-pencil"></span>
                    </div>
                </Link>
                <div class="min-h-0 flex-1">
                    <ScrollPanel ref="scrollPanel" class="w-full h-full p-2">
                        <div
                            v-for="(conversation, index) in $page.props.auth
                                .history"
                            :key="conversation.api_id"
                            class="my-2"
                        >
                            <div
                                v-if="
                                    !showRenameInput ||
                                    selectedConversation !== conversation.api_id
                                "
                                class="relative flex group rounded-lg hover:bg-app-dark"
                                :class="{
                                    'bg-app-dark':
                                        conversation.api_id ===
                                        $page.url.slice(
                                            $page.url.lastIndexOf('/') + 1,
                                        ),
                                }"
                            >
                                <Link
                                    :href="`/chat/${conversation.api_id}`"
                                    class="block flex-1 my-1 px-2 py-1 whitespace-nowrap truncate rounded-lg cursor-pointer"
                                >
                                    {{ conversation.name }}
                                </Link>
                                <button
                                    @click="
                                        toggleHistoryOverlayPanel(
                                            $event,
                                            conversation.api_id,
                                        )
                                    "
                                    class="block absolute right-2 top-1 p-1 rounded-lg hidden bg-app-dark group-hover:block"
                                >
                                    <span class="pi pi-ellipsis-h"></span>
                                </button>
                            </div>

                            <div
                                v-else
                                class="block flex-1 my-1 py-2 rounded-lg"
                            >
                                <InputText
                                    v-model="selectedConversationName"
                                    @keydown.enter="renameConversation"
                                    ref="renameInput"
                                    class="w-full rounded-lg text-white bg-black max-xl:w-3/4"
                                />
                            </div>
                        </div>
                    </ScrollPanel>
                </div>
                <div class="w-full relative text-sm">
                    <ProfileItems v-if="showProfileOP" />
                </div>
                <div
                    @click="showProfileOP = !showProfileOP"
                    :class="{ 'bg-app-light': showProfileOP }"
                    class="flex gap-4 p-2 cursor-pointer rounded-lg hover:bg-app-light"
                >
                    <UserAvatar />

                    <div class="self-center">
                        {{ $page.props.auth.user.name }}
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- Chat History Overlay Panel -->
    <OverlayPanel
        ref="historyOP"
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
.p-scrollpanel-bar-x {
    display: none;
}
.p-overlaypanel:after,
.p-overlaypanel:before {
    display: none;
}
</style>
