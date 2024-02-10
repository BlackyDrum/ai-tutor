<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

import { Head } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import InputText from "primevue/inputtext";

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <div
            class="w-full h-screen flex flex-col justify-center items-center dark:bg-app-light dark:text-white"
        >
            <div
                class="w-full flex flex-col flex-1 items-center justify-center"
            >
                <div class="mb-4 border rounded-full">
                    <ApplicationLogo class="w-16" />
                </div>
                <div class="text-2xl font-bold">How can I help you?</div>
            </div>
            <div class="w-full text-center">
                <InputText
                    class="w-1/2 h-14 rounded-lg dark:text-white dark:bg-app-light max-xl:w-3/4"
                    placeholder="Type your Message..."
                />
            </div>
            <div class="my-2 text-center text-xs text-gr">
                {{appName}} can make mistakes. Please contact <a class="underline" href="mailto:remmy@fh-aachen.de">remmy@fh-aachen.de</a>
                for technical assistance.
            </div>
        </div>
    </AuthenticatedLayout>
</template>
