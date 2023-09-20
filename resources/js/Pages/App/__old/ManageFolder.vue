<template>
    <AppLayout title="Manage Folder">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Manage Folder
            </h2>
        </template>

        <div class="py-8">
            <!-- Azioni cartella -->
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="font-bold text-lg">
                        Selected folder -
                        <span class="text-cyan-600 font-light font-mono">{{ originalFolderPath }}</span>
                    </h3>

                    <!--                    <h3 v-if="selectedFolder" class="font-bold">-->
                    <!--                        Copia in: {{ selectedFolder.folderPath }}-->
                    <!--                    </h3>-->

                    <fieldset class="mt-7">
                        <span>
                            <label for="move">Move</label>
                            <input v-model="selectedAction" type="radio" id="move" value="move"
                                   class="ml-2 mb-0. cursor-pointer">
                        </span>
                        <span class="ml-10">
                            <label for="copy">Copy</label>
                            <input v-model="selectedAction" type="radio" id="copy" value="copy"
                                   class="ml-2 mb-0.5 cursor-pointer">
                        </span>
                    </fieldset>
                    <div class="mt-7">
                        <JetButton class="bg-red-400"
                                   @click.prevent="goBack('my-files')"
                        >
                            Cancel
                        </JetButton>

                        <JetButton :disabled="buttonDisabled"
                                   class="ml-4 bg-green-400 disabled:bg-gray-400"
                                   @click.prevent="openConfirmModal()"
                        >
                            Confirm
                        </JetButton>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="font-bold text-lg">Folders list:</h3>
                    <div class="pl-4">

                        <!-- Riga con back button -->
                        <div v-if="!folderIsRoot"
                             class="text-blue-600 font-semibold pl-5 cursor-pointer hover:text-blue-400">
                            <p v-if="parent != null"
                               @click.prevent=(openFolder(parent.id))
                            >
                                &#x3c; <span class="underline">Back</span>
                            </p>
                            <p v-else
                               @click.prevent=(openFolder())
                            >
                                &#x3c; <span class="underline">Back</span>
                            </p>
                        </div>

                        <!-- se è un utente normale mostro la sua foot folder -->
                        <div v-if="props.folder && !props.folders" class="inline-block">
                            <div class="whitespace-nowrap text-sm font-medium text-gray-900 inline-flex mt-1">
                                <input @click.prevent="selectFolder(props.folder.data)" type="radio" name="folder.name" id=folder.id
                                       value="folder" class="mt-1 cursor-pointer">

                                <IconFolder class="inline-block ml-3 my-auto cursor-pointer"
                                            @click.prevent="openFolder(props.folder.data.id)"
                                ></IconFolder>

                                <label
                                    class="cursor-pointer font-medium m-0 ml-2 pt-1 text-align:center hover:underline hover:text-blue-300"
                                    @click.prevent="openFolder(props.folder.data.id)"
                                >
                                    {{ folder.data.name }} &#x3e;
                                </label>
                            </div>
                        </div>

                        <!-- se sono admin o se sono un utente che sta navigando, mostro tutte le cartelle figlie della cartella corrente -->
                        <div v-else-if="folders.data.length > 0" class="inline-block">
                            <div v-for="folder in folders.data">
                                <div class="whitespace-nowrap text-sm font-medium text-gray-900 inline-flex mt-1">
                                    <input @click.prevent="selectFolder(folder)" type="radio" name="folder.name" id=folder.id
                                           value="folder" class="mt-1 cursor-pointer">

                                    <IconFolder class="inline-block ml-3 my-auto cursor-pointer"
                                                @click.prevent="openFolder(folder.id)"
                                    ></IconFolder>

                                    <label
                                        class="cursor-pointer font-medium m-0 ml-2 pt-1 text-align:center hover:underline hover:text-blue-300"
                                        @click.prevent="openFolder(folder.id)"
                                    >
                                        {{ folder.name }} &#x3e;
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- modale conferma azione -->
        <JetConfirmationModal :show="confirmModal" @close.prevent="closeConfirmModal()">
            <template #title>
            <span class="text-center">
                Are you sure you want to {{ selectedAction }} {{ originalFolderPath }} into {{ selectedFolder.folderFullPath }} ?
            </span>
            </template>
            <template #footer>
                <JetButton @click.prevent="closeConfirmModal()"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Cancel</span>
                </JetButton>
                <JetButton @click.prevent="confirmSelection()"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Confirm</span>
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- modale errore -->
        <JetConfirmationModal :show="errorModal" @close.prevent="closeErrorModal">
            <template #title>
            <span class="text-center">
                Error
            </span>
            </template>
            <template #content>
                <span
                    class="text-center"> {{ errorMessage }}</span>
            </template>
            <template #footer>
                <JetButton @click.prevent="closeErrorModal"
                           class="bg-asred-200 text-white cursor-pointer mb-3 mr-2 px-6 py-2 rounded-sm hover:bg-gray-600">
                    <span>Ok</span>
                </JetButton>
            </template>
        </JetConfirmationModal>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/__oldAppLayout.vue";
import {router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import JetButton from '@/Components/PrimaryButton.vue';
import IconFolder from "@/Components/Icons/FolderIcon.vue";
import JetConfirmationModal from "@/Components/ConfirmationModal.vue";

const props = defineProps({
    rootFolderId: Number,
    parent: Object,
    folders: Object,
    folder: Object,
    folderIsRoot: Boolean,
    originalFolderId: Number,
    originalFolderPath: String,
    originalFolderParent: Object, // per il redirect
});

const selectedFolder = ref(null);

const selectedAction = ref('');

const buttonDisabled = computed(() => {
    return (selectedFolder.value == null || selectedAction.value === '');
});

const goBack = (backRoute) => {
    // torno indietro aprendo la cartella padre di quella selezionata
    router.get(route(backRoute), {
        folderId: props.originalFolderParent.id,
    });
}

const openFolder = (folderId = null) => {
    if (folderId != null) {
        router.get(route('backend.file-system.manage-folder'), {
            folderId: folderId,
            originalFolderId: props.originalFolderId,
            originalFolderPath: props.originalFolderPath
        }, {
            only: ['parent', 'folders', 'folderIsRoot'],
        });
    } else {
        router.get(route('backend.file-system.manage-folder'), {
            originalFolderId: props.originalFolderId,
            originalFolderPath: props.originalFolderPath
        }, {
            only: ['parent', 'folders', 'folderIsRoot'],
        });
    }
}

const selectFolder = (folder) => {
    selectedFolder.value = {
        folderId: folder.id,
        folderFullPath: folder.fullPath,
    };
}

const confirmSelection = () => {
    router.post(route('backend.file-system.move-or-copy-folder'), {
        selectedAction: selectedAction.value,
        selectedFolderId: selectedFolder.value.folderId,
        originalFolderId: props.originalFolderId,
    }, {
        onError: (error) => {
            if (error.manageFolderError) {
                errorMessage.value = error.message;
                openErrorModal();
            }
        }
    });
}

/* modale conferma selezione */
let confirmModal = ref(false);

const openConfirmModal = () => {
    confirmModal.value = true;
}

const closeConfirmModal = () => {
    confirmModal.value = false;
}

/* modale errore */
let errorModal = ref(false);
let errorMessage = ref('');

const openErrorModal = () => {
    errorModal.value = true;
}

const closeErrorModal = () => {
    errorModal.value = false;
}

console.log(props);

</script>
