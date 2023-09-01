<template>
    <div class="w-[600px] h-[80px] flex items-center">
        <TextInput type="text"
                   class="block w-full mr-2"
                   v-model="search"
                   autocomplete
                   @keyup.enter.prevent="onSearch()"
                   placeholder="Search for files and folders"/>
    </div>
</template>

<script setup>
import TextInput from "@/Components/TextInput.vue";
import {ref} from "vue";

const search = ref('');

function onSearch() {
    console.log('onSearch');
}
</script>
