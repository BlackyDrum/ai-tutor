<script setup>
import { router, Link, usePage } from "@inertiajs/vue3";
import { onBeforeMount, onBeforeUnmount, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import UserAvatar from "@/Components/UserAvatar.vue";

import ScrollPanel from "primevue/scrollpanel";
import OverlayPanel from "primevue/overlaypanel";

const appName = import.meta.env.VITE_APP_NAME;

const page = usePage();
const toast = useToast();

const showProfileOP = ref(false);
const historyOP = ref();
const selectedConversation = ref(null);
const showResponsiveNavBar = ref(true);
const scrollPanel = ref();
const isCheckingStatus = ref(false);

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
};

const deleteConversation = () => {
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
        });
};

const checkChromaDBStatus = () => {
    if (isCheckingStatus.value) return;

    isCheckingStatus.value = true;
    window.axios.get('/chroma/status')
        .then(result => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: result.data.message,
                life: 5000,
            });
        })
        .catch(error => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            isCheckingStatus.value = false;
        })
}
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
                        >
                            <div class="relative flex group">
                                <Link
                                    :href="`/chat/${conversation.api_id}`"
                                    :class="{
                                        'bg-app-dark':
                                            conversation.api_id ===
                                            $page.url.slice(
                                                $page.url.lastIndexOf('/') + 1,
                                            ),
                                    }"
                                    class="block flex-1 my-1 p-2 rounded-lg cursor-pointer hover:bg-app-dark"
                                >
                                    Chat #{{
                                        $page.props.auth.history.length - index
                                    }}
                                </Link>
                                <button
                                    @click="
                                        toggleHistoryOverlayPanel(
                                            $event,
                                            conversation.api_id,
                                        )
                                    "
                                    class="block absolute right-2 top-2 p-1 rounded-lg hidden group-hover:block"
                                >
                                    <span class="pi pi-ellipsis-h"></span>
                                </button>
                            </div>
                        </div>
                    </ScrollPanel>
                </div>
                <div class="w-full relative text-sm">
                    <div
                        v-if="showProfileOP"
                        :class="
                            $page.props.auth.user.admin
                                ? '-top-[175px]'
                                : '-top-[100px]'
                        "
                        class="absolute w-full z-10 p-1 mb-2 rounded-lg bg-app-dark"
                    >
                        <Link
                            v-if="$page.props.auth.user.admin"
                            href="/admin"
                            class="block flex gap-4 p-2 mb-1 cursor-pointer rounded-lg hover:bg-app-light"
                        >
                            <div>
                                <span class="pi pi-user"></span>
                            </div>
                            <div>Admin</div>
                        </Link>
                        <div
                            v-if="$page.props.auth.user.admin"
                            @click="checkChromaDBStatus"
                            class="flex gap-4 p-2 mt-1 cursor-pointer rounded-lg hover:bg-app-light"
                        >
                            <div>
                                <span :class="isCheckingStatus ? 'pi pi-spin pi-spinner' : 'pi pi-database'"></span>
                            </div>
                            <div>Check ChromaDB</div>
                        </div>
                        <div
                            class="flex gap-4 p-2 mb-1 cursor-not-allowed opacity-30 rounded-lg"
                        >
                            <div>
                                <span class="pi pi-cog"></span>
                            </div>
                            <div>Settings</div>
                        </div>
                        <hr class="border-0 h-px bg-gray-500/40" />
                        <div
                            @click="router.post('/logout')"
                            class="flex gap-4 p-2 mt-1 cursor-pointer rounded-lg hover:bg-app-light"
                        >
                            <div>
                                <span class="pi pi-sign-out"></span>
                            </div>
                            <div>Log Out</div>
                        </div>
                    </div>
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
    <OverlayPanel ref="historyOP" class="bg-app-dark border-none z-50">
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
