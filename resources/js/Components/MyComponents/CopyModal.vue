<template>
    <DialogModal :show="modelValue" max-width="lg">
        <template #title>
            Copy File
        </template>
        <template #content>
            <InputLabel for="newFolderName" value="Folder Name" class="sr-only"/>

            <TextInput type="text"
                       ref="folderNameInput"
                       id="newFolderName" v-model="folderName"
                       class="mt-1 block w-full"
                       :class="errorMessage ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                       placeholder="Name"
                       autofocus
                       @keyup.enter="copy"
                       @keyup.esc="closeModal"
            />

            <InputError :message="errorMessage" class="mt-2"/>
        </template>
        <template #footer>
            <SecondaryButton @click="closeModal">
                Cancel
            </SecondaryButton>

            <PrimaryButton class="ml-3"
                           @click="copy">
                Submit
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
import {router, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DialogModal from "@/Components/DialogModal.vue";

// Props & Emit
const props = defineProps({
    modelValue: Boolean,
    copyFolderIds: Array,
    copyFileIds: Array,
});

const emit = defineEmits(['update:modelValue', 'copy']);

// Refs
const folderNameInput = ref(null);
const folderName = ref('');
const errorMessage = ref('');

// prendo il currentFolderId dalle props della pagina base
const page = usePage();

// Methods
function copy() {
    console.log('Copy');

    router.post(route('copy'),
        {
            newFolderName: folderName.value,
            currentFolderId: page.props.currentFolder ? page.props.currentFolder.data.id : page.props.auth.user.root_folder_id,
            copyFileIds: props.copyFileIds,
            copyFolderIds: props.copyFolderIds
        },
        {
            preserveState: true,
            onSuccess: (data) => {
                console.log('copySuccess', data);

                emit('copy');

                closeModal();

                // ToDo show success notification
            },
            onError: (error) => {
                console.log('copyError', error);

                errorMessage.value = error.message;

                folderNameInput.value.focus();
            }
        });
}

function closeModal() {
    emit('update:modelValue');
    folderName.value = '';
    errorMessage.value = '';
}
</script>
