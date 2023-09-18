<template>
    <PrimaryButton @click="onDownloadClick">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
             xmlns="http://www.w3.org/2000/svg">
            <path
                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
                stroke-linecap="round"
                stroke-linejoin="round"/>
        </svg>
        Download
    </PrimaryButton>
</template>

<script setup>
// Imports
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {router} from "@inertiajs/vue3";
import {showErrorDialog, showSuccessNotification} from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    downloadFolderIds: Array,
    downloadFileIds: Array,
});

const emit = defineEmits(['download']);

// Methods
function onDownloadClick() {
    if (!props.downloadFileIds.length && !props.downloadFolderIds.length) {
        showErrorDialog('Please select at least one file to download');

        return;
    } else {
        console.log('Download');

        router.get(route('download'),
            {
                downloadFileIds: props.downloadFileIds,
                downloadFolderIds: props.downloadFolderIds
            },
            {
                onSuccess: (data) => {
                    console.log('downloadSuccess', data);
                    emit('download');
                    showSuccessNotification('Files downloaded successfully');
                },
                onError: (errors) => {
                    console.log('downloadError', errors);

                    let message;

                    if (errors.message) {
                        message = errors.message;
                    } else {
                        message = 'Error during download. Please try again later.';
                    }

                    showErrorDialog(message);
                }
            });
    }
}
</script>
