<script setup>
import { Link, router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

import Tag from "primevue/tag";

const toast = useToast();
</script>

<template>
    <div
        :class="$page.props.auth.user.admin ? '-top-[140px]' : '-top-[100px]'"
        class="absolute z-10 mb-2 w-full rounded-lg bg-app-dark p-1"
    >
        <Link
            v-if="$page.props.auth.user.admin"
            href="/admin"
            class="mb-1 block flex cursor-pointer gap-4 rounded-lg p-2 hover:bg-app-light"
        >
            <div>
                <span class="pi pi-shield"></span>
            </div>
            <div>Admin</div>
        </Link>

        <div
            class="mb-1 flex cursor-not-allowed gap-4 rounded-lg p-2 opacity-30"
        >
            <div class="self-center">
                <span class="pi pi-cog"></span>
            </div>
            <div class="flex w-full">
                <div class="self-center">Settings</div>
                <Tag class="ml-auto" value="Coming soon" severity="info"></Tag>
            </div>
        </div>
        <hr class="h-px border-0 bg-gray-500/40" />
        <div
            @click="router.post('/logout')"
            class="mt-1 flex cursor-pointer gap-4 rounded-lg p-2 hover:bg-app-light"
        >
            <div>
                <span class="pi pi-sign-out"></span>
            </div>
            <div>Log Out</div>
        </div>
    </div>
</template>
