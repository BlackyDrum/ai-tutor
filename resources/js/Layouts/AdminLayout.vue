<script setup>
import AdminNavBar from "@/Components/AdminNavBar.vue";

import Toast from "primevue/toast";
import ConfirmDialog from 'primevue/confirmdialog';
</script>

<template>
    <Toast />
    <ConfirmDialog />
    <div class="flex bg-admin-light">
        <AdminNavBar />

        <div class="w-full min-w-0">
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
