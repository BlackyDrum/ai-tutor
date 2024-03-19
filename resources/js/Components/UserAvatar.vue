<script setup>
import Avatar from "primevue/avatar";

defineProps({
    label: String,
});
</script>

<template>
    <Avatar
        :label="!label ? $page.props.auth.user.name[0].toUpperCase() : label"
        class="bg-[#E67E22]"
        shape="circle"
    />
</template>
