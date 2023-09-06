<template>
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
                                    <Checkbox @change="onSelectAllChange" v-model:checked="allSelected"/>
                                </div>
                                <!-- seconda colonna -->
                                <div class="col-span-4 ml-6">Name</div>
                                <!-- terza colonna -->
                                <div class="col-span-5">Shared with</div>
                            </div>

                            <!-- Body -->
                            <div class="divide-y divide-gray-200 bg-white">
                                <!-- 1) visualizzazione cartelle -->
                                <div v-if="folders"
                                     v-for="folder in folders"
                                     :key="folder.folderId"
                                     class="grid grid-cols-12 gap-10 py-2">
                                    <div
                                        class="col-span-1 py-4 pl-6 whitespace-nowrap text-sm font-medium text-gray-900 pr-0 inline-flex items-center">
                                        <Checkbox class="mr-4"/>
                                    </div>
                                    <div class="col-span-4 ml.6 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex items-center overflow-hidden">
                                        <IconFolder class="mr-3"/>
                                        {{ folder.folderName }}
                                    </div>
                                    <div class="col-span-2 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center overflow-hidden">
                                        {{ folder.userName }}
                                    </div>
                                </div>

                                <!-- 2) visualizzazione file -->
                                <div v-if="files"
                                     v-for="file in files"
                                     :key="file.fileId"
                                     class="grid grid-cols-12 gap-10 py-1 transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer">
                                    <div
                                        class="col-span-1 py-4 pl-6 whitespace-nowrap text-sm font-medium text-gray-900 pr-0 inline-flex items-center">
                                        <Checkbox class="mr-4"/>
                                    </div>
                                    <div
                                        class="col-span-4 ml-6 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex items-center overflow-hidden">
                                        <FileIcon class="mr-3"/>
                                        {{ file.fileName }}
                                    </div>
                                    <div class="col-span-2 whitespace-nowrap align-middle my-auto text-gray-500 inline-flex items-center overflow-hidden">
                                        {{ file.userName }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!files.length && !folders.length" class="py-8 text-center text-sm text-gray-400">
                        There is no file shared
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import {router, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import IconFolder from "@/Components/Icons/FolderIcon.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";

const page = usePage();

// Props
const props = defineProps({
    folders: Array,
    files: Array,
});

console.log('bbbbbbbbbb')
console.log(props)
</script>
