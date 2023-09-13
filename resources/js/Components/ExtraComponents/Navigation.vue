<template>
    <nav class="min-w-[200px]">
        <Link :href="route('newMyFiles')" class="h-[80px] px-3 flex items-center gap-2 font-medium">
            <ApplicationMark
                class="block h-9 w-auto fill-current text-gray-800"
            />
            FileManager
        </Link>

        <div class="ml-3">
            <CreateNewDropdown/>

            <div class="py-3">
                <NavLink :href="route('newMyFiles')" :active="route().current('newMyFiles')">My Files</NavLink>
                <NavLink :href="route('favourites')" :active="route().current('favourites')">Favourites</NavLink>
                <NavLink :href="route('newSharedWithMe')" :active="route().current('newSharedWithMe')">Shared With Me</NavLink>
                <NavLink :href="route('newSharedByMe')" :active="route().current('newSharedByMe')">Shared By Me</NavLink>
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
