<template>
    <AppLayout_new title="NewSharedWithMe">
        <nav class="flex justify-end mb-3 mt-1">
            <div class="flex">
                <DownloadFilesButton class="mr-3"/>
<!--                <DeleteFilesButton/>-->
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
import {ref} from "vue";
import AppLayout_new from "@/Layouts/AppLayout_new.vue";
import DownloadFilesButton from "@/Components/ExtraComponents/DownloadFilesButton.vue";
import SharedWithMeTable from "@/Components/MyComponents/SharedWithMeTable.vue";
import DeleteFilesButton from "@/Components/ExtraComponents/DeleteFilesButton.vue";

const props = defineProps({
    folders: Array,
    files: Array,
});

</script>
