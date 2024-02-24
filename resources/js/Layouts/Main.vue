<script setup>
import { usePage } from "@inertiajs/vue3";
import { onMounted, ref, watch } from "vue";

import Dropdown from "primevue/dropdown";

const page = usePage();

const selectedCollection = ref(null);

onMounted(() => {
    selectedCollection.value =
        page.props.collections.find(
            (collection) =>
                collection.id === Number.parseInt(window.localStorage.getItem("collection")),
        ) ?? page.props.collections[0];
});

watch(selectedCollection, () => {
    window.localStorage.setItem("collection", selectedCollection.value.id);
});

defineExpose({
    selectedCollection
})
</script>

<template>
    <div
        class="w-full h-dvh flex flex-col justify-center items-center dark:bg-app-light dark:text-white"
    >
        <Dropdown
            id="collections"
            class="mt-2 ml-2 text-center min-w-[10rem] max-w-[15rem] mr-auto max-md:ml-auto dark:hover:bg-app-dark dark:bg-app-light dark:text-white"
            v-model="selectedCollection"
            :options="$page.props.collections"
            optionLabel="name"
        />
        <slot />
    </div>
</template>

<style>
@media (prefers-color-scheme: dark) {
   #collections .p-inputtext {
        color: white;
    }
}

.p-dropdown {
    border: none;
}
</style>
