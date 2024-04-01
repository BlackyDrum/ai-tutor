<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

const isDeletingConversation = ref(false);

const deleteAll = () => {
    if (isDeletingConversation.value) return;

    confirm.require({
        message: `This will delete all conversations`,
        header: "Delete conversations?",
        icon: "pi pi-info-circle",
        rejectLabel: "Cancel",
        acceptLabel: "Delete",
        rejectClass: "p-button-secondary p-button-outlined",
        acceptClass: "p-button-danger",
        acceptIcon: "pi pi-trash",
        accept: () => {
            isDeletingConversation.value = true;

            window.axios
                .delete("/conversation/all", {})
                .then((result) => {
                    if (page.url === "/") {
                        page.props.auth.history.splice(
                            0,
                            page.props.auth.history.length,
                        );
                    } else {
                        router.get("/");
                    }
                })
                .catch((error) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail:
                            error.response.data.message ?? error.response.data,
                        life: 5000,
                    });
                })
                .finally(() => {
                    isDeletingConversation.value = false;
                });
        },
    });
};
</script>

<template>
    <div
        :class="$page.props.auth.user.admin ? '-top-[118px]' : '-top-[80px]'"
        class="absolute z-10 w-full rounded-lg bg-gray-300 p-1 font-semibold dark:bg-app-dark"
    >
        <Link
            v-if="$page.props.auth.user.admin"
            href="/admin"
            class="mb-1 block flex cursor-pointer gap-4 rounded-lg p-2 hover:bg-gray-400 hover:dark:bg-app-light"
        >
            <div>
                <span class="pi pi-shield"></span>
            </div>
            <div>Admin</div>
        </Link>

        <div
            @click="deleteAll"
            class="my-1 flex cursor-pointer gap-4 rounded-lg p-2 text-red-600 hover:bg-gray-400 hover:dark:bg-app-light"
        >
            <div>
                <span class="pi pi-trash"></span>
            </div>
            <div>Delete All</div>
        </div>
        <hr class="h-px border-0 bg-gray-500/40" />
        <div
            @click="router.post('/logout')"
            class="mt-1 flex cursor-pointer gap-4 rounded-lg p-2 hover:bg-gray-400 hover:dark:bg-app-light"
        >
            <div>
                <span class="pi pi-sign-out"></span>
            </div>
            <div>Log Out</div>
        </div>
    </div>
</template>
