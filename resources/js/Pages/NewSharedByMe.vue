<template>
    <AppLayout_new title="NewSharedByMe">
        <nav class="flex items-center justify-end mb-3 mt-1">
            <div class="flex">
                <DownloadFilesButton class="mr-3"/>
                <DeleteFilesButton/>
            </div>
        </nav>

        <!-- Tabella -->
        <div class="flex-1 overflow-auto">
            <SharedByMeTable :folders="folders"
                             :files="files"/>
        </div>
    </AppLayout_new>
</template>

<script setup>
import AppLayout_new from "@/Layouts/AppLayout_new.vue";
import DownloadFilesButton from "@/Components/ExtraComponents/DownloadFilesButton.vue";
import DeleteFilesButton from "@/Components/ExtraComponents/DeleteFilesButton.vue";
import SharedByMeTable from "@/Components/MyComponents/SharedByMeTable.vue";

const props = defineProps({
    folders: Array,
    files: Array,
});

</script>
