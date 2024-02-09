<script setup></script>

<template>
    <div class="flex">
        <div class="h-screen w-[260px] flex-shrink-0 bg-black dark:text-white max-sm:hidden">
            <nav class="h-full">

            </nav>
        </div>
        <div class="w-full">
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
