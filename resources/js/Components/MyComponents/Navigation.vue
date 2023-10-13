<template>
    <nav class="min-w-[200px]">
        <Link :href="route('my-files')" class="h-[80px] px-3 flex items-center gap-2 font-medium">
            <ApplicationMark
                class="block w-auto text-gray-800 fill-current h-9"
            />
            FileManager
        </Link>

        <div class="ml-3">
            <CreateNewDropdown/>

            <div class="py-3">
                <NavLink :href="route('my-files')" :active="route().current('my-files')">My Files</NavLink>
                <NavLink :href="route('favorites')" :active="route().current('favorites')">Favorites</NavLink>
                <NavLink :href="route('shared-with-me')" :active="route().current('shared-with-me')">Shared With Me</NavLink>
                <NavLink :href="route('shared-by-me')" :active="route().current('shared-by-me')">Shared By Me</NavLink>
                <NavLink :href="route('trash')" :active="route().current('trash')">Trash</NavLink>
                <NavLink :href="route('__old-my-files')" :active="route().current('__old-my-files')">__oldVersion</NavLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
import {Link} from "@inertiajs/vue3"
import ApplicationMark from "@/Components/ApplicationMark.vue";
import CreateNewDropdown from "@/Components/MyComponents/CreateNewDropdown.vue";
import NavLink from "@/Components/MyComponents/NavLink.vue";
</script>
