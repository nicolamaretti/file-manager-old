<template>
    <nav class="min-w-[200px]">
        <Link :href="route('new')" class="h-[80px] px-3 flex items-center gap-2 font-medium">
            <ApplicationMark
                class="block h-9 w-auto fill-current text-gray-800"
            />
            FileManager
        </Link>

        <div class="px-3">
            <CreateNewDropdown/>

            <div class="py-3">
                <NavLink :href="route('new')" :active="$page.props.route_name === 'new'">My Files</NavLink>
                <NavLink :href="route('new')" :active="$page.props.route_name === 'new'">Shared With Me</NavLink>
                <NavLink :href="route('new')" :active="$page.props.route_name === 'new'">Shared By Me</NavLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
import {Link} from "@inertiajs/vue3"
import ApplicationMark from "@/Components/ApplicationMark.vue";
import CreateNewDropdown from "@/Components/ExtraComponents/CreateNewDropdown.vue";
import NavLink from "@/Components/ExtraComponents/NavLink.vue";
</script>
