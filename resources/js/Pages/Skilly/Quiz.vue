<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Main from "@/Layouts/Main.vue";

import Dropdown from "primevue/dropdown";
import Button from "primevue/button";

const toast = useToast();
const page = usePage();

const settings = ref({
    difficulty: null,
    topic: null,
});

const generate = () => {
    if (!settings.value.topic || !settings.value.difficulty) {
        return;
    }

    window.axios.post('/skilly/quiz/create', {
        ...settings.value,
    })
        .then((response) => {

        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />

        <Main ref="mainComponent">
            <div>
                <h1
                    class="q-animate-gradient bg-gradient-to-r from-emerald-500 via-pink-400 to-blue-500 bg-clip-text p-3 text-center text-8xl font-bold text-transparent max-md:text-4xl"
                >
                    Skilly AI Quiz
                </h1>

                <form class="mx-auto mt-14 flex w-[85%] flex-col gap-4">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-6">
                        <div class="flex flex-col">
                            <Dropdown
                                v-model="settings.topic"
                                :options="page.props.topics"
                                placeholder="Topic"
                            />
                        </div>
                        <div class="flex flex-col">
                            <Dropdown
                                v-model="settings.difficulty"
                                :options="page.props.difficulties"
                                placeholder="Difficulty"
                            />
                        </div>
                    </div>
                    <div class="mx-auto mt-8">
                        <Button label="Generate Quiz" @click="generate" />
                    </div>
                </form>
            </div>
        </Main>
    </AuthenticatedLayout>
</template>
