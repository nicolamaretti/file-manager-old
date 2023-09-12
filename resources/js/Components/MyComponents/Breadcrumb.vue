<template>
    <nav class="flex" aria-label="Breadcrumb">
        <ol role="list" class="flex items-center space-x-1">
            <li>
                <div>
                    <NavLink :href="route('newMyFiles')" class="text-gray-400 hover:text-gray-500">
                        <HomeIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true"/>
                        <span class="ml-2">My Files</span>
                    </NavLink>
                </div>
            </li>
            <li v-for="ancestor in ancestors" :key="ancestor.id">
                <NavLink :href="route('newMyFiles', {folderId: ancestor.id})" class="text-gray-400 hover:text-gray-500 flex items-center">
                    <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-gray-400" aria-hidden="true"/>
                    <span class="ml-2 text-sm font-medium text-gray-500 hover:text-gray-700">{{ ancestor.name }}</span>
                </NavLink>
            </li>
        </ol>
    </nav>
</template>

<script setup>
import {ChevronRightIcon, HomeIcon} from '@heroicons/vue/20/solid'
import NavLink from "@/Components/NavLink.vue";

const props = defineProps({
    ancestors: Array
})
</script>
