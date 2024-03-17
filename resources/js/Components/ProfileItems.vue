<script setup>
import { Link, router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

import Tag from "primevue/tag";

const toast = useToast();
</script>

<template>
    <div
        :class="$page.props.auth.user.admin ? '-top-[140px]' : '-top-[100px]'"
        class="absolute w-full z-10 p-1 mb-2 rounded-lg bg-app-dark"
    >
        <Link
            v-if="$page.props.auth.user.admin"
            href="/admin"
            class="block flex gap-4 p-2 mb-1 cursor-pointer rounded-lg hover:bg-app-light"
        >
            <div>
                <span class="pi pi-shield"></span>
            </div>
            <div>Admin</div>
        </Link>

        <div
            class="flex gap-4 p-2 mb-1 cursor-not-allowed opacity-30 rounded-lg"
        >
            <div class="self-center">
                <span class="pi pi-cog"></span>
            </div>
            <div class="flex w-full">
                <div class="self-center">Settings</div>
                <Tag class="ml-auto" value="Coming soon" severity="info"></Tag>
            </div>
        </div>
        <hr class="border-0 h-px bg-gray-500/40" />
        <div
            @click="router.post('/logout')"
            class="flex gap-4 p-2 mt-1 cursor-pointer rounded-lg hover:bg-app-light"
        >
            <div>
                <span class="pi pi-sign-out"></span>
            </div>
            <div>Log Out</div>
        </div>
    </div>
</template>
