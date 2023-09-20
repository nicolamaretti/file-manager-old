<template>
    <nav class="min-w-[200px]">
        <Link :href="route('my-files')" class="h-[80px] px-3 flex items-center gap-2 font-medium">
            <ApplicationMark
                class="block h-9 w-auto fill-current text-gray-800"
            />
            FileManager
        </Link>

        <div class="ml-3">
            <CreateNewDropdown/>

            <div class="py-3">
                <NavLink :href="route('my-files')" :active="route().current('my-files')">My Files</NavLink>
                <NavLink :href="route('favourites')" :active="route().current('favourites')">Favourites</NavLink>
                <NavLink :href="route('shared-with-me')" :active="route().current('shared-with-me')">Shared With Me</NavLink>
                <NavLink :href="route('shared-by-me')" :active="route().current('shared-by-me')">Shared By Me</NavLink>
                <NavLink :href="route('__old-my-files')" :active="route().current('__old-my-files')">__oldVersion</NavLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
import {Link, usePage} from "@inertiajs/vue3"
import ApplicationMark from "@/Components/ApplicationMark.vue";
import CreateNewDropdown from "@/Components/ExtraComponents/CreateNewDropdown.vue";
import NavLink from "@/Components/ExtraComponents/NavLink.vue";
</script>
