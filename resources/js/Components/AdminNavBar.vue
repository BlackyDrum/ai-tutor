<script setup>
import { router, Link } from "@inertiajs/vue3";
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import ApplicationLogo from "@/Components/ApplicationLogo.vue";

import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";

const appName = import.meta.env.VITE_APP_NAME;

const toast = useToast();

const showResponsiveNavBar = ref(true);
const fileInput = ref();
const showCollectionCreateOverlay = ref(false);
const showCollectionSelectOverlay = ref(false);
const isCreatingCollection = ref(false);
const isUploadingFile = ref(false);
const collectionInput = ref();
const selectedCollection = ref();

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
                url: null,
                upload: true,
            },
            { label: "All collections", url: "/admin/embeddings/collections" },
            {
                label: "Create collection",
                url: null,
                overlay: true,
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
        showCollectionSelectOverlay.value = true;
    };
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", handleResize);
});

const handleUpload = () => {
    if (!selectedCollection.value) return;

    const formData = new FormData();
    formData.append("file", fileInput.value[0].files[0]);
    formData.append("collection", selectedCollection.value.id);

    isUploadingFile.value = true;

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

            showCollectionSelectOverlay.value = false;

            selectedCollection.value = null;

            fileInput.value[0].value = null;

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
            isUploadingFile.value = false;
        });
};

const handleCollectionCreation = () => {
    if (!collectionInput.value) return;

    isCreatingCollection.value = true;

    window.axios
        .post("/admin/embeddings/collections/create", {
            name: collectionInput.value,
        })
        .then((result) => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: "New collection created",
                life: 5000,
            });

            collectionInput.value = null;
            showCollectionCreateOverlay.value = false;

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
            isCreatingCollection.value = false;
        });
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
                                v-if="!item.upload && !item.overlay"
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
                                v-else-if="item.upload"
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
                            <li
                                class="w-full p-2 rounded-lg font-medium cursor-pointer hover:bg-[#EEF2FF]"
                                v-else-if="item.overlay"
                                @click="showCollectionCreateOverlay = true"
                            >
                                <div>
                                    {{ item.label }}
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

    <!-- Dialog to create a new Collection -->
    <Dialog
        v-model:visible="showCollectionCreateOverlay"
        modal
        header="Create Collection"
    >
        <div class="flex flex-col gap-4">
            <div>
                <InputText
                    v-model="collectionInput"
                    placeholder="Collection Name"
                />
            </div>
            <div class="w-full">
                <Button
                    @click="handleCollectionCreation"
                    :icon="
                        isCreatingCollection
                            ? 'pi pi-spin pi-spinner'
                            : 'pi pi-save'
                    "
                    class="w-full"
                    label="Save"
                />
            </div>
        </div>
    </Dialog>

    <!-- Dialog to select a collection when uploading a file -->
    <Dialog
        v-model:visible="showCollectionSelectOverlay"
        modal
        header="Select Collection"
    >
        <div class="flex flex-col gap-4">
            <div class="w-full">
                <Dropdown
                    v-model="selectedCollection"
                    :options="$page.props.collections"
                    optionLabel="name"
                    placeholder="Select a collection"
                    class="w-full"
                />
            </div>
            <div class="w-full">
                <Button
                    @click="handleUpload"
                    :icon="
                        isUploadingFile
                            ? 'pi pi-spin pi-spinner'
                            : 'pi pi-upload'
                    "
                    class="w-full"
                    label="Upload"
                />
            </div>
        </div>
    </Dialog>
</template>
