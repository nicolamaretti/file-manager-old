<template>
    <DialogModal :show="modelValue" max-width="lg">
        <template #title>
            Rename
        </template>
        <template #content>
            <InputLabel for="newName" value="New Name" class="sr-only"/>

            <TextInput type="text"
                       ref="newNameInput"
                       id="newName"
                       v-model="newName"
                       class="mt-1 block w-full"
                       :class="errorMessage ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                       placeholder="Name"
                       autofocus
                       @keyup.enter="rename"
                       @keyup.esc="closeModal"
            />

            <InputError :message="errorMessage" class="mt-2"/>
        </template>
        <template #footer>
            <SecondaryButton @click="closeModal">
                Cancel
            </SecondaryButton>

            <PrimaryButton class="ml-3"
                           @click="rename">
                Submit
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
import {router} from "@inertiajs/vue3";
import {ref} from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DialogModal from "@/Components/DialogModal.vue";
import {showSuccessNotification} from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    modelValue: Boolean,
    folderId: Number,
    fileId: Number,
});

const emit = defineEmits(['update:modelValue', 'rename']);

// Refs
const newNameInput = ref(null);
const newName = ref('');
const errorMessage = ref('');

// Methods
function rename() {
    console.log('Rename');

    router.post(route('rename'),
        {
            newName: newName.value,
            folderId: props.folderId,
            fileId: props.fileId,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['currentFolder', 'rootFolders'],
            onSuccess: (data) => {
                console.log('renameSuccess', data);

                emit('rename');

                closeModal();

                showSuccessNotification('File renamed correctly');
            },
            onError: (errors) => {
                console.log('renameError', errors);

                errorMessage.value = errors.message;

                newNameInput.value.focus();
            }
        });
}

function closeModal() {
    emit('update:modelValue');
    newName.value = '';
    errorMessage.value = '';
}

</script>
