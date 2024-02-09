<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import ScrollPanel from "primevue/scrollpanel";
import Avatar from "primevue/avatar";

const showProfileOP = ref(false);
const showResponsiveNavBar = ref(false);
</script>

<template>
    <div
        @click="showResponsiveNavBar = !showResponsiveNavBar"
        class="fixed z-30 dark:text-white ml-2 mt-2 cursor-pointer"
        :class="{ 'text-white': showResponsiveNavBar }"
    >
        <span class="pi pi-bars"></span>
    </div>

    <div class="flex">
        <div
            class="h-screen w-[260px] flex-shrink-0 z-20 pt-10 bg-black text-white max-sm:fixed"
            :class="{ hidden: !showResponsiveNavBar }"
        >
            <nav class="h-full w-full p-2">
                <div class="h-full w-full flex flex-col">
                    <div class="flex-1 h-[60%]">
                        <ScrollPanel class="w-full h-full"> </ScrollPanel>
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
