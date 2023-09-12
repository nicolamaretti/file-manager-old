<template>
    <nav class="flex items-center justify-between mb-3 mt-4 border-b">
        <Breadcrumb :ancestors="ancestors"/>
    </nav>

    <!-- Tabella -->
    <div class="flex-1 overflow-auto">
        <table class="min-w-full shadow ring-1 ring-black ring-opacity-5  sm:rounded-lg">
            <tbody>
            <tr v-for="folder in folders"
                class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                :class="(selectedFolders[folder.id]) ? 'bg-blue-50' : 'bg-white'"
                @click="$event => toggleSelectFolder(folder.id)"
                @dblclick.prevent="openFolder(folder.id)">
                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                    <Checkbox @change="$event => onSelectFolderCheckboxChange(folder.id)"
                              v-model="selectedFolders[folder.id]"
                              :checked="selectedFolders[folder.id]"
                              class="mr-4"/>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                    <FolderIcon class="mr-3"/>
                    {{ folder.name }}
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import {computed, onBeforeMount, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import AppLayout_new from "@/Layouts/AppLayout_new.vue";
import ShareFilesButton from "@/Components/ExtraComponents/ShareFilesButton.vue";
import DownloadFilesButton from "@/Components/ExtraComponents/DownloadFilesButton.vue";
import DeleteFilesButton from "@/Components/ExtraComponents/DeleteFilesButton.vue";
import Breadcrumb from "@/Components/MyComponents/Breadcrumb.vue";
import Checkbox from "@/Components/Checkbox.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";
import FolderIcon from "@/Components/Icons/FolderIcon.vue";
import RenameFileButton from "@/Components/ExtraComponents/RenameFileButton.vue";
import CopyFileButton from "@/Components/ExtraComponents/CopyFileButton.vue";
import MoveFilesButton from "@/Components/ExtraComponents/MoveFilesButton.vue";

// Props
const props = defineProps({
    moveFolderIds: Array,
    moveFileIds: Array,
    currentFolder: Object,
    folders: Object,
    ancestors: Array,
});

const page = usePage();
// const folders = page.props.folders;
// const ancestors = page.props.ancestors;

// Computed
const selectedFolderIds = computed(() => Object.entries(selectedFolders.value).filter(a => a[1]).map(a => a[0]));
const selectedFileIds = computed(() => Object.entries(selectedFiles.value).filter(a => a[1]).map(a => a[0]));

// Refs
const selectedFolders = ref({});
const selectedFiles = ref({});
const allSelected = ref(false);

// Methods
// const openFolder = (folderId = null) => {
//     if (folderId != null) {
//         // ritorna la cartella selezionata
//         router.get(route('getFoldersForMoveModal'), {folderId: folderId}, {preserveScroll: true});
//     } else {
//         // ritorna le cartelle di root
//         router.get(route('getFoldersForMoveModal'), {}, {preserveScroll: true});
//     }
// }

function toggleSelectFolder(folderId) {
    onSelectFolderCheckboxChange(folderId);
}

function onSelectFolderCheckboxChange(folderId) {
    // metto tutte le selected folders a false tranne quella selezionata
    for (let folder of props.folders.data) {
        selectedFolders.value[folder.id] = false;
    }

    selectedFolders.value[folderId] = true;

    console.log(selectedFolders.value);
}

function onRestore() {
    allSelected.value = false;
    selectedFolders.value = {};
    selectedFiles.value = {};
}

// onBeforeMount(() => {
//     // console.log('before mount')
//     router.get(route('getFoldersForMoveModal'), {
//         'moveFileIds' : props.moveFileIds,
//         'moveFolderIds' : props.moveFolderIds,
//     }, {
//         onSuccess: (data) => {
//             console.log(data)
//         },
//         onError: (error) => {
//
//         },
//         preserveScroll: true,
//     });
// });

</script>
