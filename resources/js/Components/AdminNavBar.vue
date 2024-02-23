<script setup>
import { router, Link } from "@inertiajs/vue3";
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const appName = import.meta.env.VITE_APP_NAME;

const toast = useToast();

const showResponsiveNavBar = ref(true);
const fileForm = ref();
const fileInput = ref();

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
            {
                label: "Create embedding",
                url: "/admin/create-embedding",
                other: true,
            },
        ],
    },
    {
        groupName: "Users",
        items: [
            { label: "All users", url: "/admin/users" },
            { label: "Create user", url: "/admin/create-users" },
        ],
    },
];

onMounted(() => {
    handleResize();

    window.addEventListener("resize", handleResize);

    fileInput.value[0].onchange = () => {
        const formData = new FormData();
        formData.append("file", fileInput.value[0].files[0]);
        window.axios
            .post("/admin/embeddings/create", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((result) => {
                toast.add({
                    severity: "success",
                    summary: "Success",
                    detail: "New embedding created",
                    life: 5000,
                });

                router.reload();
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
                fileInput.value[0].value = null;
            });
    };
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
        class="h-dvh w-[200px] lg:w-[260px] z-20 overflow-y-auto bg-admin-light max-sm:bg-admin-dark flex-shrink-0 max-sm:fixed"
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
                <div class="mb-6">
                    <ul v-for="menu in menuItems" class="p-1 mt-4">
                        <div class="text-xs">{{ menu.groupName }}</div>
                        <template v-for="item in menu.items">
                            <li
                                v-if="!item.other"
                                @click="router.get(item.url)"
                                class="w-full p-2 rounded-lg font-medium cursor-pointer hover:bg-[#EEF2FF]"
                                :class="{
                                    'bg-[#EEF2FF] text-[#4338CC] font-bold':
                                        $page.url === item.url,
                                }"
                            >
                                <div>
                                    {{ item.label }}
                                </div>
                            </li>
                            <li
                                class="w-full p-2 rounded-lg font-medium cursor-pointer hover:bg-[#EEF2FF]"
                                v-else
                            >
                                <div class="w-full">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        id="files"
                                        accept=".pdf, .txt"
                                        class="hidden"
                                    />
                                    <label for="files">{{ item.label }}</label>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
                <hr class="h-px bg-admin-dark border-0" />
                <ul class="p-1 mt-4">
                    <li
                        class="w-full p-2 rounded-lg font-medium cursor-pointer hover:bg-[#EEF2FF]"
                    >
                        <div @click="router.get('/')">
                            Back to {{ appName }}
                        </div>
                    </li>
                    <li
                        class="w-full p-2 rounded-lg font-medium cursor-pointer hover:bg-[#EEF2FF]"
                    >
                        <div @click="router.post('/logout')">Logout</div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</template>
