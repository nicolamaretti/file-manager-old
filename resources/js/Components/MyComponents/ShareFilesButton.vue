<template>
    <button @click="onClick"
        class="inline-flex items-center px-4 py-2 mr-3 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="w-4 h-4 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
        </svg>
        Share
    </button>

    <ShareFilesModal v-model="showShareModal" :file-ids="fileIds" @restore="onRestore" />
</template>

<script setup>
import { ref } from "vue";
import ShareFilesModal from "@/Components/MyComponents/ShareFilesModal.vue";
import { showErrorDialog } from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    fileIds: Array,
});

const emit = defineEmits(['restore']);

// Refs
const showShareModal = ref(false);

// Methods
function onClick() {
    if (!props.fileIds.length) {
        showErrorDialog('Please select at least one file to share');

        return;
    }

    showShareModal.value = true;
}

function onRestore() {
    emit('restore');
}
</script>
