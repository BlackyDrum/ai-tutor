<script setup>
import { router, Link, usePage } from "@inertiajs/vue3";
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import UserAvatar from "@/Components/UserAvatar.vue";
import ProfileItems from "@/Components/ProfileItems.vue";
import Conversations from "@/Components/Conversations.vue";

import ScrollPanel from "primevue/scrollpanel";

const appName = import.meta.env.VITE_APP_NAME;

const page = usePage();
const toast = useToast();

const showProfileOP = ref(false);
const showResponsiveNavBar = ref(true);
const scrollPanel = ref();

onMounted(() => {
    const scrollPosition = router.restore("scroll-position") ?? 0;
    scrollTo(scrollPosition);

    handleResize();

    window.addEventListener("resize", handleResize);
    window.addEventListener("click", handleClickOutsideProfileOverlay, true);
});

onBeforeUnmount(() => {
    router.remember(
        scrollPanel.value.$el.children[0].children[0].scrollTop,
        "scroll-position",
    );

    window.removeEventListener("resize", handleResize);
    window.removeEventListener("click", handleClickOutsideProfileOverlay, true);
});

const handleClickOutsideProfileOverlay = (event) => {
    const className = "profile-overlay";

    if (
        showProfileOP.value &&
        !event.target.className.includes(className) &&
        !event.target.parentNode.className.includes(className)
    ) {
        showProfileOP.value = false;
    }
};

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
        class="fixed right-0 z-50 mr-3 mt-3 cursor-pointer rounded-full p-2 hover:bg-gray-800/50 dark:text-white"
    >
        <span class="pi pi-bars"></span>
    </div>

    <div
        class="z-20 h-dvh w-[260px] flex-shrink-0 bg-black text-white max-md:fixed"
        :class="{ hidden: !showResponsiveNavBar }"
    >
        <nav class="h-full w-full p-2">
            <div class="flex h-full w-full flex-col">
                <Link
                    href="/"
                    class="block flex cursor-pointer rounded-lg p-2 hover:bg-app-light"
                >
                    <div>
                        <ApplicationLogo class="w-8" />
                    </div>
                    <div class="ml-3 self-center">
                        {{ appName }}
                    </div>
                    <div class="ml-auto self-center">
                        <svg
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M16.7929 2.79289C18.0118 1.57394 19.9882 1.57394 21.2071 2.79289C22.4261 4.01184 22.4261 5.98815 21.2071 7.20711L12.7071 15.7071C12.5196 15.8946 12.2652 16 12 16H9C8.44772 16 8 15.5523 8 15V12C8 11.7348 8.10536 11.4804 8.29289 11.2929L16.7929 2.79289ZM19.7929 4.20711C19.355 3.7692 18.645 3.7692 18.2071 4.2071L10 12.4142V14H11.5858L19.7929 5.79289C20.2308 5.35499 20.2308 4.64501 19.7929 4.20711ZM6 5C5.44772 5 5 5.44771 5 6V18C5 18.5523 5.44772 19 6 19H18C18.5523 19 19 18.5523 19 18V14C19 13.4477 19.4477 13 20 13C20.5523 13 21 13.4477 21 14V18C21 19.6569 19.6569 21 18 21H6C4.34315 21 3 19.6569 3 18V6C3 4.34314 4.34315 3 6 3H10C10.5523 3 11 3.44771 11 4C11 4.55228 10.5523 5 10 5H6Z"
                                fill="currentColor"
                            ></path>
                        </svg>
                    </div>
                </Link>
                <div class="min-h-0 flex-1">
                    <ScrollPanel ref="scrollPanel" class="h-full w-full p-2">
                        <Conversations />
                    </ScrollPanel>
                </div>
                <div class="relative w-full text-sm">
                    <ProfileItems v-if="showProfileOP" />
                </div>
                <div
                    @click="showProfileOP = !showProfileOP"
                    :class="{ 'bg-app-light': showProfileOP }"
                    class="profile-overlay flex cursor-pointer gap-4 rounded-lg p-2 hover:bg-app-light"
                >
                    <UserAvatar />

                    <div class="self-center">
                        {{ $page.props.auth.user.abbreviation }}
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
