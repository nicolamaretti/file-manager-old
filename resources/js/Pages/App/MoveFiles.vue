<template>
    <AppLayout title="Move">
        <h2 class="mt-1">
            Move into...
        </h2>
        <nav class="flex items-center justify-between mb-3 mt-2 border-b">
            <Breadcrumb :ancestors="ancestors"/>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
            <table class="min-w-full shadow ring-1 ring-black ring-opacity-5 border sm:rounded-lg">
                <thead class="bg-gray-100 border-b sm:rounded-lg">
                <tr>
                    <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left w-[40px] max-w-[40px] pr-0">

                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Name
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Path
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="folder in folders"
                    :class="(selectedFolder[folder.id]) ? 'bg-blue-50' : 'bg-white'"
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    @click="toggleSelectFolder(folder.id)">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[40px] max-w-[40px] pr-0">
                        <Checkbox v-model="selectedFolder[folder.id]"
                                  :checked="selectedFolder[folder.id]"
                                  class="mr-4"
                                  @change="onSelectFolderCheckboxChange(folder.id)"/>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                        <FolderIcon class="mr-3"/>
                        {{ folder.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-400 items-center">
                        {{ folder.path }}
                    </td>
                </tr>
                </tbody>
            </table>

            <div class="mt-6 mb-6 flex justify-center space-x-10">
                <SecondaryButton @click.prevent="goBack">
                    Cancel
                </SecondaryButton>

                <PrimaryButton :class="{ 'opacity-25': !selected }"
                               :disable="!selected"
                               class="ml-3"
                               @click="move">
                    Move
                </PrimaryButton>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
// Imports
import {router, usePage} from "@inertiajs/vue3";
import {computed, ref} from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import Breadcrumb from "@/Components/ExtraComponents/Breadcrumb.vue";
import FolderIcon from "@/Components/Icons/FolderIcon.vue";
import Checkbox from "@/Components/Checkbox.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {showErrorNotification, showSuccessNotification} from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    moveFolderIds: Array,
    moveFileIds: Array,
    currentFolder: Object,
    folders: Object,
    ancestors: Array,
    currentFolderId: Number,
});

// Uses
const page = usePage();

// Computed
const moveIntoFolderId = computed(() => Object.entries(selectedFolder.value).filter(a => a[1]).map(a => a[0]));

// Refs
const selectedFolder = ref({});
const selected = ref(false);

// Methods
function toggleSelectFolder(folderId) {
    onSelectFolderCheckboxChange(folderId);
}

function onSelectFolderCheckboxChange(folderId) {
    // metto tutte le selected folders a false tranne quella selezionata
    for (let folder of props.folders) {
        selectedFolder.value[folder.id] = false;
    }

    selectedFolder.value[folderId] = !selectedFolder.value[folderId];

    selected.value = true;

    console.log(selectedFolder.value, moveIntoFolderId.value[0]);
}

function move() {
    console.log('Move')

    router.post(route('move'),
        {
            'moveIntoFolderId': moveIntoFolderId.value[0],
            'moveFolderIds': props.moveFolderIds,
            'moveFileIds': props.moveFileIds,
        },
        {
            onSuccess: () => {
                console.log('onMoveSuccess')

                showSuccessNotification('Selected files have been moved correctly');
            },
            onError: (errors) => {
                console.log('onMoveError', errors);

                showErrorNotification('An error occurred while trying to move selected files. Please try again later.')
            }
        });
}

function goBack() {
    router.get(route('my-files'), {
            'folderId': props.currentFolderId,
        });
}

console.log('MoveFiles', props);

</script>
