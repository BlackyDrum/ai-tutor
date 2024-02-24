<script setup>
import Toast from "primevue/toast";
import NavBar from "@/Components/NavBar.vue";
</script>

<template>
    <Toast />
    <div class="flex">
        <NavBar />
        <div class="w-full">
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
.p-message-error {
    word-break: break-word;
}
</style>
