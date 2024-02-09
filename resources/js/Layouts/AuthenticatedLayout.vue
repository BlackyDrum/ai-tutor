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
        class="fixed z-30 dark:text-white right-0 mr-3 mt-3 cursor-pointer"
    >
        <span class="pi pi-bars"></span>
    </div>

    <div class="flex">
        <div
            class="h-screen w-[260px] flex-shrink-0 z-20 bg-black text-white max-sm:fixed"
            :class="{ hidden: !showResponsiveNavBar }"
        >
            <nav class="h-full w-full p-2">
                <div class="h-full w-full flex flex-col">
                    <div
                        @click="router.get('/')"
                        class="flex hover:bg-app-light cursor-pointer p-2 rounded-lg"
                    >
                        <div>
                            <ApplicationLogo class="w-8" />
                        </div>
                        <div class="self-center ml-3">
                            {{ appName }}
                        </div>
                        <div class="ml-auto self-center">
                            <span class="pi pi-pencil"></span>
                        </div>
                    </div>
                    <div class="flex-1 h-[60%]">
                        <ScrollPanel class="w-full h-full p-2">
                            <p v-for="i in 100">Chat #{{ i }}</p>
                        </ScrollPanel>
                    </div>
                    <div
                        v-if="showProfileOP"
                        class="bg-app-dark p-2 rounded-lg mb-2 transition-all"
                    >
                        <div
                            @click="router.post('/logout')"
                            class="flex gap-4 hover:bg-app-light cursor-pointer p-2 rounded-lg"
                        >
                            <div>
                                <span class="pi pi-sign-out"></span>
                            </div>
                            <div>Log Out</div>
                        </div>
                    </div>
                    <div
                        @click="showProfileOP = !showProfileOP"
                        :class="{ 'bg-app-light': showProfileOP }"
                        class="flex gap-4 hover:bg-app-light cursor-pointer p-2 rounded-lg"
                    >
                        <Avatar
                            :label="$page.props.auth.user.name[0]"
                            class="bg-[#E67E22]"
                            size="large"
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
