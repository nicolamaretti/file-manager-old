<template>
    <AppLayout_new title="NewMyFiles">
        <nav class="flex items-center justify-between mb-3 mt-1">
            <Breadcrumb :ancestors="ancestors"/>

            <!-- Bottoni -->
            <div class="flex">
                <ShareFilesButton class="mr-3"/>
                <DownloadFilesButton class="mr-3"/>
                <DeleteFilesButton :delete-folder-ids="selectedFolderIds"
                                   :delete-file-ids="selectedFileIds" @delete="onDelete()"/>
            </div>
        </nav>
        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
<!--            <MyFilesTable :files="props.files"-->
<!--                          :folders="props.folders"-->
<!--                          :currentFolder="props.currentFolder"-->
<!--                          :isUserAdmin="isUserAdmin"/>-->

            <div class="px-4 sm:px-6 lg:px-8">
                <div class="mt-0.5 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle px-1">
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                                <div class="flex-1 overflow-auto min-w-full divide-y divide-gray-300">

                                    <!-- Header -->
                                    <div class="grid grid-cols-12 gap-10 py-3.5 text-left font-semibold bg-gray-100">
                                        <!-- prima colonna -->
                                        <div class="col-span-1 text-left pl-6 text-sm font-semibold text-gray-900">
                                            <Checkbox @change="onSelectAllChange()" v-model:checked="allSelected"/>
                                        </div>
                                        <!-- seconda colonna -->
                                        <div class="col-span-4 ml-2">Name</div>
                                        <!-- terza colonna -->
                                        <div v-if="isUserAdmin" class="grid grid-cols-5 col-span-5">
                                            <!-- l'admin vede anche l'owner -->
                                            <div class="col-span-2">Owner</div>
                                            <div class="col-span-3">Last Modified</div>
                                        </div>
                                        <div v-else class="col-span-5">Last Modified</div>
                                        <!-- quarta colonna -->
                                        <div class="col-span-2">Size</div>
                                    </div>

                                    <!-- Body -->
                                    <div class="divide-y divide-gray-200">
                                        <!-- 1) visualizzazione cartelle -->
                                        <div v-if="folders"
                                             v-for="folder in folders.data"
                                             :key="folder.id"
                                             :class="(selectedFolders[folder.id] || allSelected) ? 'bg-blue-50' : 'bg-white'"
                                             class="grid grid-cols-12 gap-10 py-1 transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                                             @click="$event => toggleSelectFolder(folder.id)"
                                             @dblclick.prevent="openFolder(folder.id)">

                                            <div class="col-span-1 py-4 pl-6 whitespace-nowrap text-sm font-medium text-gray-900 pr-0 inline-flex items-center">
                                                <Checkbox @change="$event => onSelectFolderCheckboxChange(folder.id)"
                                                          v-model="selectedFolders[folder.id]"
                                                          :checked="selectedFolders[folder.id] || allSelected"
                                                          class="mr-4"/>

                                                <div class="text-yellow-500 mr-4"
                                                     @click.stop.prevent="addRemoveFavouriteFolder(folder)">
                                                    <svg class="w-6 h-6"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         stroke-width="1.5"
                                                         viewBox="0 0 24 24"
                                                         xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div
                                                class="col-span-4 ml-2 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex items-center overflow-hidden">
                                                <IconFolder class="mr-3"/>
                                                {{ folder.name }}
                                            </div>
                                            <!-- l'admin vede anche l'owner -->
                                            <div v-if="isUserAdmin"
                                                 class="grid grid-cols-5 col-span-5 whitespace-nowrap align-middle my-auto text-gray-500 items-center">
                                                <div class="col-span-2 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center overflow-hidden">
                                                    {{ folder.owner }}
                                                </div>
                                                <div class="col-span-3 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center overflow-hidden">
                                                    {{ folder.updated_at }}
                                                </div>
                                            </div>
                                            <div v-else
                                                 class="col-span-5 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center">
                                                {{ folder.updated_at }}
                                            </div>
                                            <div
                                                class="col-span-2 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center">
                                                -----
                                            </div>
                                        </div>

                                        <!-- 2) visualizzazione file -->
                                        <div v-if="files"
                                             v-for="file in files.data"
                                             :key="file.id"
                                             :class="(selectedFiles[file.id] || allSelected) ? 'bg-blue-50' : 'bg-white'"
                                             class="grid grid-cols-12 gap-10 py-1 transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                                             @click="$event => toggleSelectFile(file.id)">
                                            <div class="col-span-1 py-4 pl-6 whitespace-nowrap text-sm font-medium text-gray-900 pr-0 inline-flex items-center">
                                                <Checkbox @change="$event => onSelectFileCheckboxChange(file.id)"
                                                          v-model="selectedFiles[file.id]"
                                                          :checked="selectedFiles[file.id] || allSelected"
                                                          class="mr-4"/>
                                                <div class="text-yellow-500 mr-4"
                                                     @click.stop.prevent="addRemoveFavouriteFile(file)">
                                                    <svg class="w-6 h-6"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         stroke-width="1.5"
                                                         viewBox="0 0 24 24"
                                                         xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div
                                                class="col-span-4 ml-2 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex items-center overflow-hidden">
                                                <FileIcon class="mr-3"/>
                                                {{ file.file_name }}
                                            </div>
                                            <!-- l'admin vede anche l'owner -->
                                            <div v-if="isUserAdmin"
                                                 class="grid grid-cols-5 col-span-5 whitespace-nowrap align-middle my-auto text-gray-500 items-center">
                                                <div class="col-span-2 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center overflow-hidden">
                                                    {{ currentFolder.data.owner }}
                                                </div>
                                                <div class="col-span-3 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center">
                                                    {{ file.updated_at }}
                                                </div>
                                            </div>
                                            <div v-else
                                                 class="col-span-5 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center overflow-hidden">
                                                {{ file.updated_at }}
                                            </div>
                                            <div
                                                class="col-span-2 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center">
                                                {{ file.size }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- controllo per la home page dell'admin: all'inizio, files è null e fa crashare l'app -->
                            <div v-if="files && folders">
                                <div v-if="!files.data.length && !folders.data.length"
                                     class="py-8 text-center text-sm text-gray-400">
                                    There is no data in this folder
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout_new>
</template>

<script setup>
import {computed, ref} from "vue";
import {router} from "@inertiajs/vue3";
import AppLayout_new from "@/Layouts/AppLayout_new.vue";
import ShareFilesButton from "@/Components/ExtraComponents/ShareFilesButton.vue";
import DownloadFilesButton from "@/Components/ExtraComponents/DownloadFilesButton.vue";
import DeleteFilesButton from "@/Components/ExtraComponents/DeleteFilesButton.vue";
import Breadcrumb from "@/Components/MyComponents/Breadcrumb.vue";
// import MyFilesTable from "@/Components/MyComponents/MyFilesTable.vue";
import Checkbox from "@/Components/Checkbox.vue";
import IconFolder from "@/Components/Icons/FolderIcon.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";

// Props
const props = defineProps({
    currentFolder: Object,
    rootFolderId: Number,
    isUserAdmin: Boolean,
    parent: Object,
    folders: Object,
    files: Object,
    folderIsRoot: Boolean,
    ancestors: Array,
});

// Computed
const selectedFolderIds = computed(() => Object.entries(selectedFolders.value).filter(a => a[1]).map(a => a[0]));
const selectedFileIds = computed(() => Object.entries(selectedFiles.value).filter(a => a[1]).map(a => a[0]));


// Refs
const selectedFolders = ref({});
const selectedFiles = ref({});
const allSelected = ref(false);

// Methods
const openFolder = (folderId = null) => {
    if (folderId != null) {
        // ritorna la cartella selezionata
        router.get(route('newMyFiles'), {folderId: folderId}, {preserveScroll: true});
    } else {
        // ritorna le cartelle di root
        router.get(route('newMyFiles'), {}, {preserveScroll: true});
    }
}

function onSelectAllChange() {
    props.folders.data.forEach(f => {
        selectedFolders.value[f.id] = allSelected.value;
    });

    props.files.data.forEach(f => {
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
        for (let folder of props.folders.data) {
            if (!selectedFolders.value[folder.id]) {
                checked = false;
                break;
            }
        }

        // controllo se almeno un file è false
        for (let file of props.files.data) {
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
        for (let file of props.files.data) {
            if (!selectedFiles.value[file.id]) {
                checked = false;
                break;
            }
        }

        // controllo se almeno una folder è false
        for (let folder of props.folders.data) {
            if (!selectedFolders.value[folder.id]) {
                checked = false;
                break;
            }
        }

        allSelected.value = checked;
    }
}

function onDelete() {
    allSelected.value = false;
    selectedFolders.value = {};
    selectedFiles.value = {};
}

</script>
