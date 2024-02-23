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
    files: Array,
});

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const tableHeadBackground = ref("#DADADA");
const selectedFile = ref(null);
const isDeleting = ref(false);

const tableItems = [
    { header: "ID", field: "id" },
    { header: "Name", field: "name" },
    { header: "Size", field: "size" },
    { header: "Mime", field: "mime" },
    { header: "Created At", field: "created_at" },
];

const confirmFileDeletion = () => {
    if (!selectedFile.value) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: "You need to select a file",
            life: 5000,
        });
        return;
    }

    confirm.require({
        message: "Do you want to delete this file/embedding?",
        header: "Deleting file",
        icon: "pi pi-info-circle",
        rejectLabel: "Cancel",
        acceptLabel: "Delete",
        rejectClass: "p-button-secondary p-button-outlined",
        acceptClass: "p-button-danger",
        accept: () => {
            isDeleting.value = true;

            window.axios
                .delete("/admin/embeddings", {
                    data: {
                        id: selectedFile.value.id,
                    },
                })
                .then((result) => {
                    page.props.files.splice(
                        page.props.files.findIndex(
                            (file) => file.id === result.data.id,
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
                    selectedFile.value = null;

                    isDeleting.value = false;
                });
        },
        reject: () => {},
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Embeddings" />

        <div
            class="h-dvh w-full p-5 flex flex-wrap items-center justify-center bg-admin-light overflow-y-auto"
        >
            <div class="w-full">
                <div class="flex">
                    <div class="flex flex-wrap gap-3 mr-auto mb-5">
                        <Button
                            class="text-black border-gray-300 bg-white font-medium"
                            label="Delete"
                            :icon="
                                isDeleting
                                    ? 'pi pi-spin pi-spinner'
                                    : 'pi pi-trash'
                            "
                            @click="confirmFileDeletion"
                        />
                    </div>
                </div>
                <DataTable
                    v-model:selection="selectedFile"
                    :value="$page.props.files"
                    selectionMode="single"
                    class="shadow-lg"
                    scrollable
                    scrollHeight="35rem"
                    showGridlines
                >
                    <template #empty> No embeddings created yet </template>
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
                </DataTable>
            </div>
        </div>
    </AdminLayout>
</template>
