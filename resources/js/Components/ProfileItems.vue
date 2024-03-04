<script setup>
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "primevue/usetoast";

const isCheckingStatus = ref(false);

const toast = useToast();

const checkChromaDBStatus = () => {
    if (isCheckingStatus.value) return;

    isCheckingStatus.value = true;
    window.axios
        .get("/chroma/status")
        .then((result) => {
            toast.add({
                severity: "info",
                summary: "Info",
                detail: result.data.message,
                life: 5000,
            });
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 10000,
            });
        })
        .finally(() => {
            isCheckingStatus.value = false;
        });
};
</script>

<template>
    <div
        :class="$page.props.auth.user.admin ? '-top-[175px]' : '-top-[100px]'"
        class="absolute w-full z-10 p-1 mb-2 rounded-lg bg-app-dark"
    >
        <Link
            v-if="$page.props.auth.user.admin"
            href="/admin"
            class="block flex gap-4 p-2 mb-1 cursor-pointer rounded-lg hover:bg-app-light"
        >
            <div>
                <span class="pi pi-user"></span>
            </div>
            <div>Admin</div>
        </Link>
        <div
            v-if="$page.props.auth.user.admin"
            @click="checkChromaDBStatus"
            class="flex gap-4 p-2 mt-1 cursor-pointer rounded-lg hover:bg-app-light"
        >
            <div>
                <span
                    :class="
                        isCheckingStatus
                            ? 'pi pi-spin pi-spinner'
                            : 'pi pi-database'
                    "
                ></span>
            </div>
            <div>Check Sync</div>
        </div>

        <div
            class="flex gap-4 p-2 mb-1 cursor-not-allowed opacity-30 rounded-lg"
        >
            <div>
                <span class="pi pi-cog"></span>
            </div>
            <div>Settings</div>
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
