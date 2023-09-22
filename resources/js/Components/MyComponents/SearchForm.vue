<template>
    <div class="w-[800px] h-[80px] flex items-center">
        <TextInput type="text"
                   class="block w-full mr-2"
                   v-model="search"
                   autocomplete
                   @keyup.enter.prevent="onSearch"
                   placeholder="Search for files and folders"/>
    </div>
</template>

<script setup>
import TextInput from "@/Components/TextInput.vue";
import {ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";

// Refs
const search = ref('');
const currentPage = window.location.pathname;

function onSearch() {
    console.log('onSearch', search.value);

    router.get(route('search'),
        {
            'currentPage': currentPage,
            'searchValue' : search.value
        },
        {
            only: ['folders', 'files'],
            preserveState: true,
            onSuccess: (data) => {
                console.log('onSearchSuccess', data);
            }
        });
}

</script>
