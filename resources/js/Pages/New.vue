<template>
    <AppLayout_new title="New">
        <nav class="flex items-center justify-between p-1 mb-3">
            <!-- Bottoni -->
            <div class="flex">
                <ShareFilesButton/>
                <DownloadFilesButton class="mr-2"/>
                <DeleteFilesButton/>
            </div>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="onSelectAllChange" v-model:checked="allSelected"/>
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">

                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Name
                    </th>
                    <th v-if="rootFolderId == null"
                        class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Owner
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Last Modified
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Size
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="folder in folders.data" :key="folder.id"
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    @dblclick.prevent="openFolder(folder.id)">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                        <Checkbox/>
                    </td>
                    <td class="px-6 py-4 max-w-[40px] text-sm font-medium text-gray-900 text-yellow-500">
                        <div @click.stop.prevent="addRemoveFavourite(file)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                            </svg>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                        <IconFolder class="inline-block mr-3"/>
                        {{ folder.name }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </AppLayout_new>
</template>

<script setup>
import {computed, ref} from "vue";
import {router, usePage, Link} from "@inertiajs/vue3";
import AppLayout_new from "@/Layouts/AppLayout_new.vue";
import Checkbox from "@/Components/Checkbox.vue";
import ShareFilesButton from "@/Components/ExtraComponents/ShareFilesButton.vue";
import DownloadFilesButton from "@/Components/ExtraComponents/DownloadFilesButton.vue";
import DeleteFilesButton from "@/Components/ExtraComponents/DeleteFilesButton.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";
import ActionIconShare from "@/Components/Icons/ActionIconShare.vue";
import ActionIconZip from "@/Components/Icons/ActionIconZip.vue";
import ChevronLeftIcon from "@/Components/Icons/ChevronLeftIcon.vue";
import DownloadIcon from "@/Components/Icons/DownloadIcon.vue";
import IconFolder from "@/Components/Icons/FolderIcon.vue";
import ActionIconDelete from "@/Components/Icons/ActionIconDelete.vue";
import ManageFolderIcon from "@/Components/Icons/ManageFolderIcon.vue";
import ActionIconEdit from "@/Components/Icons/ActionIconEdit.vue";
import ManageFileIcon from "@/Components/Icons/ManageFileIcon.vue";

const userFolderPermission = computed(() => usePage().props.folderPermission);

const props = defineProps({
    currentUserName: String,
    currentFolderId: Number,
    currentFolderName: String,
    currentFolderFullPath: String,
    rootFolderId: Number,
    isUserAdmin: Boolean,
    parent: Object,
    folders: Object,
    folder: Object,
    files: Object,
    folderIsRoot: Boolean,
});

const openFolder = (folderId = null) => {
    if (folderId != null) {
        // ritorna la cartella selezionata
        router.get(route('new'), {
            folderId: folderId
        });
    } else {
        // ritorna le cartelle di root
        router.get(route('new'));
    }
}

console.log(props);
</script>
