<template>
    <button
        class="inline-flex items-center px-4 py-2 mr-3 text-sm font-medium text-blue-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700"
        type="button" @click="onClick">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="w-4 h-4 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-6 6m0 0l-6-6m6 6V9a6 6 0 0112 0v3" />
        </svg>
        Restore
    </button>

    <ConfirmationDialog :show="showRestoreDialog" message="Are you sure you want to restore selected files?"
        @cancel="onRestoreCancel" @confirm="onRestoreConfirm" />
</template>

<script setup>
import { ref } from "vue";
import ConfirmationDialog from "@/Components/MyComponents/ConfirmationDialog.vue";
import { router } from "@inertiajs/vue3";
import { showErrorDialog, showSuccessNotification } from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    fileIds: Array,
});

const emit = defineEmits(['restore']);

// Refs
const showRestoreDialog = ref(false);

// Methods
function onClick() {
    if (!props.fileIds.length) {
        showErrorDialog('Please select at least one file to restore');

        return;
    }

    showRestoreDialog.value = true;
}

function onRestoreCancel() {
    showRestoreDialog.value = false;
}

function onRestoreConfirm() {
    console.log('Restore');

    router.put(route('restore', {
        fileIds: props.fileIds
    }), {
        preserveState: true,
        preserveScroll: true,
        only: ['files'],
        onSuccess: (data) => {
            console.log('onRestoreSuccess', data);

            showRestoreDialog.value = false;

            emit('restore');

            showSuccessNotification('Selected files have been restored successfully');
        },
        onError: (errors) => {
            console.log('onRestoreError', errors);

            let message;

            if (errors.message) {
                message = errors.message;
            } else {
                message = 'Error during restore. Please try again later.';
            }

            showErrorDialog(message);
        }
    });
}
</script>