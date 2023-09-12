<template>
    <AppLayout_new title="NewSharedByMe">
        <nav class="flex items-center justify-end mb-3 mt-1">
            <div class="flex">
<!--                <ShareFilesButton class="mr-3"/>-->
                <DownloadFilesButton class="mr-3"/>
                <DeleteSharedButton :stop-share-file-ids="selectedFileIds"
                                    :stop-share-folder-ids="selectedFolderIds"
                                    @stop-share="onRestore"/>
            </div>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
            <table class="min-w-full shadow ring-1 ring-black ring-opacity-5 border sm:rounded-lg">
                <thead class="bg-gray-100 border-b sm:rounded-lg">
                <tr>
                    <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="onSelectAllChange" v-model:checked="allSelected"/>
                    </th>
                    <th class="pl-14 text-sm font-semibold text-gray-900 px-6 py-4 text-left">
                        Name
                    </th>
                    <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left">
                        Shared with
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
                    <td class="pl-14 px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                        <FolderIcon class="mr-3"/>
                        {{ folder.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ folder.username }}
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
                    <td class="pl-14 px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                        <FileIcon class="mr-3"/>
                        {{ file.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ file.username }}
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="!files.length && !folders.length" class="py-8 text-center text-sm text-gray-400">
                You have no file shared
            </div>
        </div>
    </AppLayout_new>
</template>

<script setup>
import AppLayout_new from "@/Layouts/AppLayout_new.vue";
import DownloadFilesButton from "@/Components/ExtraComponents/DownloadFilesButton.vue";
import DeleteFilesButton from "@/Components/ExtraComponents/DeleteFilesButton.vue";
import SharedByMeTable from "@/Components/MyComponents/SharedByMeTable.vue";
import DeleteSharedButton from "@/Components/ExtraComponents/DeleteSharedButton.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";
import Checkbox from "@/Components/Checkbox.vue";
import FolderIcon from "@/Components/Icons/FolderIcon.vue";
import {computed, ref} from "vue";
import ShareFilesButton from "@/Components/ExtraComponents/ShareFilesButton.vue";

const props = defineProps({
    folders: Array,
    files: Array,
});

// Computed
const selectedFolderIds = computed(() => Object.entries(selectedFolders.value).filter(a => a[1]).map(a => a[0]));
const selectedFileIds = computed(() => Object.entries(selectedFiles.value).filter(a => a[1]).map(a => a[0]));

// Refs
const selectedFolders = ref({});
const selectedFiles = ref({});
const allSelected = ref(false);

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

function onRestore() {
    allSelected.value = false;
    selectedFolders.value = {};
    selectedFiles.value = {};
}

console.log(props);

</script>
