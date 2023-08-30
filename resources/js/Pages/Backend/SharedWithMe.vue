<template>
    <AppLayout title="Shared with me">
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 leading-tight">
                Shared with me
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
                            Owner
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
                                class="overflow-hidden col-span-3 whitespace-nowrap py-auto my-auto align-middle font-medium text-gray-900 inline-flex">
                                <IconFolder class="inline-block my-auto mr-3"/>
                                <label class="m-0 pt-1 text-align:center">
                                    {{ folder.folderName }}
                                </label>
                            </div>
                            <div
                                class="overflow-hidden col-span-2 whitespace-nowrap pt-1 my-auto text-gray-500">
                                {{ folder.folderOwner }}
                            </div>
                            <div
                                class="col-span-3 relative whitespace-nowrap pt-1 my-auto text-left font-medium">

                                <a :href="route('backend.file-manager.zip-folder', folder.folderId)">
                                    <ActionIconZip class="mr-2"/>
                                </a>
                            </div>
                        </div>

                        <!-- Riga della tabella per i files -->
                        <div v-for="file in files"
                             class="grid grid-cols-8 gap-3 py-auto px-4 sm:px-6 text-sm">
                            <div
                                class="overflow-hidden col-span-3 whitespace-nowrap py-auto my-auto align-middle font-medium text-gray-900 inline-flex">
                                <FileIcon class="inline-block my-auto mr-3"
                                />
                                <label class="m-0 pt-1 text-align:center"
                                >
                                    {{ file.fileName }}
                                </label>
                            </div>
                            <div
                                class="overflow-hidden col-span-2 whitespace-nowrap pt-1 my-auto text-gray-500">
                                <!--                                {{ folder.owner }}-->
                                {{ file.fileOwner }}
                            </div>
                            <div
                                class="col-span-3 relative whitespace-nowrap pt-1 my-auto text-left font-medium">

                                <a :href="route('backend.file-manager.download-file', file.fileId)">
                                    <DownloadIcon class="mr-2"/>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
// Imports
import AppLayout from "@/Layouts/AppLayout.vue";
import ActionIconZip from "@/Components/Icons/ActionIconZip.vue";
import DownloadIcon from "@/Components/Icons/DownloadIcon.vue";
import IconFolder from "@/Components/Icons/FolderIcon.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";

// Props
const props = defineProps({
    folders: Object,
    files: Object,
});

</script>
