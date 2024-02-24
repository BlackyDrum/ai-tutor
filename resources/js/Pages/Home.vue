<script setup>
import { Head, router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Main from "@/Layouts/Main.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Prompt from "@/Components/Prompt.vue";
import LoadingDots from "@/Components/LoadingDots.vue";

const toast = useToast();

const isSendingRequest = ref(false);
const promptComponent = ref();
const mainComponent = ref();

onMounted(() => {
    promptComponent.value.focusInput();
});

const handleCreateConversation = (userMessage) => {
    if (userMessage.length === 0 || isSendingRequest.value) return;

    isSendingRequest.value = true;

    window.axios
        .post("/chat/create-conversation", {
            message: userMessage,
            collection: mainComponent.value.selectedCollection.id
        })
        .then((result) => {
            router.get(`/chat/${result.data.id}`);
        })
        .catch((error) => {
            isSendingRequest.value = false;

            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <Main ref="mainComponent">
            <div
                class="w-full flex flex-col flex-1 items-center justify-center"
            >
                <div v-if="!isSendingRequest" class="mb-4 border rounded-full">
                    <ApplicationLogo class="w-16" />
                </div>
                <div v-if="!isSendingRequest" class="text-2xl text-center font-bold">
                    How can I help you?
                </div>
                <LoadingDots v-if="isSendingRequest" />
            </div>
            <Prompt
                :sending="isSendingRequest"
                @is-submitting="handleCreateConversation"
                ref="promptComponent"
            />
        </Main>
    </AuthenticatedLayout>
</template>
