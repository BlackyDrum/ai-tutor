<script setup>
import { router, Link } from "@inertiajs/vue3";
import {onBeforeUnmount, onMounted, ref} from "vue";

import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const appName = import.meta.env.VITE_APP_NAME;

const showResponsiveNavBar = ref(true);

const menuItems = [
    { groupName: "", items: [{ label: "Dashboard", url: "/admin" }] },
    {
        groupName: "Agents",
        items: [
            { label: "All agents", url: "/admin/agents" },
            { label: "Create agent", url: "/admin/agents/create-agent" },
        ],
    },
    {
        groupName: "Embeddings",
        items: [
            { label: "All embeddings", url: "/admin/embeddings" },
            { label: "Create embedding", url: "/admin/create-embedding" },
        ],
    },
];

onMounted(() => {
    handleResize();

    window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", handleResize);
});


const handleResize = () => {
    if (window.innerWidth <= 768) {
        showResponsiveNavBar.value = false;
    }
};
</script>

<template>
    <div
        @click="showResponsiveNavBar = !showResponsiveNavBar"
        class="fixed right-0 mr-3 z-50 mt-3 p-2 rounded-full cursor-pointer hover:bg-admin-dark"
    >
        <span class="pi pi-bars"></span>
    </div>

    <div
        class="h-dvh w-[200px] lg:w-[260px] z-20 overflow-y-auto bg-admin-dark flex-shrink-0 max-sm:fixed"
        :class="{ hidden: !showResponsiveNavBar }"
    >
        <nav class="h-full w-full p-2">
            <div class="h-full w-full flex flex-col">
                <Link
                    href="/admin"
                    class="block flex p-2 rounded-lg cursor-pointer hover:bg-admin-dark"
                >
                    <div>
                        <ApplicationLogo class="w-8" />
                    </div>
                    <div class="self-center ml-3 font-bold">Adminpanel</div>
                </Link>
                <div class="my-6">
                    <ul v-for="menu in menuItems" class="p-1 mt-4">
                        <div class="text-xs">{{ menu.groupName }}</div>
                        <li
                            v-for="item in menu.items"
                            @click="router.get(item.url)"
                            class="w-full p-2 rounded-lg font-medium cursor-pointer"
                            :class="{
                                'bg-[#EEF2FF] text-[#4338CC] font-bold':
                                    $page.url === item.url,
                            }"
                        >
                            <div>
                                {{ item.label }}
                            </div>
                        </li>
                    </ul>
                </div>
                <hr class="h-px bg-admin-light border-0" />
                <ul class="p-1 mt-4">
                    <li
                        class="w-full p-2 rounded-lg font-medium cursor-pointer"
                    >
                        <div @click="router.get('/')">
                            Back to {{ appName }}
                        </div>
                    </li>
                    <li
                        class="w-full p-2 rounded-lg font-medium cursor-pointer"
                    >
                        <div @click="router.post('/logout')">Logout</div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</template>
