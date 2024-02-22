<script setup>
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

import AdminLayout from "@/Layouts/AdminLayout.vue";

import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";

defineProps({
    agents: Array,
});

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const tableHeadBackground = ref("#DADADA");
const selectedAgent = ref(null);
const isDeleting = ref(false);
const isSettingActive = ref(false);

const confirmAgentDeletion = () => {
    if (!selectedAgent.value) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: "You need to select an agent",
            life: 5000,
        });
        return;
    }

    confirm.require({
        message: "Do you want to delete this record?",
        header: "Danger Zone",
        icon: "pi pi-info-circle",
        rejectLabel: "Cancel",
        acceptLabel: "Delete",
        rejectClass: "p-button-secondary p-button-outlined",
        acceptClass: "p-button-danger",
        accept: () => {
            window.axios
                .delete("/admin/agents", {
                    data: {
                        id: selectedAgent.value.id,
                    },
                })
                .then((result) => {
                    page.props.agents.splice(
                        page.props.agents.findIndex(
                            (agent) => agent.id === result.data.id,
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
                    selectedAgent.value = null;
                });
        },
        reject: () => {},
    });
};

const setAgentActive = () => {
    if (!selectedAgent.value) {
        toast.add({
            severity: "info",
            summary: "Info",
            detail: "You need to select an agent",
            life: 5000,
        });
        return;
    }

    window.axios.patch('/admin/agents/active', {
        id: selectedAgent.value.id
    })
        .then(result => {
            const oldActive = page.props.agents.find(agent => agent.active);
            oldActive.active = false;

            const newActive = page.props.agents.find(agent => agent.id === result.data.id);
            newActive.active = true;
        })
        .catch(error => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail:
                    error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            selectedAgent.value = null;
        })
}
</script>

<template>
    <AdminLayout>
        <Head title="Create Agent" />

        <div
            class="h-dvh p-5 w-full flex flex-wrap items-center justify-center bg-admin-light overflow-y-auto"
        >
            <div class="w-full">
                <div class="flex">
                    <div class="flex gap-3 ml-auto mb-5">
                        <Button
                            class="text-black border-gray-300 font-medium"
                            label="Set Active"
                            severity="info"
                            :icon="
                                isSettingActive
                                    ? 'pi pi-spin pi-spinner'
                                    : 'pi pi-angle-double-up'
                            "
                            @click="setAgentActive"
                        />
                        <Button
                            class="text-black border-gray-300 bg-white font-medium"
                            label="Delete"
                            :icon="
                                isDeleting
                                    ? 'pi pi-spin pi-spinner'
                                    : 'pi pi-trash'
                            "
                            @click="confirmAgentDeletion"
                        />
                    </div>
                </div>
                <DataTable
                    v-model:selection="selectedAgent"
                    selectionMode="single"
                    :value="$page.props.agents"
                    class="shadow-lg"
                    scrollable
                    scrollHeight="40rem"
                >
                    <template #empty> No agents created yet </template>
                    <Column
                        :headerStyle="{ background: tableHeadBackground }"
                        field="id"
                        header="ID"
                    ></Column>
                    <Column
                        :headerStyle="{ background: tableHeadBackground }"
                        field="name"
                        header="Name"
                    ></Column>
                    <Column
                        :headerStyle="{ background: tableHeadBackground }"
                        field="created_at"
                        header="Created At"
                    ></Column>
                    <Column
                        :headerStyle="{ background: tableHeadBackground }"
                        field="creator"
                        header="Created by"
                    ></Column>
                    <Column
                        :headerStyle="{ background: tableHeadBackground }"
                        field="active"
                        header="Active"
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
