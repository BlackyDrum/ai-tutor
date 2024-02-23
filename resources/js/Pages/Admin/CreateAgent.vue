<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

import AdminLayout from "@/Layouts/AdminLayout.vue";

import InputText from "primevue/inputtext";
import FloatLabel from "primevue/floatlabel";
import Textarea from "primevue/textarea";
import Button from "primevue/button";

const toast = useToast();

const form = useForm({
    name: null,
    context: null,
    first_message: null,
    response_shape: null,
    instructions: null,
});

const items = [
    { label: "Name", attribute: "name", textarea: false },
    { label: "Context", attribute: "context", textarea: false },
    { label: "First Message", attribute: "first_message", textarea: false },
    { label: "Response Shape", attribute: "response_shape", textarea: false },
    { label: "Instructions", attribute: "instructions", textarea: true },
];

const handleForm = () => {
    form.post("/admin/agents/create", {
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: "New agent created",
                life: 5000,
            });

            form.reset();
        },
        onError: (error) => {
            if (typeof error.message !== "undefined") {
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: error.message,
                    life: 5000,
                });
            }
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Create Agent" />

        <div
            class="h-dvh w-full p-5 flex flex-wrap items-center justify-center bg-admin-light overflow-y-auto"
        >
            <div
                class="w-full flex flex-wrap gap-7 max-w-[48rem] max-xl:max-w-[40rem] max-lg:max-w-[35rem] max-md:max-w-[25rem] max-md:max-w-[20rem]"
            >
                <div class="w-full" v-for="item in items">
                    <FloatLabel class="w-full">
                        <InputText
                            class="w-full"
                            :disabled="form.processing"
                            v-model="form[item.attribute]"
                            v-if="!item.textarea"
                        />
                        <Textarea
                            class="w-full resize-none"
                            rows="15"
                            :disabled="form.processing"
                            v-model="form[item.attribute]"
                            v-else
                        />
                        <label>{{ item.label }}</label>
                    </FloatLabel>
                    <small class="text-red-600">
                        {{ form.errors[item.attribute] }}
                    </small>
                </div>

                <Button
                    @click="handleForm"
                    :icon="
                        form.processing ? 'pi pi-spin pi-spinner' : 'pi pi-save'
                    "
                    :disabled="form.processing"
                    label="Submit"
                    class="ml-auto"
                />
            </div>
        </div>
    </AdminLayout>
</template>
