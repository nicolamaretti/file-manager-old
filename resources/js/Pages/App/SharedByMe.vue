<template>
    <AppLayout title="SharedByMe">
        <nav class="flex items-center justify-end mt-1 mb-3">
            <div class="flex">
                <DownloadFilesButton :download-file-ids="selectedFileIds"
                                     @download="onRestore"/>
                <DeleteSharedButton :file-ids="selectedFileIds"
                                    @stop-share="onRestore"/>
            </div>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
            <table class="min-w-full border shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                <thead class="bg-gray-100 border-b sm:rounded-lg">
                <tr>
                    <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="onSelectAllChange" v-model:checked="allSelected"/>
                    </th>
                    <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left w-[20px] max-w-[20px]">

                    </th>
                    <th class="px-6 py-4 text-sm font-semibold text-left text-gray-900">
                        Name
                    </th>
                    <th class="px-6 py-4 text-sm font-semibold text-left text-gray-900">
                        Shared with
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="file in allFiles.data"
                    :key="file.id"
                    :class="(selectedFiles[file.id] || allSelected) ? 'bg-blue-50' : 'bg-white'"
                    class="transition duration-300 ease-in-out border-b cursor-pointer hover:bg-blue-100"
                    @dblclick.prevent="openFolder(file)"
                    @click="toggleSelectFile(file.id)">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="onSelectCheckboxChange(file.id)"
                                  v-model="selectedFiles[file.id]"
                                  :checked="selectedFiles[file.id] || allSelected"
                                  class="mr-4"/>
                    </td>
                    <td class="px-6 py-4 max-w-[20px] text-sm font-medium text-yellow-500"
                        @click.stop.prevent="addRemoveFavorite(file.id)">
                        <svg v-if="!file.is_favorite" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path fill-rule="evenodd"
                                  d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </td>
                    <td class="flex items-center px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                        <FileIcon :file="file" class="mr-3"/>
                        {{ file.name }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                        {{ file.shared_with }}
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="allFiles.data.length === 0" class="py-8 text-sm text-center text-gray-400">
                You have no file shared
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import DownloadFilesButton from "@/Components/MyComponents/DownloadFilesButton.vue";
import DeleteSharedButton from "@/Components/MyComponents/DeleteSharedButton.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";
import Checkbox from "@/Components/Checkbox.vue";
import {computed, onUpdated, ref} from "vue";
import {router} from "@inertiajs/vue3";
import {showErrorDialog, showSuccessNotification} from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    files: Object,
});

// Computed
const selectedFileIds = computed(() => Object.entries(selectedFiles.value).filter(a => a[1]).map(a => a[0]));

// Refs
const selectedFiles = ref({});
const allSelected = ref(false);
const allFiles = ref({
    data: props.files.data,
});

// Methods
function openFolder(file = null) {
    if (!file.is_folder) {
        return;
    }

    console.log('openFolder');

    router.get(route('my-files'), {
        'folderId': file.id,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('openFolderSuccess', props.currentFolder);
        },
        onError: (errors) => {
            console.log('openFolderErrors', errors);
        }
    });
}

function onSelectAllChange() {
    for (const file of allFiles.value.data) {
        selectedFiles.value[file.id] = allSelected.value;
    }

    console.log(selectedFiles.value);
}

function toggleSelectFile(id) {
    selectedFiles.value[id] = !selectedFiles.value[id];

    onSelectCheckboxChange(id);
}

function onSelectCheckboxChange(id) {
    if (!selectedFiles.value[id]) {
        allSelected.value = false;
    } else {
        let checked = true;

        // controllo se almeno un file è false
        for (const file of allFiles.value.data) {
            if (!selectedFiles.value[file.id]) {
                checked = false;
                break;
            }
        }

        allSelected.value = checked;
    }

    console.log(selectedFileIds.value);
}

function addRemoveFavorite(id) {
    console.log('addRemoveFavorite');

    router.post(route('add-remove-favorites'),
        {
            fileId: id
        },
        {
            preserveState: true,
            only: ['files'],
            onSuccess: (data) => {
                console.log('addRemoveFavoriteSuccess', data);

                showSuccessNotification('Selected file has been added/removed to favorites');
            },
            onError: (errors) => {
                console.log('addRemoveFavoriteError', errors);

                showErrorDialog('Error trying to add/remove selected file to favorites. Please try again later.')
            },
        });
}

function onRestore() {
    allSelected.value = false;
    selectedFiles.value = {};
}

onUpdated(() => {
    allFiles.value = {
        data: props.files.data
    }
});

console.log('SharedByMe', props.files);

</script>
