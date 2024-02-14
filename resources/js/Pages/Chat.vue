<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

import {Head, router, usePage} from "@inertiajs/vue3";
import InputText from "primevue/inputtext";
import ScrollPanel from "primevue/scrollpanel";
import {ref} from "vue";

defineProps({
    messages: Array
})

const appName = import.meta.env.VITE_APP_NAME;

const page = usePage();

const userMessage = ref("");
const isSendingRequest = ref(false);

const handleCreateConversation = () => {
    isSendingRequest.value = true;
    window.axios.post('/chat/chat-agent', {
        message: userMessage.value,
        conversation_id: page.url.slice(page.url.lastIndexOf('/') + 1)
    })
        .then(result => {
            router.reload();
        })
        .catch(error => {
            console.log(error)
        })
        .finally(() => {
            userMessage.value = "";
            isSendingRequest.value = false;
        })
}

</script>

<template>
    <AuthenticatedLayout>
        <Head title="Chat" />

        <div
            class="w-full h-dvh flex flex-col justify-center items-center dark:bg-app-light dark:text-white"
        >
            <div class="flex-1 overflow-y-auto py-4 px-6">
                <div v-for="message in messages">
                    <div class="max-w-[48rem] max-xl:max-w-[30rem] max-lg:max-w-[20rem] min-w-[48rem] max-xl:min-w-[30rem] max-lg:min-w-[20rem]">
                        <div class="flex flex-col mt-6">
                            <div class="font-bold">
                                You
                            </div>
                            <div>
                                {{message.user_message}}
                            </div>
                        </div>
                        <div class="flex flex-col mt-6">
                            <div class="font-bold">
                                {{appName}}
                            </div>
                            <div>
                                {{message.agent_message}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full text-center">
                <InputText
                    v-model="userMessage"
                    @keydown.enter="handleCreateConversation"
                    :disabled="isSendingRequest"
                    class="w-1/2 h-14 rounded-lg dark:text-white dark:bg-app-light max-xl:w-3/4"
                    placeholder="Type your Message..."
                />
            </div>
            <div class="my-2 text-center text-xs text-gr">
                {{ appName }} can make mistakes. Please contact
                <a class="underline" href="mailto:remmy@fh-aachen.de"
                >remmy@fh-aachen.de</a
                >
                for technical assistance.
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style>
/* width */
::-webkit-scrollbar {
    width: 8px;
}

/* Track */
::-webkit-scrollbar-track {
    background: transparent;
}

/* Handle */
::-webkit-scrollbar-thumb {
    background: #ffffff;
    border-radius: 4px; /* Adjust the radius to your preference */
}
</style>
