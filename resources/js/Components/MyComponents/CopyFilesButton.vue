<template>
    <button
        class="inline-flex items-center px-4 py-2 mr-3 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700"
        type="button" @click="copy">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path
                d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"
                stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        Copy
    </button>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import { showErrorDialog, showSuccessNotification } from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    fileIds: Array,
});

const emit = defineEmits(['copy']);

// Methods
function copy() {
    console.log('Copy');

    router.post(route('copy'),
        {
            fileIds: props.fileIds,
        },
        {
            preserveState: true,
            onSuccess: (data) => {
                console.log('copySuccess', data);

                emit('copy');

                showSuccessNotification("Selected files have been successfully copied");
            },
            onError: (errors) => {
                console.log('copyError', errors.message);

                let message;

                if (errors.message) {
                    message = errors.message;
                } else {
                    message = 'Error during copy. Please try again later.';
                }

                showErrorDialog(message);
            }
        });
}
</script>
