<template>
    <DialogModal :show="modelValue">
        <template #title>
            Move files to...
        </template>
        <template #content>
            <MoveFilesModalTable :move-file-ids="moveFileIds"
                                 :move-folder-ids="moveFolderIds"/>
        </template>
        <template #footer>
            <SecondaryButton @click="closeModal">
                Cancel
            </SecondaryButton>

            <PrimaryButton class="ml-3"
                           :class="{ 'opacity-25': selected }"
                           @click="move"
                           :disable="selected">
                Submit
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import MoveFilesModalTable from "@/Pages/MoveFilesModalTable.vue";
import DialogModal from "@/Components/DialogModal.vue";

const props = defineProps({
    modelValue: Boolean,
    moveFolderIds: Array,
    moveFileIds: Array,
});

const selected = ref(false);
const selectedFolders = ref({});

const emit = defineEmits(['update:modelValue', 'move']);

// Methods
// function toggleSelectFolder(folderId) {
//     onSelectFolderCheckboxChange(folderId);
// }
//
// function onSelectFolderCheckboxChange(folderId) {
//     // metto tutte le selected folders a false tranne quella selezionata
//     for (let folder of props.folders.data) {
//         selectedFolders.value[folder.id] = false;
//     }
//
//     selectedFolders.value[folderId] = true;
//
//     console.log(selectedFolders.value);
// }

function move() {
    router.post(route('move'), {
        preserveState: true,
        onSuccess: (data) => {
            console.log(data);

            emit('move');

            closeModal();

            // ToDo show success notification
        },
        onError: (error) => {
            console.log(error);

            // ToDo show error notification
        }
    });
}

function closeModal() {
    emit('update:modelValue');
    form.clearErrors();
    form.reset();
}
</script>
