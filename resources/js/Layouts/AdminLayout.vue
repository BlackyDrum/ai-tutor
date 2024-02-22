<script setup>
import AdminNavBar from "@/Components/AdminNavBar.vue";
</script>

<template>
    <div class="flex bg-admin-light">
        <AdminNavBar />

        <div class="w-full">
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
