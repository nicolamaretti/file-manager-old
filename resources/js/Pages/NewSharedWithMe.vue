<template>
    <AppLayout_new title="NewSharedWithMe">
        <nav class="flex justify-end mb-3 mt-1">
            <div class="flex">
                <DownloadFilesButton :download-file-ids="selectedFileIds"
                                     :download-folder-ids="selectedFolderIds"
                                     @download="onRestore"/>
                <DeleteFilesButton />
            </div>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
            <table class="min-w-full shadow ring-1 ring-black ring-opacity-5 border sm:rounded-lg">
                <thead class="bg-gray-100 border-b sm:rounded-lg">
                <tr>
                    <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="onSelectAllChange()" v-model:checked="allSelected"/>
                    </th>
                    <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left w-[20px] max-w-[20px]">

                    </th>
                    <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left">
                        Name
                    </th>
                    <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left">
                        Owner
                    </th>
                </tr>
                </thead>
                <tbody>
                <!-- 1) visualizzazione cartelle -->
                <tr v-if="folders"
                    v-for="folder in folders"
                    :key="folder.id"
                    :class="(selectedFolders[folder.id] || allSelected) ? 'bg-blue-50' : 'bg-white'"
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    @click="$event => toggleSelectFolder(folder.id)">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="$event => onSelectFolderCheckboxChange(folder.id)"
                                  v-model="selectedFolders[folder.id]"
                                  :checked="selectedFolders[folder.id] || allSelected"
                                  class="mr-4"/>
                    </td>
                    <td class="px-6 py-4 max-w-[20px] text-sm font-medium text-yellow-500"
                        @click.stop.prevent="addRemoveFavouriteFolder(folder.id)">
                        <svg v-if="!folder.is_favourite" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                        <FolderIcon class="mr-3"/>
                        {{ folder.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ folder.owner }}
                    </td>
                </tr>

                <!-- 2) visualizzazione file -->
                <tr v-if="files"
                    v-for="file in files"
                    :key="file.id"
                    :class="(selectedFiles[file.id] || allSelected) ? 'bg-blue-50' : 'bg-white'"
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    @click="$event => toggleSelectFile(file.id)">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="$event => onSelectFileCheckboxChange(file.id)"
                                  v-model="selectedFiles[file.id]"
                                  :checked="selectedFiles[file.id] || allSelected"
                                  class="mr-4"/>
                    </td>
                    <td class="px-6 py-4 max-w-[20px] text-sm font-medium text-yellow-500"
                        @click.stop.prevent="addRemoveFavouriteFile(file.id)">
                        <svg v-if="!file.is_favourite" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                        <FileIcon class="mr-3"/>
                        {{ file.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ file.owner }}
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="!files.length && !folders.length" class="py-8 text-center text-sm text-gray-400">
                There is no file shared with you
            </div>
        </div>
    </AppLayout_new>
</template>

<script setup>
import {computed, ref} from "vue";
import AppLayout_new from "@/Layouts/AppLayout_new.vue";
import DownloadFilesButton from "@/Components/ExtraComponents/DownloadFilesButton.vue";
import SharedWithMeTable from "@/Components/MyComponents/SharedWithMeTable.vue";
import DeleteFilesButton from "@/Components/ExtraComponents/DeleteFilesButton.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";
import Checkbox from "@/Components/Checkbox.vue";
import FolderIcon from "@/Components/Icons/FolderIcon.vue";
import ShareFilesButton from "@/Components/ExtraComponents/ShareFilesButton.vue";
import {useForm} from "@inertiajs/vue3";
import Breadcrumb from "@/Components/MyComponents/Breadcrumb.vue";

const props = defineProps({
    folders: Array,
    files: Array,
    ancestors: Array,
});

// Computed
const selectedFolderIds = computed(() => Object.entries(selectedFolders.value).filter(a => a[1]).map(a => a[0]));
const selectedFileIds = computed(() => Object.entries(selectedFiles.value).filter(a => a[1]).map(a => a[0]));

// Refs
const selectedFolders = ref({});
const selectedFiles = ref({});
const allSelected = ref(false);

const addRemoveFavouritesForm = useForm({
    _method: 'POST',
    'folderId': null,
    'fileId': null,
});

// Methods
function onSelectAllChange() {
    props.folders.forEach(f => {
        selectedFolders.value[f.id] = allSelected.value;
    });

    props.files.forEach(f => {
        selectedFiles.value[f.id] = allSelected.value;
    });

    console.log(selectedFolders.value)
    console.log(selectedFiles.value)
}

function toggleSelectFolder(folderId) {
    selectedFolders.value[folderId] = !selectedFolders.value[folderId];

    onSelectFolderCheckboxChange(folderId);
}

function toggleSelectFile(fileId) {
    selectedFiles.value[fileId] = !selectedFiles.value[fileId];

    onSelectFileCheckboxChange(fileId);
}

function onSelectFolderCheckboxChange(folderId) {
    if (!selectedFolders.value[folderId]) {
        allSelected.value = false;
    } else {
        let checked = true;

        // controllo se almeno una folder è false
        for (let folder of props.folders) {
            if (!selectedFolders.value[folder.id]) {
                checked = false;
                break;
            }
        }

        // controllo se almeno un file è false
        for (let file of props.files) {
            if (!selectedFiles.value[file.id]) {
                checked = false;
                break;
            }
        }

        allSelected.value = checked;
    }
}

function onSelectFileCheckboxChange(fileId) {
    if (!selectedFiles.value[fileId]) {
        allSelected.value = false;
    } else {
        let checked = true;

        // controllo se almeno un file è false
        for (let file of props.files) {
            if (!selectedFiles.value[file.id]) {
                checked = false;
                break;
            }
        }

        // controllo se almeno una folder è false
        for (let folder of props.folders) {
            if (!selectedFolders.value[folder.id]) {
                checked = false;
                break;
            }
        }

        allSelected.value = checked;
    }
}

function addRemoveFavouriteFolder(folderId) {
    addRemoveFavouritesForm.folderId = folderId;

    sendFavouriteRequest();
}

function addRemoveFavouriteFile(fileId) {
    addRemoveFavouritesForm.fileId = fileId;

    sendFavouriteRequest();
}

function sendFavouriteRequest() {
    addRemoveFavouritesForm.post(route('addRemoveFavourites'), {
        onSuccess: (data) => {
            console.log(data);
            addRemoveFavouritesForm.reset();
        },
        onError: (errors) => {
            console.log(errors);
            addRemoveFavouritesForm.reset();
            addRemoveFavouritesForm.clearErrors();
        },
    });
}

function onRestore() {
    allSelected.value = false;
    selectedFolders.value = {};
    selectedFiles.value = {};
}
</script>
