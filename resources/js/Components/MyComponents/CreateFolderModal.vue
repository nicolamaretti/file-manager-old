<template>
    <DialogModal :show="modelValue" max-width="lg">
        <template #title>
            Create New Folder
        </template>
        <template #content>
            <InputLabel for="folderName" value="Folder Name" class="sr-only" />

            <TextInput type="text" ref="folderNameInput" v-model="folderName" id="folderName" class="block w-full mt-1"
                :class="errorMessage ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                placeholder="Folder Name" @keyup.enter.prevent="createFolder" @keyup.esc="closeModal" />

            <InputError :message="errorMessage" class="mt-2" />
        </template>
        <template #footer>
            <SecondaryButton @click="closeModal">
                Cancel
            </SecondaryButton>

            <PrimaryButton class="ml-3" @click="createFolder">
                Submit
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DialogModal from "@/Components/DialogModal.vue";
import { showSuccessNotification } from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    modelValue: Boolean,
});

const emit = defineEmits(['update:modelValue'])

// Refs
const folderNameInput = ref(null);
const folderName = ref('');
const errorMessage = ref('');

// Uses
const page = usePage();

// Computed
const userRootFolder = computed(() => page.props.userRootFolder);

// Methods
function createFolder() {
    console.log('Create Folder', folderName.value);

    const name = folderName.value;

    router.post(route('create-folder'),
        {
            newFolderName: folderName.value,
            currentFolderId: page.props.currentFolder ? page.props.currentFolder.data.id : userRootFolder.value.id
        },
        {
            preserveState: true,
            only: ['files', 'rootFolders'],
            onSuccess: (data) => {
                console.log('createFolderSuccess', data);

                closeModal();

                showSuccessNotification(`Folder '${name}' created successfully`);
            },
            onError: (errors) => {
                console.log('createFolderErrors', errors);

                errorMessage.value = errors.message;

                folderNameInput.value.focus();
            }
        });
}

function closeModal() {
    emit('update:modelValue');
    errorMessage.value = '';
    folderName.value = '';
}
</script>
