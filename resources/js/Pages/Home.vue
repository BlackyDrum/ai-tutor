<script setup>
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "primevue/usetoast";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Main from "@/Layouts/Main.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Prompt from "@/Components/Prompt.vue";

const toast = useToast();

const isSendingRequest = ref(false);

const handleCreateConversation = (userMessage) => {
    if (userMessage.length === 0 || isSendingRequest.value) return;

    isSendingRequest.value = true;

    window.axios
        .post("/create-conversation", {
            message: userMessage,
        })
        .then((result) => {
            router.get(`/chat/${result.data.id}`);
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message || error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            isSendingRequest.value = false;
        });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <Main>
            <div
                class="w-full flex flex-col flex-1 items-center justify-center"
            >
                <div class="mb-4 border rounded-full">
                    <ApplicationLogo class="w-16" />
                </div>
                <div class="text-2xl font-bold">How can I help you?</div>
            </div>
            <Prompt
                :sending="isSendingRequest"
                @isSubmitting="handleCreateConversation"
            />
        </Main>
    </AuthenticatedLayout>
</template>
