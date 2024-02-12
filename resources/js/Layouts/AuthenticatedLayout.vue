<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import ScrollPanel from "primevue/scrollpanel";
import Avatar from "primevue/avatar";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const showProfileOP = ref(false);
const showResponsiveNavBar = ref(false);

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div
        @click="showResponsiveNavBar = !showResponsiveNavBar"
        class="fixed right-0 mr-3 mt-3 p-2 rounded-full cursor-pointer dark:text-white hover:bg-gray-800/50"
    >
        <span class="pi pi-bars"></span>
    </div>

    <div class="flex">
        <div
            class="h-dvh w-[260px] flex-shrink-0 bg-black text-white max-sm:fixed"
            :class="{ 'hidden' : !showResponsiveNavBar }"
        >
            <nav class="h-full w-full p-2">
                <div class="h-full w-full flex flex-col">
                    <div
                        @click="router.get('/')"
                        class="flex p-2 rounded-lg cursor-pointer hover:bg-app-light"
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
                    </div>
                    <div class="h-[60%] flex-1">
                        <ScrollPanel class="w-full h-full p-2">
                            <p v-for="i in 100">Chat #{{ i }}</p>
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
                            <hr class="border-0 h-px bg-gray-500/40">
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
                        <Avatar
                            :label="$page.props.auth.user.name[0]"
                            class="bg-[#E67E22]"
                            shape="circle"
                        />
                        <div class="self-center">
                            {{ $page.props.auth.user.name }}
                        </div>
                    </div>
                </div>
            </nav>
        </div>
        <div class="w-full">
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
.p-scrollpanel-bar-x {
    display: none;
}
</style>
