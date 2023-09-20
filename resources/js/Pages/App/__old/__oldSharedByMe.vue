<template>
    <AppLayout title="Shared By Me">
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 leading-tight">
                Shared By Me
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
                        class="grid grid-cols-8 gap-3 px-4 sm:px-6 py-3.5 text-left text-sm font-semibold text-gray-900 bg-gray-100">
                        <!-- prima colonna -->
                        <div class="col-span-3">
                            Name
                        </div>
                        <!-- seconda colonna -->
                        <div class="col-span-2">
                            Shared with
                        </div>
                        <!-- terza colonna -->
                        <div class="col-span-2">
                            Actions
                        </div>
                    </div>
                    <!-- Body -->
                    <div class="divide-y divide-gray-200 bg-white">
                        <!-- Riga della tabella per le folders -->
                        <div v-for="folder in folders"
                             class="grid grid-cols-8 gap-3 py-auto px-4 sm:px-6 text-sm">
                            <div
                                class="overflow-hidden col-span-3 whitespace-nowrap my-auto align-middle font-medium text-gray-900 inline-flex">
                                <IconFolder class="inline-block my-auto mr-3"/>
                                <label class="m-0 pt-1 text-align:center">
                                    {{ folder.folderName }}
                                </label>
                            </div>
                            <div class="overflow-hidden col-span-2 whitespace-nowrap pt-1 my-auto text-gray-500">
                                {{ folder.userName }}
                            </div>
                            <div
                                class="col-span-3 relative whitespace-nowrap pt-1 my-auto text-left font-medium">

                                <ActionIconDelete class="mr-2"
                                                  @click.prevent="openStopShareFolderModal(folder)"/>
                            </div>
                        </div>

                        <!-- Riga della tabella per i files -->
                        <div v-for="file in files"
                             class="grid grid-cols-8 gap-3 py-auto px-4 sm:px-6 text-sm">
                            <div
                                class="overflow-hidden col-span-3 whitespace-nowrap py-auto my-auto align-middle font-medium text-gray-900 inline-flex">
                                <FileIcon class="inline-block my-auto mr-3"
                                />
                                <label class="m-0 pt-1 text-align:center">
                                    {{ file.fileName }}
                                </label>
                            </div>
                            <div
                                class="overflow-hidden col-span-2 whitespace-nowrap pt-1 my-auto text-gray-500">
                                {{ file.userName }}
                            </div>
                            <div
                                class="col-span-3 relative whitespace-nowrap pt-1 my-auto text-left font-medium">

                                <ActionIconDelete class="mr-2"
                                                  @click.prevent="openStopShareFileModal(file)"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- modale stop sharing folder -->
        <JetConfirmationModal :show="stopShareFolderConfirmModal"
                              @close.prevent="closeStopShareFolderModal()">
            <template #title>
                <span class="text-center">
                    STOP SHARING FOLDER
                </span>
            </template>
            <template #content>
                <span class="text-center">Are you sure you want to stop sharing </span>
                <span class="text-center font-bold break-all">{{ folderToStop.folderName }}</span>
                <span class="text-center">?</span>
            </template>
            <template #footer>
                <JetButton @click.prevent="closeStopShareFolderModal()"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Cancel</span>
                </JetButton>
                <JetButton @click.prevent="stopShareFolder()"
                           class="bg-asgreen-200 text-white rounded-sm mb-3  cursor-pointer  px-6 py-2 hover:bg-gray-600">
                    <span>Confirm</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale stop sharing file -->
        <JetConfirmationModal :show="stopShareFileConfirmModal"
                              @close.prevent="closeStopShareFileModal()">
            <template #title>
                <span class="text-center">
                    STOP SHARING FILE
                </span>
            </template>
            <template #content>
                <span class="text-center">Are you sure you want to stop sharing </span>
                <span class="text-center font-bold break-all">{{ fileToStop.fileName }}</span>
                <span class="text-center">?</span>
            </template>
            <template #footer>
                <JetButton @click.prevent="closeStopShareFileModal()"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Cancel</span>
                </JetButton>
                <JetButton @click.prevent="stopShareFile()"
                           class="bg-asgreen-200 text-white rounded-sm mb-3  cursor-pointer  px-6 py-2 hover:bg-gray-600">
                    <span>Confirm</span>
                </JetButton>
            </template>
        </JetConfirmationModal>
    </AppLayout>
</template>

<script setup>
// Imports
import {ref} from 'vue';
import {router} from "@inertiajs/vue3";
import ActionIconDelete from "@/Components/Icons/ActionIconDelete.vue";
import AppLayout from "@/Layouts/__oldAppLayout.vue";
import IconFolder from "@/Components/Icons/FolderIcon.vue";
import JetButton from "@/Components/PrimaryButton.vue";
import JetConfirmationModal from "@/Components/ConfirmationModal.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";

// Props
const props = defineProps({
    folders: Object,
    files: Object,
});

const stopShareFolder = () => {
    router.delete(route('shared-by-me.stop-sharing-folder', folderToStop.value.folderId), {
        onSuccess: () => {
            closeStopShareFolderModal();
        },
        onError: (error) => {
            console.log(error);

            closeStopShareFolderModal();
        }
    });
}

const stopShareFile = () => {
    router.delete(route('shared-by-me.stop-sharing-file', fileToStop.value.fileId), {
        onSuccess: () => {
            closeStopShareFileModal();
        },
        onError: (error) => {
            console.log(error);

            closeStopShareFileModal();
        }
    });
}

//////////////////////////////////////////////////////////////////////////
/* 1) modale stop sharing folder */
let stopShareFolderConfirmModal = ref(false);
let folderToStop = ref(null);

function openStopShareFolderModal(folder) {
    stopShareFolderConfirmModal.value = true;
    folderToStop.value = folder;
}

function closeStopShareFolderModal() {
    stopShareFolderConfirmModal.value = false;
    folderToStop.value = null;
}

/* 2) modale stop sharing file */
let stopShareFileConfirmModal = ref(false);
let fileToStop = ref(null);

function openStopShareFileModal(file) {
    stopShareFileConfirmModal.value = true;
    fileToStop.value = file;
}

function closeStopShareFileModal() {
    stopShareFileConfirmModal.value = false;
    fileToStop.value = null;
}

console.log(props);
</script>
