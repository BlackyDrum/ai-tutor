<script setup>
import Toast from "primevue/toast";
import NavBar from "@/Components/NavBar.vue";
import Terms from "@/Components/Terms.vue";
</script>

<template>
    <Toast />
    <Terms />
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
.p-toast {
    max-width: calc(100vw - 40px);
    word-break: break-word;
}
</style>
