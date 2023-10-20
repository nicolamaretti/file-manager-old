<template>
    <Head :title="title" />

    <div class="flex w-full h-screen gap-2 px-3 bg-gray-50">
        <!-- Sezione laterale sx -->
        <Navigation />

        <!-- Sezione centrale -->
        <main :class="dragOver ? 'dropzone' : ''" class="flex flex-col flex-1 ml-10 overflow-hidden"
            @drop.prevent="handleDrop" @dragover.prevent="onDragOver" @dragleave.prevent="onDragLeave">

            <!-- Se l'utente sta facendo un drag dentro all'applicazione -->
            <template v-if="dragOver" class="py-8 text-sm text-center text-gray-500">
                Drop files here to upload
            </template>

            <!-- Visualizzazione standard della barra di ricerca, del menu utente e della tabella con file e cartelle -->
            <template v-else>
                <div class="flex items-center justify-between w-full ml-1">
                    <SearchForm />

                    <UserSettingsDropdown />
                </div>

                <!-- Slot per la tabella -->
                <div class="flex flex-col flex-1 overflow-hidden">
                    <slot />
                </div>
            </template>
        </main>
    </div>

    <ErrorDialog />
    <!--    <FormProgress :form="fileUploadForm"/>-->
    <Notification />
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { emitter, FILE_UPLOAD_STARTED, showErrorDialog, showSuccessNotification } from "@/event-bus.js";
import Navigation from "@/Components/MyComponents/Navigation.vue";
import SearchForm from "@/Components/MyComponents/SearchForm.vue";
import UserSettingsDropdown from "@/Components/MyComponents/UserSettingsDropdown.vue";
import ErrorDialog from "@/Components/MyComponents/ErrorDialog.vue";
import Notification from "@/Components/MyComponents/Notification.vue";

// Props & Emit
defineProps({
    title: String,
});

// Refs
const dragOver = ref(false);

// Uses
const page = usePage();

// Methods
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

    if (files.length) {
        uploadFiles(files);
    }
}

async function uploadFiles(files) {
    console.log('Upload');

    router.post(route('upload'),
        {
            files: files,
            currentFolderId: page.props.currentFolder.data.id,
        },
        {
            preserveState: true,
            only: ['files'],
            onSuccess: (data) => {
                console.log('uploadSuccess', data);

                showSuccessNotification('Files uploaded successfully');
            },
            onError: (errors) => {
                console.log('uploadError', errors.message);

                // let message = errors.message
                showErrorDialog(errors.message);
            }
        });
}

onMounted(() => {
    emitter.on(FILE_UPLOAD_STARTED, uploadFiles);
});

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
