<template>
    <button
        class="inline-flex items-center px-4 py-2 mr-3 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700"
        type="button" @click="openMoveSelection">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="w-4 h-4 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M7.848 8.25l1.536.887M7.848 8.25a3 3 0 11-5.196-3 3 3 0 015.196 3zm1.536.887a2.165 2.165 0 011.083 1.839c.005.351.054.695.14 1.024M9.384 9.137l2.077 1.199M7.848 15.75l1.536-.887m-1.536.887a3 3 0 11-5.196 3 3 3 0 015.196-3zm1.536-.887a2.165 2.165 0 001.083-1.838c.005-.352.054-.695.14-1.025m-1.223 2.863l2.077-1.199m0-3.328a4.323 4.323 0 012.068-1.379l5.325-1.628a4.5 4.5 0 012.48-.044l.803.215-7.794 4.5m-2.882-1.664A4.331 4.331 0 0010.607 12m3.736 0l7.794 4.5-.802.215a4.5 4.5 0 01-2.48-.043l-5.326-1.629a4.324 4.324 0 01-2.068-1.379M14.343 12l-2.882 1.664" />
        </svg>
        Move
    </button>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

// Props & Emit
const props = defineProps({
    fileIds: Array,
    currentFolderId: Number,
});

// Refs
const folders = ref({});

// Methods
function openMoveSelection() {
    console.log('openMoveSelection')

    router.get(route('select-folders-to-move'),
        {
            'fileIds': props.fileIds,
            'currentFolderId': props.currentFolderId
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['folders'],
            onSuccess: () => {
                console.log('openMoveSelectionSuccess');
            },
            onError: (errors) => {
                console.log('openMoveSelectionError', errors);
            }
        });
}

</script>
