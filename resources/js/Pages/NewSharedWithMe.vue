<template>
    <AppLayout_new title="NewSharedWithMe">
        <nav class="flex items-center justify-between mb-3 mt-1">
            <Breadcrumb/>

            <div class="flex">
                <DownloadFilesButton class="mr-3"/>
                <DeleteFilesButton/>
            </div>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
            <SharedWithMeTable :folders="folders"
                               :files="files"/>
        </div>
    </AppLayout_new>
</template>

<script setup>
import {computed, ref} from "vue";
import {router, usePage, Link} from "@inertiajs/vue3";
import AppLayout_new from "@/Layouts/AppLayout_new.vue";
import DownloadFilesButton from "@/Components/ExtraComponents/DownloadFilesButton.vue";
import Breadcrumb from "@/Components/MyComponents/Breadcrumb.vue";
import SharedWithMeTable from "@/Components/MyComponents/SharedWithMeTable.vue";
import DeleteFilesButton from "@/Components/ExtraComponents/DeleteFilesButton.vue";

const props = defineProps({
    folders: Array,
    files: Array,
});

console.log(props);
</script>
