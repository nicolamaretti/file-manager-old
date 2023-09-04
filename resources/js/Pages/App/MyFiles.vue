<template>
    <AppLayout title="My Files">
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 leading-tight">
                My Files
            </h2>
        </template>

        <!-- Sezione creazione cartelle e upload file -->
        <div class="bg-white shadow border-gray-300 border-t-2">
            <div class="px-4 py-5 flex">
                <!-- 1.1) Creazione nuova cartella root (se Admin) -->
                <div v-if="rootFolderId == null"
                     class="basis-1/2 px-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Create Root Folder</h3>

                    <form @submit.prevent="submitRootFolderForm"
                          class="mt-5 sm:flex sm:items-center">
                        <div class="w-full sm:max-w-xs">
                            <label for="newRootFolderName" class="sr-only"></label>
                            <input type="text"
                                   name="newRootFolderName"
                                   id="newRootFolderName"
                                   v-model="rootFolderForm.newRootFolderName"
                                   class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <button type="submit"
                                class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:mt-0 sm:w-auto">
                            Create
                        </button>
                    </form>
                </div>

                <!-- 1.2) Creazione nuova cartella "normale" (se non Admin) -->
                <div v-else class="basis-1/2 px-6">
                    <div v-if="userFolderPermission.write"
                         class="grid grid-cols-2 sm:flex">
                        <div class="sm:basis-1/2">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Create Folder</h3>

                            <form @submit.prevent="submitFolderForm"
                                  class="mt-5 sm:flex sm:items-center">
                                <div class="w-full sm:max-w-xs">
                                    <label for="newFolderName"
                                           class="sr-only"></label>
                                    <input type="text"
                                           name="newFolderName"
                                           id="newFolderName"
                                           v-model="folderForm.newFolderName"
                                           class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                                <button type="submit"
                                        class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:mt-0 sm:w-auto">
                                    Create
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 1.3) Upload file -->
                <div class="basis-1/2 px-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Upload File</h3>

                    <form @submit.prevent="submitFileUploadForm" class="mt-5 sm:flex sm:items-center">
                        <div class="w-full sm:max-w-xs">
                            <label for="file" class="sr-only"></label>

                            <input type="file" name="file" id="file" ref="file" @change="onChange"
                                   multiple
                                   class=" block text-sm w-11/12 file:bg-asblue-200 file:hover:bg-asblue-100 file:font-regular file:text-sm file:py-1.5 file:ring-0 file:text-asblue-800 break-all file:px-3 file:mr-4 text-gray-900">
                        </div>
                        <button type="submit"
                                class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:mt-0 sm:w-auto">
                            Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sezione visualizzazione file e cartelle personali -->
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
                        <div v-if="rootFolderId == null"
                             class="col-span-2">
                            Owner
                        </div>
                        <div v-else
                             class="col-span-2">
                            Uuid
                        </div>
                        <!-- terza colonna -->
                        <div class="col-span-3">
                            Actions
                        </div>
                    </div>
                    <!-- Body -->
                    <div class="divide-y divide-gray-200 bg-white">
                        <!-- Riga con back button -->
                        <div v-if="!folderIsRoot" class="whitespace-nowrap mt-1.5 pl-3 inline-flex items-center">
                            <div class="inline-block">
                                <ChevronLeftIcon v-if="parent != null"
                                                 @click.prevent=(openFolder(parent.id))></ChevronLeftIcon>
                                <ChevronLeftIcon v-else
                                                 @click.prevent=(openFolder())></ChevronLeftIcon>
                            </div>
                            <div class="inline-block m-0 pb-1 pl-3">
                                <label class="text-xl font-bold">
                                    {{ currentFolderName }}
                                </label>
                            </div>
                        </div>

                        <!-- se sono Admin e sono nella root, mostro tutte le cartelle -->
                        <!-- Riga della tabella -->
<!--                        <div v-if="rootFolderId == null && folderIsRoot">-->
                        <div v-if="rootFolderId == null">
                            <div v-for="folder in folders.data"
                                 class="grid grid-cols-8 gap-3 py-1 px-4 sm:px-6 text-sm border-b">
                                <div
                                    class="overflow-hidden col-span-3 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex">
                                    <IconFolder class="inline-block mr-3"/>
                                    <!--                                @click.prevent="openFolder(folder.id)"-->
                                    <label class="cursor-pointer m-0 pt-1 text-align:center overflow-hidden hover:underline"
                                           @click.prevent="openFolder(folder.id)">
                                        {{ folder.name }}
                                    </label>
                                </div>
                                <div
                                    class="col-span-1 whitespace-nowrap pt-1 my-auto text-gray-500 overflow-hidden">
                                    {{ folder.owner }}
                                </div>

                                <!-- div vuoto per creare spazio -->
                                <div class="col-span-1"/>

                                <div
                                    class="overflow-hidden col-span-3 relative whitespace-nowrap my-auto py-auto text-left font-medium">
                                    <ActionIconEdit v-if="isUserAdmin"
                                                    class="mr-2"
                                                    @click.prevent="openRenameFolderModal(folder.id)"/>

                                    <a :href="route('folder.zip', folder.id)">
                                        <ActionIconZip class="mr-2"/>
                                    </a>

                                    <ActionIconDelete class="mr-2"
                                                      @click.prevent="openDeleteFolderModal(folder)"/>
                                </div>
                            </div>

                            <!-- 2) visualizzazione file -->
                            <div v-if="files" v-for="file in files.data"
                                 class="grid grid-cols-8 gap-3 py-1 px-4 sm:px-6 text-sm border-b">
                                <div
                                    class="overflow-hidden col-span-3 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex">
                                    <FileIcon class="inline-block my-auto mr-3"/>
                                    <!--                                @click.prevent="openFile(file.id)"-->
                                    <p class="m-0 pt-0.5">
                                        <!--                                    @click.prevent="openFile(file.id)"-->
                                        {{ file.file_name }}
                                    </p>
                                </div>
                                <div class="col-span-1 whitespace-nowrap py-auto my-auto text-gray-500">
                                    <p class="m-0 pt-0.5">
                                        ----------
                                    </p>
                                </div>

                                <!-- div vuoto per creare spazio -->
                                <div class="col-span-1"/>

                                <div
                                    class="overflow-hidden col-span-3 relative whitespace-nowrap my-auto py-auto text-left font-medium">
                                    <ActionIconEdit v-if="userFolderPermission.write"
                                                    class="mr-2"
                                                    @click.prevent="openRenameFileModal(file.id)"/>

                                    <a :href="route('file.download', file.id)">
                                        <DownloadIcon class="mr-2"/>
                                    </a>

                                    <ActionIconDelete class="mr-2"
                                                      @click.prevent="openDeleteFileModal(file)"/>
                                </div>
                            </div>
                        </div>

                        <!-- se invece non sono admin, visualizzo il contenuto della mia root folder (files e cartelle) -->
                        <div v-else>
                            <!-- 1) visualizzazione sottocartelle -->
                            <div v-for="folder in folders.data"
                                 class="grid grid-cols-8 gap-3 py-1 px-4 sm:px-6 text-sm border-b">
                                <div
                                    class="overflow-hidden col-span-3 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex">
                                    <IconFolder class="inline-block mr-3"/>
                                    <!--                                @click.prevent="openFolder(folder.id)"-->
                                    <label class="cursor-pointer m-0 pt-1 text-align:center overflow-hidden hover:underline"
                                           @click.prevent="openFolder(folder.id)">
                                        {{ folder.name }}
                                    </label>
                                </div>
                                <div
                                    class="col-span-1 whitespace-nowrap pt-1 my-auto text-gray-500 overflow-hidden">
                                    {{ folder.uuid }}
                                </div>

                                <!-- div vuoto per creare spazio -->
                                <div class="col-span-1"/>

                                <div
                                    class="overflow-hidden col-span-3 relative whitespace-nowrap my-auto py-auto text-left font-medium">
                                    <ActionIconEdit v-if="userFolderPermission.write"
                                                    class="mr-2"
                                                    @click.prevent="openRenameFolderModal(folder.id)"/>

                                    <ActionIconShare class="mr-2"
                                                     @click.prevent="openShareFolderModal(folder.id)"/>

                                    <ManageFolderIcon v-if="userFolderPermission.write"
                                                      class="mr-2"
                                                      @click.prevent="manageFolder(folder)"/>

                                    <a :href="route('folder.zip', folder.id)">
                                        <ActionIconZip class="mr-2"/>
                                    </a>

                                    <ActionIconDelete class="mr-2"
                                                      @click.prevent="openDeleteFolderModal(folder)"/>
                                </div>
                            </div>

                            <!-- 2) visualizzazione file -->
                            <div v-for="file in files.data"
                                 class="grid grid-cols-8 gap-3 py-1 px-4 sm:px-6 text-sm border-b">
                                <div
                                    class="overflow-hidden col-span-3 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex">
                                    <FileIcon class="inline-block my-auto mr-3"/>
                                    <!--                                @click.prevent="openFile(file.id)"-->
                                    <p class="m-0 pt-0.5">
                                        <!--                                    @click.prevent="openFile(file.id)"-->
                                        {{ file.file_name }}
                                    </p>
                                </div>
                                <div class="col-span-1 whitespace-nowrap py-auto my-auto text-gray-500">
                                    <p class="m-0 pt-0.5">
                                        ----------
                                    </p>
                                </div>

                                <!-- div vuoto per creare spazio -->
                                <div class="col-span-1"/>

                                <div
                                    class="overflow-hidden col-span-3 relative whitespace-nowrap py-auto  text-left font-medium ">
                                    <ActionIconEdit v-if="userFolderPermission.write"
                                                    class="mr-2"
                                                    @click.prevent="openRenameFileModal(file.id)"/>

                                    <ActionIconShare class="mr-2"
                                                     @click.prevent="openShareFileModal(file.id)"/>

                                    <ManageFileIcon class="mr-2"
                                                    @click.prevent="manageFile(file)"/>

                                    <a :href="route('file.download', file.id)">
                                        <DownloadIcon class="mr-2"/>
                                    </a>

                                    <ActionIconDelete class="mr-2"
                                                      @click.prevent="openDeleteFileModal(file)"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--------------------        MODALI        -------------------->
        <!-- modale errore creazione cartella -->
        <JetConfirmationModal :show="folderCreationErrorModal"
                              @close.prevent="closeFolderCreationErrorModal()">
            <template #title>
            <span class="text-center">
                FOLDER CREATION ERROR
            </span>
            </template>
            <template #content>
                <span class="text-center">Warning, a folder with this name already exists in this path.</span>
            </template>
            <template #footer>
                <JetButton @click.prevent="closeFolderCreationErrorModal()"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Ok</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale cancellazione cartella -->
        <JetConfirmationModal :show="deleteFolderConfirmModal"
                              @close.prevent="closeDeleteFolderModal()">
            <template #title>
                <span class="text-center">
                    DELETE FOLDER
                </span>
            </template>
            <template #content>
                <span class="text-center">Are you sure you want to delete </span>
                <span class="text-center font-bold break-all">{{ folderToDelete.name }}</span>
                <span class="text-center">?</span>
                <div class="mt-4 font-bold break-all text-red-400">Warning, all subfolders and relative files will also
                    be deleted. The action is irreversible.
                </div>
            </template>
            <template #footer>
                <JetButton @click.prevent="closeDeleteFolderModal()"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Cancel</span>
                </JetButton>
                <JetButton @click.prevent="deleteFolder()"
                           class="bg-asgreen-200 text-white rounded-sm mb-3  cursor-pointer  px-6 py-2 hover:bg-gray-600">
                    <span>Delete</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale errore cancellazione cartella -->
        <JetConfirmationModal :show="folderDeletionErrorModal"
                              @close.prevent="closeFolderDeletionErrorModal()">
            <template #title>
            <span class="text-center">
                FOLDER DELETION ERROR
            </span>
            </template>
            <template #content>
                <span class="text-center">An error occurred during the folder deletion.</span>
            </template>
            <template #footer>
                <JetButton @click.prevent="closeFolderDeletionErrorModal()"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Ok</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale cancellazione file -->
        <JetConfirmationModal :show="deleteFileConfirmModal"
                              @close.prevent="closeDeleteFileModal()">
            <template #title>
                <span class="text-center">
                    DELETE FILE
                </span>
            </template>
            <template #content>
                <span class="text-center">Are you sure you want to delete  </span>
                <span class="text-center font-bold break-all">{{ fileToDelete.file_name }}</span>
                <span class="text-center">?</span>
                <div class="mt-4 font-bold break-all text-red-400">Warning, the action is irreversible.</div>
            </template>
            <template #footer>
                <JetButton @click.prevent="closeDeleteFileModal()"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Cancel</span>
                </JetButton>
                <JetButton @click.prevent="deleteFile()"
                           class="bg-asgreen-200 text-white rounded-sm mb-3  cursor-pointer  px-6 py-2 hover:bg-gray-600">
                    <span>Delete</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale errore cancellazione file -->
        <JetConfirmationModal :show="fileDeletionErrorModal"
                              @close.prevent="closeFileDeletionErrorModal()">
            <template #title>
            <span class="text-center">
                FILE DELETION ERROR
            </span>
            </template>
            <template #content>
                <span class="text-center">An error occurred during the file deletion.</span>
            </template>
            <template #footer>
                <JetButton @click.prevent="openFolderDeletionErrorModal()"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Ok</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale rinomina cartella -->
        <RenameFolderModal :folderId="renameFolderId"
                           :show="renameFolderModal"
                           @close="closeRenameFolderModal()"
        />

        <!-- modale rinomina file -->
        <RenameFileModal :folderId="renameFileId"
                         :show="renameFileModal"
                         @close="closeRenameFileModal()"
        />

        <!-- modale condivisione cartella -->
        <ShareFolderModal :folderId="shareFolderId"
                          :show="shareFolderModal"
                          @close="closeShareFolderModal()"
        />

        <!-- modale condivisione file -->
        <ShareFileModal :fileId="shareFileId"
                        :show="shareFileModal"
                        @close="closeShareFileModal()"
        />
    </AppLayout>
</template>

<script setup>
// Imports
import {router, useForm, usePage} from "@inertiajs/vue3";
import {computed, ref} from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import JetConfirmationModal from '@/Components/ConfirmationModal.vue';
import JetButton from '@/Components/PrimaryButton.vue';
import ActionIconDelete from '@/Components/Icons/ActionIconDelete.vue';
import IconFolder from '@/Components/Icons/FolderIcon.vue';
import FileIcon from '@/Components/Icons/FileIcon.vue';
import ActionIconZip from '@/Components/Icons/ActionIconZip.vue';
import ManageFileIcon from '@/Components/Icons/ManageFileIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import ActionIconEdit from '@/Components/Icons/ActionIconEdit.vue';
import ChevronLeftIcon from '@/Components/Icons/ChevronLeftIcon.vue';
import ManageFolderIcon from "@/Components/Icons/ManageFolderIcon.vue";
import RenameFolderModal from '@/Components/MyComponents/RenameFolderModal.vue';
import RenameFileModal from '@/Components/MyComponents/RenameFileModal.vue';
import ShareFolderModal from "@/Components/MyComponents/ShareFolderModal.vue";
import ShareFileModal from "@/Components/MyComponents/ShareFileModal.vue";
import ActionIconShare from "@/Components/Icons/ActionIconShare.vue";

// Shared props
const userFolderPermission = computed(() => usePage().props.folderPermission);
// console.log(userFolderPermission);

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
    //userOrganizationAdmin: Boolean,
    //userDepartment: Boolean,
});


///////////////////////////////////////////////////////////////////////////////////////////////
// AZIONI CARTELLE
const openFolder = (folderId = null) => {
    if (folderId != null) {
        // ritorna la cartella selezionata
        router.get(route('my-files'), {
            folderId: folderId
        });
    } else {
        // ritorna le cartelle di root
        router.get(route('my-files'));
    }
}

const deleteFolder = () => {
    console.log('Folder to delete: ' + folderToDelete.value);

    router.delete(route('folder.delete', folderToDelete.value.id), {
        onSuccess: () => {
            closeDeleteFolderModal();
        },
        onError: (error) => {
            console.log(error);

            if (error.folderDeletionError) {
                openFolderDeletionErrorModal();
            }
        }
    });
}

/* condividi cartella */
const shareFolder = (folderId) => {
    router.post(route('folder.share', folderId));
}

/* copia/spostamento cartella */
const manageFolder = (folder) => {
    router.get(route('backend.file-system.manage-folder'), {
        originalFolderId: folder.id,
        originalFolderPath: folder.fullPath
    });
}

///////////////////////////////////////////////////////////////////////////////////////////////
// AZIONI FILE
const deleteFile = () => {
    router.delete(route('file.delete', fileToDelete.value.id), {
        onSuccess: () => {
            closeDeleteFileModal();
        },
        onError: (error) => {
            console.log(error);

            if (error.fileDeletionError) {
                openFileDeletionErrorModal();
            }
        }
    });
}

// copia/spostamento file
const manageFile = (file) => {
    // props.folder esiste? Se sì prendi il full path, altrimenti stringa vuota
    let fileFullPath = props.folder ? props.folder.data.fullPath + '/' + file.file_name : '';
    console.log('manageFile: ', props.folder);

    router.get(route('backend.file-system.manage-file'), {
        originalFileId: file.id,
        originalFileFullPath: fileFullPath,
        originalFolderId: props.folder.data.id,
        originalFolderFullPath: props.folder.data.fullPath,
    });
}

// const openFile = (fileId) => {
//     router.get(route('backend.file-manager.open-file', fileId));
// }

///////////////////////////////////////////////////////////////////////////////////////////////
// AZIONI FORM
/* 1) new root folder form */
const rootFolderForm = useForm({
    _method: 'POST',
    newRootFolderName: '',
});

const submitRootFolderForm = () => {
    rootFolderForm.post(route('folder.create-root'), {
        onError: (error) => {
            rootFolderForm.reset('newRootFolderName');

            if (error.folderExistsError) {
                openFolderCreationErrorModal();
            }
        },
        onSuccess: (data) => {
            rootFolderForm.reset('newRootFolderName');

            console.log(data);
        }
    });
}

/* 2) new folder form */
const folderForm = useForm({
    _method: 'POST',
    newFolderName: '',
    currentFolderId: props.currentFolderId
})

const submitFolderForm = () => {
    folderForm.post(route('folder.create'), {
        onError: (error) => {
            console.log(error);

            folderForm.reset('newFolderName');

            if (error.folderExistsError) {
                openFolderCreationErrorModal();
            }
        },
        onSuccess: (data) => {
            folderForm.reset('newFolderName');

            console.log(data);
        }
    })
}

/* 3) file upload form */
const fileUploadForm = useForm({
    _method: 'POST',
    files: null,
    currentFolderId: props.currentFolderId
});

const submitFileUploadForm = () => {
    fileUploadForm.post(route('file.upload'));
}

const onChange = (event) => {
    console.log('event', event.target.files);
    // aggiornamento della variabile che contiene il file (dentro al form) con il file caricato
    fileUploadForm.files = event.target.files;

    console.log("uploadFiles: ", fileUploadForm.files);
}


///////////////////////////////////////////////////////////////////////////////////////////////
// HELPER MODALI
/* 1) modale errore creazione cartella */
let folderCreationErrorModal = ref(false);

const openFolderCreationErrorModal = () => {
    folderCreationErrorModal.value = true;
}

const closeFolderCreationErrorModal = () => {
    folderCreationErrorModal.value = false;
}

/* 2) modale cancellazione cartella */
let deleteFolderConfirmModal = ref(false);
let folderToDelete = ref(null);

function openDeleteFolderModal(folder) {
    deleteFolderConfirmModal.value = true;
    folderToDelete.value = folder;
}

function closeDeleteFolderModal() {
    deleteFolderConfirmModal.value = false;
    folderToDelete.value = null;
}

/* 3) modale errore cancellazione cartella */
let folderDeletionErrorModal = ref(false);

const openFolderDeletionErrorModal = () => {
    folderDeletionErrorModal.value = true;
}

const closeFolderDeletionErrorModal = () => {
    closeDeleteFolderModal();
    folderDeletionErrorModal.value = false;
}

/* 4) modale cancellazione file */
let deleteFileConfirmModal = ref(false);
let fileToDelete = ref(null);

function openDeleteFileModal(file) {
    deleteFileConfirmModal.value = true;
    fileToDelete.value = file;
}

function closeDeleteFileModal() {
    deleteFileConfirmModal.value = false;
    fileToDelete.value = null;
}

/* 5) modale errore cancellazione file */
let fileDeletionErrorModal = ref(false);

const openFileDeletionErrorModal = () => {
    fileDeletionErrorModal.value = true;
}

const closeFileDeletionErrorModal = () => {
    deleteFileConfirmModal.value = false;
    fileDeletionErrorModal.value = false;
}

/* 6) modale rinomina cartella */
let renameFolderModal = ref(false);
let renameFolderId = ref(null);

const openRenameFolderModal = (folderId) => {
    renameFolderModal.value = true;
    renameFolderId.value = folderId;
}

const closeRenameFolderModal = () => {
    renameFolderModal.value = false;
    renameFolderId.value = null;
}

/* 6) modale rinomina file */
let renameFileModal = ref(false);
let renameFileId = ref(null);

const openRenameFileModal = (fileId) => {
    console.log(renameFileModal.value);

    renameFileModal.value = true;
    renameFileId.value = fileId;

    console.log(renameFileModal.value);
}

const closeRenameFileModal = () => {
    renameFileModal.value = false;
    renameFileId.value = null;
}

/* 7) modale share folder */
let shareFolderModal = ref(false);
let shareFolderId = ref(null);

const openShareFolderModal = (folderId) => {
    shareFolderModal.value = true;
    shareFolderId.value = folderId;
}

const closeShareFolderModal = () => {
    shareFolderModal.value = false;
    shareFolderId.value = null;
}

/* 8) modale share file */
let shareFileModal = ref(false);
let shareFileId = ref(null);
const openShareFileModal = (fileId) => {
    shareFileModal.value = true;
    shareFileId.value = fileId;
}

const closeShareFileModal = () => {
    shareFileModal.value = false;
    shareFileId.value = null;
}

console.log(props);

</script>
