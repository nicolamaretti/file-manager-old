<template>
    <AppLayout_new title="NewMyFiles">
        <nav class="flex items-center justify-between mb-3 mt-1">
            <Breadcrumb/>
            <!-- Bottoni -->
            <div class="flex">
                <ShareFilesButton class="mr-3"/>
                <DownloadFilesButton class="mr-3"/>
                <DeleteFilesButton/>
            </div>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
            <MyFilesTable :files="allFiles.files"
                          :folders="allFiles.folders"
                          :currentFolder="props.currentFolder"
                          :rootFolderId="props.rootFolderId"/>
        </div>
    </AppLayout_new>
</template>

<script setup>
import {computed, onUpdated, ref} from "vue";
import {router, usePage, Link} from "@inertiajs/vue3";
import AppLayout_new from "@/Layouts/AppLayout_new.vue";
import ShareFilesButton from "@/Components/ExtraComponents/ShareFilesButton.vue";
import DownloadFilesButton from "@/Components/ExtraComponents/DownloadFilesButton.vue";
import DeleteFilesButton from "@/Components/ExtraComponents/DeleteFilesButton.vue";
import MyFilesTable from "@/Components/MyComponents/MyFilesTable.vue";
import Breadcrumb from "@/Components/MyComponents/Breadcrumb.vue";
import {all} from "axios";

const userFolderPermission = computed(() => usePage().props.folderPermission);

const props = defineProps({
    currentFolder: Object,
    rootFolderId: Number,
    isUserAdmin: Boolean,
    parent: Object,
    folders: Object,
    files: Object,
    folderIsRoot: Boolean,
});

const allFiles = ref({
    files: props.files,
    folders: props.folders,
});

onUpdated(() => {
    allFiles.value.files = props.files;
    allFiles.value.folders = props.folders;
});

</script>
