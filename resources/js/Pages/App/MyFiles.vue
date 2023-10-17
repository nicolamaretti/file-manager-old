<template>
    <AppLayout title="MyFiles">
        <nav class="flex items-center justify-between mt-1 mb-3">
            <Breadcrumb :ancestors="ancestors" />

            <!-- Bottoni -->
            <div class="flex">
                <RenameFileButton v-if="selectedFileIds.length === 1" :file-id="Number(selectedFileIds[0])"
                    @restore="onRestore" />
                <CopyFilesButton v-if="selectedFileIds.length > 0" :file-ids="selectedFileIds" @copy="onRestore" />
                <MoveFilesButton v-if="selectedFileIds.length > 0"
                    :current-folder-id="currentFolder ? currentFolder.data.id : null" :file-ids="selectedFileIds" />
                <ShareFilesButton :file-ids="selectedFileIds" @restore="onRestore" />
                <DownloadFilesButton :file-ids="selectedFileIds" @download="onRestore" />
                <DeleteFilesButton :file-ids="selectedFileIds" @delete="onRestore" />
            </div>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 mb-1 overflow-auto">
            <table class="min-w-full border shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                <thead class="bg-gray-100 border-b sm:rounded-lg">
                    <tr>
                        <th class="text-sm font-semibold text-gray-900 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0">
                            <Checkbox v-model:checked="allSelected" @change="onSelectAllChange" />
                        </th>
                        <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left w-[30px] max-w-[30px]">

                        </th>
                        <th class="px-6 py-4 text-sm font-semibold text-left text-gray-900">
                            Name
                        </th>
                        <th class="px-6 py-4 text-sm font-semibold text-left text-gray-900">
                            Owner
                        </th>
                        <!-- <th class="px-6 py-4 text-sm font-semibold text-left text-gray-900">
                            Path
                        </th> -->
                        <th class="px-6 py-4 text-sm font-semibold text-left text-gray-900">
                            Last Modified
                        </th>
                        <th class="px-6 py-4 text-sm font-semibold text-left text-gray-900">
                            Size
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file in allFiles.data" :key="file.id"
                        :class="(selectedFiles[file.id] || allSelected) ? 'bg-blue-50' : 'bg-white'"
                        class="transition duration-300 ease-in-out border-b cursor-pointer hover:bg-blue-100"
                        @dblclick.prevent="openFolder(file)" @click="toggleSelectFile(file.id)">
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                            <Checkbox v-model="selectedFiles[file.id]" :checked="selectedFiles[file.id] || allSelected"
                                class="mr-4" @change="onSelectCheckboxChange(file.id)" />
                        </td>
                        <td class="px-6 py-4 max-w-[40px] text-sm font-medium text-yellow-500"
                            @click.stop.prevent="addRemoveFavorite(file.id)">
                            <svg v-if="!file.is_favorite" class="w-6 h-6" fill="none" stroke="currentColor"
                                stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path clip-rule="evenodd"
                                    d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"
                                    fill-rule="evenodd" />
                            </svg>
                        </td>
                        <td class="flex items-center px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            <FileIcon :file="file" class="mr-3" />
                            {{ file.name }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ file.owner === page.props.auth.user.name ? 'me' : file.owner }}
                        </td>
                        <!-- <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ file.path }}
                        </td> -->
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ file.updated_at }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ file.size }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!allFiles.data.length" class="py-8 text-sm text-center text-gray-400">
                There is no data in this folder
            </div>

            <div ref="loadMoreIntersect"></div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, onUpdated, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ShareFilesButton from "@/Components/MyComponents/ShareFilesButton.vue";
import DownloadFilesButton from "@/Components/MyComponents/DownloadFilesButton.vue";
import DeleteFilesButton from "@/Components/MyComponents/DeleteFilesButton.vue";
import Breadcrumb from "@/Components/MyComponents/Breadcrumb.vue";
import Checkbox from "@/Components/Checkbox.vue";
import FileIcon from "@/Components/Icons/FileIcon.vue";
import RenameFileButton from "@/Components/MyComponents/RenameFileButton.vue";
import CopyFilesButton from "@/Components/MyComponents/CopyFilesButton.vue";
import MoveFilesButton from "@/Components/MyComponents/MoveFilesButton.vue";
import { showErrorDialog, showSuccessNotification } from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    currentFolder: Object,
    userIsAdmin: Boolean,
    rootFolders: Object,
    ancestors: Array,
});

// Uses
const page = usePage();

// Computed
const selectedFileIds = computed(() => Object.entries(selectedFiles.value).filter(a => a[1]).map(a => a[0]));

// Refs
const selectedFiles = ref({});
const allSelected = ref(false);
const loadMoreIntersect = ref(null);

const allFiles = ref({
    data: props.currentFolder ? props.currentFolder.data.files : props.rootFolders.data
});

// Methods
function openFolder(file = null) {
    if (!file.is_folder) {
        return;
    }

    console.log('openFolder');

    router.get(route('my-files'), {
        'folderId': file.id,
    }, {
        preserveScroll: true,
        only: ['currentFolder', 'ancestors'],
        onSuccess: () => {
            console.log('openFolderSuccess', props.currentFolder);
        },
        onError: (errors) => {
            console.log('openFolderErrors', errors);
        }
    });
}

function onSelectAllChange() {
    for (const file of allFiles.value.data) {
        selectedFiles.value[file.id] = allSelected.value;
    }

    console.log(selectedFiles.value)
}

function toggleSelectFile(id) {
    selectedFiles.value[id] = !selectedFiles.value[id];

    onSelectCheckboxChange(id);
}

function onSelectCheckboxChange(id) {
    if (!selectedFiles.value[id]) {
        allSelected.value = false;
    } else {
        let checked = true;

        // controllo se almeno un file è false
        for (const file of allFiles.value.data) {
            if (!selectedFiles.value[file.id]) {
                checked = false;
                break;
            }
        }

        allSelected.value = checked;
    }

    console.log(selectedFiles.value)
}

function addRemoveFavorite(id) {
    console.log('addRemoveFavorite');

    router.post(route('add-remove-favorites'),
        {
            fileId: id
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['currentFolder', 'rootFolders'],
            onSuccess: (data) => {
                console.log('addRemoveFavoriteSuccess', data);

                showSuccessNotification('Selected file has been added/removed to favorites');
            },
            onError: (errors) => {
                console.log('addRemoveFavoriteError', errors);

                showErrorDialog('Error trying to add selected file to favorites. Please try again later.')
            },
        });
}

function onRestore() {
    allSelected.value = false;
    selectedFiles.value = {};
}

function loadMore() {
    console.log("load more");
    // console.log(props.files.links.next);
    //
    // if (props.files.links.next === null) {
    //     return;
    // }
    //
    // router.get(props.files.links.next, {}, {
    //     preserveState: true,
    //     only: ['files', 'folders'],
    //     onSuccess: (data) => {
    //         console.log(data, props)
    //     }
    // });

    // httpGet(props.files.links.next)
    //     .then(res => {
    //         console.log(res)
    //         // props.files.data = [...props.files.data, ...res.data]
    //         // props.files.links.next = res.links.next
    //     });
    console.log('end')
}

onUpdated(() => {
    allFiles.value = {
        data: props.currentFolder ? props.currentFolder.data.files : props.rootFolders.data
    };

    allSelected.value = false;
    selectedFiles.value = {};
});

onMounted(() => {
    // const observer = new IntersectionObserver((entries) => entries.forEach(entry => entry.isIntersecting && loadMore()), {
    //     rootMargin: '-250px 0px 0px 0px'
    // });
    //
    // observer.observe(loadMoreIntersect.value)
});

console.log('MyFiles', props)
</script>
