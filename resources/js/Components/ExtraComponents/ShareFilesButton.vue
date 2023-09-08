<template>
    <button @click="onClick"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-2">
            <path fill-rule="evenodd"
                  d="M15.75 4.5a3 3 0 11.825 2.066l-8.421 4.679a3.002 3.002 0 010 1.51l8.421 4.679a3 3 0 11-.729 1.31l-8.421-4.678a3 3 0 110-4.132l8.421-4.679a3 3 0 01-.096-.755z"
                  clip-rule="evenodd"/>
        </svg>
        Share
    </button>

    <ShareFilesModal v-model="showShareModal"
                     :share-file-ids="shareFileIds"
                     :share-folder-ids="shareFolderIds"
                     @share="onShare"/>
</template>

<script setup>
import {ref} from "vue";
import ShareFilesModal from "@/Components/ExtraComponents/ShareFilesModal.vue";

const props = defineProps({
    shareFolderIds: {
        type: Array,
        required: false,
    },
    shareFileIds: {
        type: Array,
        required: false,
    },
});

const emit = defineEmits(['restore']);

const showShareModal = ref(false);

function onClick() {
    if (!props.shareFileIds.length && !props.shareFolderIds.length) {
        return;
    }

    showShareModal.value = true;
}

function onShare() {
    emit('restore');
}
</script>
