<script setup>
import { usePage } from "@inertiajs/vue3";
import { ref } from "vue";

import Dialog from "primevue/dialog";
import Button from "primevue/button";

defineProps(["message"]);

const page = usePage();

const data = {
    "Message ID": "id",
    "Language Model": "openai_language_model",
    "Prompt Tokens": "prompt_tokens",
    "Completion Tokens": "completion_tokens",
    "Created At": "created_at",
};
const showDialog = ref(false);
</script>

<template>
    <Dialog
        :visible="message"
        :closable="false"
        :draggable="false"
        modal
        header="Inspect Message"
        class="min-w-[25%] max-w-[95%] break-words xl:max-w-[35%]"
    >
        <div v-for="(key, value) in data" class="my-1">
            <strong>{{ value }}:</strong>
            {{
                (key === "created_at"
                    ? new Date(message[key]).toLocaleString()
                    : message[key]) ?? "null"
            }}
        </div>

        <div class="flex justify-end">
            <Button label="Close" @click="$emit('close')" />
        </div>
    </Dialog>
</template>
