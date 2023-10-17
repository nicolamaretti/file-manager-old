<template>
    <AppLayout title="Move">
        <nav class="flex items-center justify-between mt-2 mb-3 border-b">
            <h2 class="mt-1 mb-1">
                Move into...
            </h2>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
            <table class="min-w-full border shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                <thead class="bg-gray-100 border-b sm:rounded-lg">
                    <tr>
                        <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left w-[40px] max-w-[40px] pr-0">

                        </th>
                        <th class="px-6 py-4 text-sm font-medium text-left text-gray-900">
                            Name
                        </th>
                        <th class="px-6 py-4 text-sm font-medium text-left text-gray-900">
                            Path
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="folder in folders" :class="(selectedFolder[folder.id]) ? 'bg-blue-50' : 'bg-white'"
                        class="transition duration-300 ease-in-out border-b cursor-pointer hover:bg-blue-100"
                        @click="toggleSelect(folder.id)">
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[40px] max-w-[40px] pr-0">
                            <Checkbox v-model="selectedFolder[folder.id]" :checked="selectedFolder[folder.id]" class="mr-4"
                                @change="onSelectCheckboxChange(folder.id)" />
                        </td>
                        <td class="flex items-center px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            <FileIcon :file="folder" class="mr-3" />
                            {{ folder.name }}
                        </td>
                        <td class="items-center px-6 py-4 text-sm font-medium text-gray-400 whitespace-nowrap">
                            {{ folder.path }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="flex justify-center mt-6 mb-6 space-x-10">
                <SecondaryButton @click.prevent="goBack">
                    Cancel
                </SecondaryButton>

                <PrimaryButton :class="{ 'opacity-25': !selected }" :disable="!selected" class="ml-3" @click="move">
                    Move
                </PrimaryButton>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
// Imports
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import Checkbox from "@/Components/Checkbox.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { showErrorNotification, showSuccessNotification } from "@/event-bus.js";
import FileIcon from "@/Components/Icons/FileIcon.vue";

// Props & Emit
const props = defineProps({
    moveFileIds: Array,
    folders: Object,
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
function toggleSelect(folderId) {
    onSelectCheckboxChange(folderId);
}

function onSelectCheckboxChange(folderId) {
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
            'moveFileIds': props.moveFileIds,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['folders', 'files'],
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
