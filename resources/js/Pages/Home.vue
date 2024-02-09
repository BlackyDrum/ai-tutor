<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

import { Head } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <div
            class="w-full h-screen flex flex-col justify-center items-center dark:bg-app-gray-light dark:text-white"
        >
            <div class="mb-4 border rounded-full">
                <ApplicationLogo class="w-16" />
            </div>
            <div class="text-2xl font-bold">How can I help you?</div>
        </div>
    </AuthenticatedLayout>
</template>
