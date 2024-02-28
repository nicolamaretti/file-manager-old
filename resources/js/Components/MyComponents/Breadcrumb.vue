<template>
    <nav class="flex" aria-label="Breadcrumb">
        <ol role="list" class="flex items-center space-x-1">
            <li>
                <NavLink :href="route('my-files')" class="text-gray-400 hover:text-gray-500">
                    <HomeIcon class="flex-shrink-0 w-5 h-5" aria-hidden="true" />
                </NavLink>
            </li>
            <li v-for="ancestor in ancestors" :key="ancestor.id">
                <NavLink :href="route('my-files', { folderId: ancestor.id })"
                    class="flex items-center text-gray-400 hover:text-gray-500">
                    <ChevronRightIcon class="flex-shrink-0 w-5 h-5 text-gray-400" aria-hidden="true" />
                    <span class="ml-2 text-sm font-medium text-gray-500 hover:text-gray-700">{{ ancestor.name }}</span>
                </NavLink>
            </li>
        </ol>
    </nav>
</template>

<script setup>
import { ChevronRightIcon, HomeIcon } from '@heroicons/vue/20/solid'
import NavLink from "@/Components/NavLink.vue";

const props = defineProps({
    ancestors: Array
});

console.log('Breadcrumb', props.ancestors);

</script>
