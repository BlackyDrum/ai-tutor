<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "primevue/usetoast";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Main from "@/Layouts/Main.vue";
import Prompt from "@/Components/Prompt.vue";

defineProps({
    messages: Array,
    conversation_id: String,
});

const appName = import.meta.env.VITE_APP_NAME;

const page = usePage();
const toast = useToast();

const isSendingRequest = ref(false);

const handleCreateConversation = userMessage => {
    if (userMessage.length === 0 || isSendingRequest.value) return;

    isSendingRequest.value = true;

    window.axios
        .post("/chat/chat-agent", {
            message: userMessage,
            conversation_id: page.props.conversation_id,
        })
        .then((result) => {
            router.reload({
                onFinish: () =>
                    (document.getElementById("scroll-container").scrollTop =
                        Number.MAX_SAFE_INTEGER),
            });
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message,
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
        <Head title="Chat" />

        <Main>
            <div id="scroll-container" class="flex-1 overflow-y-auto py-4 px-6">
                <div v-for="message in messages">
                    <div
                        class="max-w-[48rem] max-xl:max-w-[30rem] max-lg:max-w-[20rem] min-w-[48rem] max-xl:min-w-[30rem] max-lg:min-w-[20rem]"
                    >
                        <div class="flex flex-col mt-6">
                            <div class="font-bold">You</div>
                            <div>
                                {{ message.user_message }}
                            </div>
                        </div>
                        <div class="flex flex-col mt-6">
                            <div class="font-bold">
                                {{ appName }}
                            </div>
                            <div>
                                {{ message.agent_message }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Prompt :sending="isSendingRequest" @isSubmitting="handleCreateConversation"/>
        </Main>
    </AuthenticatedLayout>
</template>
