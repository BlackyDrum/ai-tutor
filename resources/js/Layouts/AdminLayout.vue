<script setup>
import AdminNavBar from "@/Components/AdminNavBar.vue";

import Toast from "primevue/toast";
</script>

<template>
    <Toast />
    <div class="flex bg-admin-light">
        <AdminNavBar />

        <div class="w-full">
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
