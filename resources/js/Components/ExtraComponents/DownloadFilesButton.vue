<template>
  <PrimaryButton @click="onDownloadClick">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
         class="w-4 h-4 mr-2">
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
    </svg>
    Download
  </PrimaryButton>
</template>

<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {emitter, FILE_DOWNLOAD_STARTED} from "@/event-bus.js";

const props = defineProps({
    downloadFolderIds: {
        type: Array,
        required: false,
    },
    downloadFileIds: {
        type: Array,
        required: false,
    },
});

const emit = defineEmits(['download']);

function onDownloadClick() {
    if (!props.downloadFileIds.length && !props.downloadFolderIds.length) {
        // showErrorDialog('Please select at least one file to delete');

        return;
    } else {
        emitter.emit(FILE_DOWNLOAD_STARTED, {
            'downloadFolderIds': props.downloadFolderIds,
            'downloadFileIds': props.downloadFileIds
        });
    }
}
</script>
