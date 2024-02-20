<script setup>
import { router, Link } from "@inertiajs/vue3";
import { onBeforeMount, onBeforeUnmount, onMounted, ref } from "vue";

import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import UserAvatar from "@/Components/UserAvatar.vue";

import ScrollPanel from "primevue/scrollpanel";

const appName = import.meta.env.VITE_APP_NAME;

const showProfileOP = ref(false);
const showResponsiveNavBar = ref(true);
const scrollPanel = ref();

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
                        >
                            <Link
                                :href="`/chat/${conversation.id}`"
                                :class="{
                                    'bg-app-dark':
                                        conversation.id ===
                                        $page.url.slice(
                                            $page.url.lastIndexOf('/') + 1,
                                        ),
                                }"
                                class="block my-1 p-2 rounded-lg cursor-pointer hover:bg-app-dark"
                            >
                                Chat #{{
                                    $page.props.auth.history.length - index
                                }}
                            </Link>
                        </div>
                    </ScrollPanel>
                </div>
                <div class="w-full relative">
                    <div
                        v-if="showProfileOP"
                        class="absolute w-full z-10 -top-[100px] p-1 mb-2 rounded-lg bg-app-dark"
                    >
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
</template>

<style>
.p-scrollpanel-bar-x {
    display: none;
}
</style>
