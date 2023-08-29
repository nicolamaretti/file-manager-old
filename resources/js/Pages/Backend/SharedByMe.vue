<script setup>

import ActionIconDelete from "@/Components/Icons/ActionIconDelete.vue";
import ManageFileIcon from "@/Components/Icons/ManageFileIcon.vue";
import ActionIconShare from "@/Components/Icons/ActionIconShare.vue";
import RenameFileModal from "@/Components/RenameFileModal.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import ChevronLeftIcon from "@/Components/Icons/ChevronLeftIcon.vue";
import ActionIconZip from "@/Components/Icons/ActionIconZip.vue";
import DownloadIcon from "@/Components/Icons/DownloadIcon.vue";
import IconFolder from "@/Components/Icons/FolderIcon.vue";
import JetButton from "@/Components/PrimaryButton.vue";
import ActionIconEdit from "@/Components/Icons/ActionIconEdit.vue";
import ManageFolderIcon from "@/Components/Icons/ManageFolderIcon.vue";
import JetConfirmationModal from "@/Components/ConfirmationModal.vue";
import RenameFolderModal from "@/Components/RenameFolderModal.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";

const props = defineProps({
    folders: Object,
    files: Object,
});

console.log(props);
</script>

<template>
    <AppLayout title="Shared by me">
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 leading-tight">
                Shared by me
            </h2>
        </template>

        <!-- Tabella -->
        <div class="m-0 sm:px-10 lg:px-10">
            <div class=" mt-8 min-w-full divide-y divide-gray-300 bg-gray-50">
                <!-- Tabella -->
                <div
                    class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg mb-20 min-w-full divide-y divide-gray-300">
                    <!-- Header -->
                    <div
                        class="grid grid-cols-5 gap-3 px-4 sm:px-6 py-3.5 text-left text-sm font-semibold text-gray-900 bg-gray-100">
                        <!-- prima colonna -->
                        <div class="col-span-3">
                            Name
                        </div>
                        <!-- seconda colonna -->
                        <div class="col-span-2">
                            Shared with
                        </div>
                    </div>
                    <!-- Body -->
                    <div class="divide-y divide-gray-200 bg-white">
                        <!-- Riga della tabella per le folders -->
                        <div v-for="folder in folders"
                            class="grid grid-cols-5 gap-3 py-3 px-4 sm:px-6 text-sm">
                            <div
                                class="overflow-hidden col-span-3 whitespace-nowrap my-auto align-middle font-medium text-gray-900 inline-flex">
                                <IconFolder class="inline-block my-auto mr-3"
                                            />
<!--                                @click="openFolder(folder.id)"-->
                                <label class="m-0 pt-1 text-align:center"
                                       >
<!--                                    @click="openFolder(folder.id)"-->
<!--                                    {{ folder.name }}-->
                                    {{ folder.foldername }}
                                </label>
                            </div>
                            <div
                                class="overflow-hidden col-span-2 whitespace-nowrap pt-1 my-auto text-gray-500">
<!--                                {{ folder.owner }}-->
                                {{ folder.username }}
                            </div>
                        </div>

                        <!-- Riga della tabella per i files -->
                        <div v-for="file in files"
                             class="grid grid-cols-5 gap-3 py-3 px-4 sm:px-6 text-sm">
                            <div
                                class="overflow-hidden col-span-3 whitespace-nowrap py-auto my-auto align-middle font-medium text-gray-900 inline-flex">
                                <FileIcon class="inline-block my-auto mr-3"
                                />
                                <!--                                @click="openFolder(folder.id)"-->
                                <label class="m-0 pt-1 text-align:center"
                                >
                                    <!--                                    @click="openFolder(folder.id)"-->
                                    <!--                                    {{ folder.name }}-->
                                    {{ file.filename }}
                                </label>
                            </div>
                            <div
                                class="overflow-hidden col-span-2 whitespace-nowrap pt-1 my-auto text-gray-500">
                                <!--                                {{ folder.owner }}-->
                                {{ file.username }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
