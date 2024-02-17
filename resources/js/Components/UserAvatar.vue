<script setup>
import Avatar from "primevue/avatar";
</script>

<template>
    <Avatar
        :label="$page.props.auth.user.name[0]"
        class="bg-[#E67E22]"
        shape="circle"
    />
</template>
