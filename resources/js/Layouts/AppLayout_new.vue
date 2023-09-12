<template>
    <Head :title="title"/>

    <div class="h-screen bg-gray-50 flex w-full gap-2 px-3">
        <!-- Sezione laterale sx -->
        <Navigation/>

        <!-- Sezione centrale -->
        <main @drop.prevent="handleDrop"
              @dragover.prevent="onDragOver"
              @dragleave.prevent="onDragLeave"
              class="flex flex-col flex-1 ml-10 overflow-hidden"
              :class="dragOver ? 'dropzone' : ''">

            <!-- Se l'utente sta facendo un drag dentro all'applicazione -->
            <template v-if="dragOver" class="text-gray-500 text-center py-8 text-sm">
                Drop files here to upload
            </template>

            <!-- Visualizzazione standard della barra di ricerca, del menu utente e della tabella con file e cartelle -->
            <template v-else>
                <div class="flex items-center justify-between w-full ml-1">
                    <SearchForm/>

                    <UserSettingsDropdown/>
                </div>

                <!-- Slot per la tabella -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <slot/>
                </div>
            </template>
        </main>
    </div>

    <!--    <ErrorDialog />-->
    <!--    <FormProgress :form="fileUploadForm"/>-->
    <!--    <Notification />-->
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {Head, Link, router, useForm, usePage} from '@inertiajs/vue3';
import {emitter, FILE_DOWNLOAD_STARTED, FILE_UPLOAD_STARTED} from "@/event-bus.js";
import Navigation from "@/Components/ExtraComponents/Navigation.vue";
import SearchForm from "@/Components/ExtraComponents/SearchForm.vue";
import UserSettingsDropdown from "@/Components/ExtraComponents/UserSettingsDropdown.vue";

defineProps({
    title: String,
});

onMounted(() => {
    emitter.on(FILE_UPLOAD_STARTED, uploadFiles);
    emitter.on(FILE_DOWNLOAD_STARTED, download);
});

const fileUploadForm = useForm({
    _method: 'POST',
    files: [],
    currentFolderId: null
});

const dragOver = ref(false);

function onDragOver() {
    dragOver.value = true;
}

function onDragLeave() {
    dragOver.value = false;
}

function handleDrop(ev) {
    dragOver.value = false;

    // i file che droppiamo sono all'interno di questo evento
    const files = ev.dataTransfer.files;

    if (!files.length) {
        return;
    } else {
        uploadFiles(files);
    }
}

function uploadFiles(files) {
    console.log(files);

    fileUploadForm.files = files;
    fileUploadForm.currentFolderId = page.props.currentFolder.data.id;

    fileUploadForm.post(route('upload'));
}

function download(downloadObject) {
    console.log('Folders to download: ' + downloadObject.downloadFolderIds);
    console.log('Files to download: ' + downloadObject.downloadFileIds);

    router.visit(route('download', {
        'downloadFolderIds': downloadObject.downloadFolderIds,
        'downloadFileIds': downloadObject.downloadFileIds
    }));
}

</script>

<style scoped>
.dropzone {
    width: 100%;
    height: 100%;
    color: #8d8d8d;
    border: 2px dashed rgb(128, 128, 128);
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
