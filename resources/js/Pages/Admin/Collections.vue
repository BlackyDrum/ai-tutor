<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

import AdminLayout from "@/Layouts/AdminLayout.vue";

import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";

defineProps({
    collections: Array,
});

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const tableHeadBackground = ref("#DADADA");
const selectedCollection = ref(null);
const isDeleting = ref(false);
const isSettingActive = ref(false);

const tableItems = [
    { header: "ID", field: "id" },
    { header: "Name", field: "name" },
    { header: "Created At", field: "created_at" },
];

const confirmCollectionDeletion = () => {
    if (!selectedCollection.value) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: "You need to select a collection",
            life: 5000,
        });
        return;
    }

    confirm.require({
        message: "Do you want to delete this collection?",
        header: "Deleting collection",
        icon: "pi pi-info-circle",
        rejectLabel: "Cancel",
        acceptLabel: "Delete",
        rejectClass: "p-button-secondary p-button-outlined",
        acceptClass: "p-button-danger",
        accept: () => {
            isDeleting.value = true;

            window.axios
                .delete("/admin/embeddings/collections", {
                    data: {
                        id: selectedCollection.value.id,
                    },
                })
                .then((result) => {
                    page.props.collections.splice(
                        page.props.collections.findIndex(
                            (collection) => collection.id === result.data.id,
                        ),
                        1,
                    );
                })
                .catch((error) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail:
                            error.response.data.message ?? error.response.data,
                        life: 5000,
                    });
                })
                .finally(() => {
                    selectedCollection.value = null;

                    isDeleting.value = false;
                });
        },
        reject: () => {},
    });
};

const setCollectionActive = () => {
    if (!selectedCollection.value) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: "You need to select a collection",
            life: 5000,
        });
        return;
    }

    isSettingActive.value = true;

    window.axios
        .patch("/admin/embeddings/collections/active", {
            id: selectedCollection.value.id,
        })
        .then((result) => {
            const oldActive = page.props.collections.find((collection) => collection.active);
            oldActive.active = false;

            const newActive = page.props.collections.find(
                (collection) => collection.id === result.data.id,
            );
            newActive.active = true;
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
            selectedCollection.value = null;

            isSettingActive.value = false;
        });
};
</script>

<template>
    <AdminLayout>
        <Head title="Collections" />

        <div
            class="h-dvh w-full p-5 flex flex-wrap items-center justify-center bg-admin-light overflow-y-auto"
        >
            <div class="w-full">
                <div class="flex">
                    <div class="flex flex-wrap gap-3 mr-auto mb-5">
                        <Button
                            class="text-white border-gray-300 font-medium"
                            label="Set Active"
                            :icon="
                                isSettingActive
                                    ? 'pi pi-spin pi-spinner'
                                    : 'pi pi-angle-double-up'
                            "
                            @click="setCollectionActive"
                        />
                        <Button
                            class="text-black border-gray-300 bg-white font-medium"
                            label="Delete"
                            :icon="
                                isDeleting
                                    ? 'pi pi-spin pi-spinner'
                                    : 'pi pi-trash'
                            "
                            @click="confirmCollectionDeletion"
                        />
                    </div>
                </div>
                <DataTable
                    v-model:selection="selectedCollection"
                    :value="$page.props.collections"
                    selectionMode="single"
                    class="shadow-lg"
                    scrollable
                    scrollHeight="35rem"
                    showGridlines
                >
                    <template #empty> No collections created yet </template>
                    <Column
                        v-for="item in tableItems"
                        :key="item.id"
                        :headerStyle="{ background: tableHeadBackground }"
                        :field="item.field"
                        :header="item.header"
                        sortable
                        :class="{
                            'min-w-[30rem]': item.field === 'instructions',
                        }"
                    ></Column>

                    <Column
                        :headerStyle="{ background: tableHeadBackground }"
                        field="active"
                        header="Active"
                        sortable
                    >
                        <template #body="{ data, field }">
                            <div
                                class="pi pi-circle-fill text-green-600"
                                :class="{ 'text-red-600': !data[field] }"
                            ></div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>
