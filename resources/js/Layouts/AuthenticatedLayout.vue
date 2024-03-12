<script setup>
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";

import NavBar from "@/Components/NavBar.vue";
import Terms from "@/Components/Terms.vue";
</script>

<template>
    <Toast />
    <Terms />
    <ConfirmDialog class="max-w-[95%]" :draggable="false" />
    <div class="flex">
        <NavBar />
        <div class="min-w-0 flex-1">
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
