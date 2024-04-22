<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, onBeforeMount, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

const isDeletingConversation = ref(false);
const currentColorScheme = ref({});

onBeforeMount(() => {
    let colorScheme = window.localStorage.getItem("color_scheme");

    if (!colorScheme) {
        window.localStorage.setItem("color_scheme", "light");
        colorScheme = "light";
    }

    switch (colorScheme) {
        case "light":
            currentColorScheme.value = { icon: "pi pi-sun", label: "Light" };
            break;
        case "dark":
            currentColorScheme.value = { icon: "pi pi-moon", label: "Dark" };
            break;
    }

    document.getElementById("body").classList.add(colorScheme);
});

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

const toggleColorScheme = () => {
    switch (currentColorScheme.value.label) {
        case "Light":
            currentColorScheme.value = { icon: "pi pi-moon", label: "Dark" };
            break;
        case "Dark":
            currentColorScheme.value = { icon: "pi pi-sun", label: "Light" };
            break;
    }

    window.localStorage.setItem(
        "color_scheme",
        currentColorScheme.value.label.toLowerCase(),
    );

    const body = document.getElementById("body");
    const newColorScheme = window.localStorage.getItem("color_scheme");

    if (body.classList.contains("dark") && newColorScheme === "light") {
        body.classList.remove("dark");
    } else {
        body.classList.remove("light");
    }

    document
        .getElementById("body")
        .classList.add(window.localStorage.getItem("color_scheme"));
};
</script>

<template>
    <div
        :class="$page.props.auth.user.admin ? '-top-[160px]' : '-top-[120px]'"
        class="absolute z-10 w-full rounded-lg bg-gray-200 p-1 font-semibold dark:bg-app-dark"
    >
        <Link
            v-if="$page.props.auth.user.admin"
            href="/admin"
            class="mb-1 block flex cursor-pointer gap-4 rounded-lg p-2 hover:bg-gray-300 hover:dark:bg-app-light"
        >
            <div>
                <span class="pi pi-shield"></span>
            </div>
            <div>Admin</div>
        </Link>

        <div
            @click="toggleColorScheme"
            class="my-1 flex cursor-pointer gap-4 rounded-lg p-2 hover:bg-gray-300 hover:dark:bg-app-light"
        >
            <div>
                <span :class="currentColorScheme.icon"></span>
            </div>
            <div>{{ currentColorScheme.label }}</div>
        </div>

        <div
            @click="deleteAll"
            class="my-1 flex cursor-pointer gap-4 rounded-lg p-2 text-red-600 hover:bg-gray-300 hover:dark:bg-app-light"
        >
            <div>
                <span class="pi pi-trash"></span>
            </div>
            <div>Delete All</div>
        </div>
        <hr class="h-px border-0 bg-gray-500/40" />
        <div
            @click="router.post('/logout')"
            class="mt-1 flex cursor-pointer gap-4 rounded-lg p-2 hover:bg-gray-300 hover:dark:bg-app-light"
        >
            <div>
                <span class="pi pi-sign-out"></span>
            </div>
            <div>Log Out</div>
        </div>
    </div>
</template>
