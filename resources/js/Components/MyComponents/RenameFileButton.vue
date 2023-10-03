<template>
    <button
        class="mr-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700"
        type="button"
        @click="showModal">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
             xmlns="http://www.w3.org/2000/svg">
            <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" stroke-linecap="round"
                  stroke-linejoin="round"/>
        </svg>
        Rename
    </button>

    <RenameFileModal v-model="modal"
                 :file-id="fileId"
                 @rename="onRename"/>
</template>

<script setup>
import {ref} from "vue";
import RenameFileModal from "@/Components/MyComponents/RenameFileModal.vue";

const props = defineProps({
    fileId: Number
});

const emit = defineEmits(['restore']);

const modal = ref(false);

// Methods
function showModal() {
    modal.value = true;
}

function onRename() {
    emit('restore');
}
</script>
