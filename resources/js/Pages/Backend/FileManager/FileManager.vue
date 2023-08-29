<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {router, useForm, usePage} from "@inertiajs/vue3";
import {computed, ref} from 'vue';
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
import RenameFolderModal from '@/Components/RenameFolderModal.vue';
import RenameFileModal from '@/Components/RenameFileModal.vue';
import ShareFolderModal from "@/Components/ShareFolderModal.vue";
import ShareFileModal from "@/Components/ShareFileModal.vue";
// import DialogModal from "@/Components/DialogModal.vue";
// import Checkbox from "@/Components/Checkbox.vue";
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
        router.get(route('backend.file-manager.index'), {
            folderId: folderId
        });
    } else {
        // ritorna le cartelle di root
        router.get(route('backend.file-manager.index'));
    }
}

const deleteFolder = () => {
    console.log('Folder to delete: ' + folderToDelete.value);

    router.delete(route('backend.file-manager.delete-folder', folderToDelete.value.id), {
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
    router.post(route('backend.file-manager.share-folder', folderId));
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
    router.delete(route('backend.file-manager.delete-file', fileToDelete.value.id), {
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

const openFile = (fileId) => {
    router.get(route('backend.file-manager.open-file', fileId));
}

///////////////////////////////////////////////////////////////////////////////////////////////
// AZIONI FORM
/* 1) new root folder form */
const rootFolderForm = useForm({
    _method: 'POST',
    newRootFolderName: '',
});

const submitRootFolderForm = () => {
    rootFolderForm.post(route('backend.file-manager.create-root-folder'), {
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
    folderForm.post(route('backend.file-manager.create-folder'), {
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
    file: null,
    currentFolderId: props.currentFolderId
});

const submitFileUploadForm = () => {
    fileUploadForm.post(route('backend.file-manager.upload-file'));
}

const uploadFile = (event) => {
    console.log('event', event.target.files[0]);
    // aggiornamento della variabile che contiene il file (dentro al form) con il file caricato
    fileUploadForm.file = event.target.files[0];

    console.log("uploadFile: ", fileUploadForm.file);
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

/* 7) modale share file */
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

<template>
    <AppLayout title="File Manager">
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 leading-tight">
                File manager
            </h2>
        </template>

        <!-- Sezione creazione cartelle e upload file -->
        <div class="bg-white shadow border-gray-300 border-t-2">
            <!-- 1.1) Creazione nuova cartella root (se Admin) -->
            <div v-if="rootFolderId == null && folderIsRoot" class="px-4 py-5 sm:p-6">
                <div class="sm:basis-1/2 px-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Create new root folder</h3>

                    <form @submit.prevent="submitRootFolderForm" class="mt-5 sm:flex sm:items-center">
                        <div class="w-full sm:max-w-xs">
                            <label for="newRootFolderName" class="sr-only"></label>
                            <input type="text" name="newRootFolderName" id="newRootFolderName"
                                   v-model="rootFolderForm.newRootFolderName"
                                   class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <button type="submit"
                                class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:mt-0 sm:w-auto">
                            Create
                        </button>
                    </form>
                </div>
            </div>

            <!-- 1.2) Creazione nuova cartella "normale" (se non Admin) -->
            <div v-else class="px-4 py-5 sm:p-6">
                <div v-if="userFolderPermission.write" class="grid grid-cols-2 sm:flex">
                    <div class="sm:basis-1/2 px-4">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Create new folder</h3>

                        <form @submit.prevent="submitFolderForm" class="mt-5 sm:flex sm:items-center">
                            <div class="w-full sm:max-w-xs">
                                <label for="newFolderName" class="sr-only"></label>
                                <input type="text" name="newFolderName" id="newFolderName"
                                       v-model="folderForm.newFolderName"
                                       class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                            <button type="submit"
                                    class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:mt-0 sm:w-auto">
                                Create
                            </button>
                        </form>
                    </div>

                    <!-- 2) Upload file -->
                    <div class="sm:basis-1/2 px-4">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Upload file</h3>

                        <form @submit.prevent="submitFileUploadForm" class="mt-5 sm:flex sm:items-center">
                            <div class="w-full sm:max-w-xs">
                                <label for="file" class="sr-only"></label>

                                <input type="file" name="file" id="file" ref="file" @change="uploadFile"
                                       class="block text-sm w-11/12 file:bg-asblue-200 file:hover:bg-asblue-100 file:font-regular file:text-sm file:py-1.5 file:ring-0 file:text-asblue-800 break-all file:px-3 file:mr-4 text-gray-900">
                            </div>
                            <button type="submit"
                                    class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:mt-0 sm:w-auto">
                                Upload
                            </button>
                        </form>
                    </div>
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
                        <div v-if="rootFolderId == null && folderIsRoot"
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
                        <div v-if="!folderIsRoot" class="whitespace-nowrap mt-1 py-1 pl-3 inline-flex items-center">
                            <div class="inline-block">
                                <ChevronLeftIcon v-if="parent != null"
                                                 @click=(openFolder(parent.id))></ChevronLeftIcon>
                                <ChevronLeftIcon v-else
                                                 @click=(openFolder())></ChevronLeftIcon>
                            </div>
                            <div class="inline-block font-semibold m-0 pb-1 pl-3">
                                <label>
                                    {{ currentFolderName }}
                                </label>
                            </div>
                        </div>

                        <!-- se sono Admin e sono nella root, mostro tutte le cartelle -->
                        <!-- Riga della tabella -->
                        <div v-if="rootFolderId == null && folderIsRoot"
                             v-for="folder in folders.data"
                             class="grid grid-cols-8 gap-3 py-1 px-4 sm:px-6 text-sm">
                            <div
                                class="overflow-hidden col-span-3 whitespace-nowrap py-auto my-auto align-middle font-medium text-gray-900 inline-flex">
                                <IconFolder class="inline-block my-auto mr-3 cursor-pointer"
                                            @click="openFolder(folder.id)"/>
                                <label class="cursor-pointer m-0 pt-1 text-align:center hover:underline"
                                       @click="openFolder(folder.id)">
                                    {{ folder.name }}
                                </label>
                            </div>
                            <div
                                class="overflow-hidden col-span-1 whitespace-nowrap pt-1 my-auto text-gray-500">
                                {{ folder.owner }}
                            </div>

                            <!-- div vuoto per creare spazio -->
                            <div class="col-span-1"/>

                            <div
                                class="col-span-3 relative whitespace-nowrap pt-1 my-auto text-left font-medium">
                                <ActionIconEdit v-if="isUserAdmin"
                                                class="mr-2"
                                                @click="openRenameFolderModal(folder.id)"/>

                                <a :href="route('backend.file-manager.zip-folder', folder)">
                                    <ActionIconZip class="mr-2"/>
                                </a>

                                <ActionIconDelete class="mr-2"
                                                  @click="openDeleteFolderModal(folder)"/>
                            </div>
                        </div>

                        <!-- in tutti gli altri casi, mostro sia le sottocartelle, sia i file che contiene la cartella corrente -->
                        <!-- 1) visualizzazione sottocartelle -->
                        <div v-else v-for="folder in folders.data"
                             class="grid grid-cols-8 gap-3 py-1 px-4 sm:px-6 text-sm">
                            <div
                                class="overflow-hidden col-span-3 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex">
                                <IconFolder class="inline-block mr-3 cursor-pointer"
                                            @click="openFolder(folder.id)"/>
                                <label class="cursor-pointer m-0 pt-1 text-align:center overflow-hidden hover:underline"
                                       @click="openFolder(folder.id)">
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
                                class="overflow-hidden col-span-3 relative whitespace-nowrap my-auto pt-1 py-auto text-left font-medium">
                                <ActionIconEdit v-if="userFolderPermission.write"
                                                class="mr-2"
                                                @click="openRenameFolderModal(folder.id)"/>

                                <ActionIconShare class="mr-2"
                                                 @click="openShareFolderModal(folder.id)"/>

                                <ManageFolderIcon v-if="userFolderPermission.write"
                                                  class="mr-2"
                                                  @click="manageFolder(folder)"/>

                                <a :href="route('backend.file-manager.zip-folder', folder)">
                                    <ActionIconZip class="mr-2"/>
                                </a>

                                <ActionIconDelete class="mr-2"
                                                  @click="openDeleteFolderModal(folder)"/>
                            </div>
                        </div>

                        <!-- 2) visualizzazione file -->
                        <div class="grid grid-cols-8 gap-3 py-1.5 px-4 sm:px-6 text-sm" v-for="file in files.data">
                            <div
                                class="overflow-hidden col-span-3 whitespace-nowrap align-middle my-auto font-medium text-gray-900 inline-flex">
                                <FileIcon class="inline-block my-auto mr-3 cursor-pointer"
                                          @click.prevent="openFile(file.id)"></FileIcon>
                                <p class="cursor-pointer m-0 pt-0.5"
                                   @click.prevent="openFile(file.id)">
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
                                                @click="openRenameFileModal(file.id)"/>

                                <ActionIconShare class="mr-2"
                                                 @click="openShareFileModal(file.id)"/>

                                <ManageFileIcon class="mr-2"
                                                @click.prevent="manageFile(file)"/>

                                <a :href="route('backend.file-manager.download-file', file)">
                                    <DownloadIcon class="mr-2"/>
                                </a>

                                <ActionIconDelete class="mr-2"
                                                  @click="openDeleteFileModal(file)"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sezione visualizzazione file e cartelle condivise per un utente (non admin) -->
        <!--        <div v-if="!isUserAdmin"-->
        <!--             class="m-0 sm:px-10 lg:px-10">-->
        <!--            <div class="grid grid-cols-2">-->
        <!--                <div class="">-->
        <!--                    <label class="text-gray-800 font-extrabold text-lg">SHARED FOLDERS</label>-->
        <!--                </div>-->
        <!--                <div class="text-right pt-0.5">-->
        <!--                    <label class="text-blue-400 font-light text-sm hover:underline hover:text-blue-600 cursor-pointer"-->
        <!--                           @click="openSelectSharedFolders()"-->
        <!--                    >-->
        <!--                        Add-->
        <!--                    </label>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--            <div class="mt-2 min-w-full divide-y divide-gray-300 bg-gray-50">-->
        <!--                &lt;!&ndash; Tabella &ndash;&gt;-->
        <!--                <div-->
        <!--                    class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg mb-20 min-w-full divide-y divide-gray-300 bg-gray-100">-->
        <!--                    &lt;!&ndash; Header &ndash;&gt;-->
        <!--                    <div-->
        <!--                        class="grid grid-cols-2 gap-3 px-4 sm:px-6 py-3.5 text-left text-sm font-semibold text-gray-900">-->
        <!--                        &lt;!&ndash; prima colonna &ndash;&gt;-->
        <!--                        <div>Name</div>-->
        <!--                        &lt;!&ndash; seconda colonna &ndash;&gt;-->
        <!--                        <div>Owner</div>-->
        <!--                    </div>-->
        <!--                    &lt;!&ndash; Riga della tabella &ndash;&gt;-->
        <!--                    <div class="divide-y divide-gray-200 bg-white">-->
        <!--                        <div v-for="folder in sharedFolders.data"-->
        <!--                             class="grid grid-cols-2 gap-3 py-4 px-4 sm:px-6 text-sm">-->
        <!--                            <div-->
        <!--                                class="overflow-hidden whitespace-nowrap py-auto my-auto align-middle font-medium text-gray-900 inline-flex">-->
        <!--                                <IconFolder class="inline-block my-auto mr-3"/>-->
        <!--                                <label class="m-0 pt-1 text-align:center">-->
        <!--                                    {{ folder.name }}-->
        <!--                                </label>-->
        <!--                            </div>-->
        <!--                            <div-->
        <!--                                class="overflow-hidden col-span-1 whitespace-nowrap pt-1 my-auto text-gray-500">-->
        <!--                                {{ folder.owner }}-->
        <!--                            </div>-->
        <!--                        </div>-->
        <!--                    </div>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--        </div>-->

        <!--------------------        MODALI        -------------------->
        <!-- modale errore creazione cartella -->
        <JetConfirmationModal :show="folderCreationErrorModal"
                              @close="closeFolderCreationErrorModal">
            <template #title>
            <span class="text-center">
                FOLDER CREATION ERROR
            </span>
            </template>
            <template #content>
                <span class="text-center">Warning, a folder with this name already exists in this path.</span>
            </template>
            <template #footer>
                <JetButton @click="closeFolderCreationErrorModal"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Ok</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale cancellazione cartella -->
        <JetConfirmationModal :show="deleteFolderConfirmModal"
                              @close="closeDeleteFolderModal">
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
                <JetButton @click="closeDeleteFolderModal"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Cancel</span>
                </JetButton>
                <JetButton @click.prevent="deleteFolder"
                           class="bg-asgreen-200 text-white rounded-sm mb-3  cursor-pointer  px-6 py-2 hover:bg-gray-600">
                    <span>Delete</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale errore cancellazione cartella -->
        <JetConfirmationModal :show="folderDeletionErrorModal"
                              @close="closeFolderDeletionErrorModal">
            <template #title>
            <span class="text-center">
                FOLDER DELETION ERROR
            </span>
            </template>
            <template #content>
                <span class="text-center">An error occurred during the folder deletion.</span>
            </template>
            <template #footer>
                <JetButton @click="closeFolderDeletionErrorModal"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Ok</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale cancellazione file -->
        <JetConfirmationModal :show="deleteFileConfirmModal"
                              @close="closeDeleteFileModal">
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
                <JetButton @click="closeDeleteFileModal"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Cancel</span>
                </JetButton>
                <JetButton @click.prevent="deleteFile"
                           class="bg-asgreen-200 text-white rounded-sm mb-3  cursor-pointer  px-6 py-2 hover:bg-gray-600">
                    <span>Delete</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale errore cancellazione file -->
        <JetConfirmationModal :show="fileDeletionErrorModal"
                              @close="closeFileDeletionErrorModal">
            <template #title>
            <span class="text-center">
                FILE DELETION ERROR
            </span>
            </template>
            <template #content>
                <span class="text-center">An error occurred during the file deletion.</span>
            </template>
            <template #footer>
                <JetButton @click="openFolderDeletionErrorModal"
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
