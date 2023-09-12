<template>
    <DialogModal :show="modelValue" @show="onShow" max-width="lg">
        <template #title>
            Copy File
        </template>
        <template #content>
            <InputLabel for="newFolderName" value="Folder Name" class="sr-only"/>

            <TextInput type="text"
                       ref="newFolderNameInput"
                       id="newFolderName" v-model="form.newFolderName"
                       class="mt-1 block w-full"
                       :class="form.errors.name ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                       placeholder="Name"
                       autofocus
                       @keyup.enter="copy"
                       @keyup.esc="closeModal"
            />

            <InputError :message="form.errors.name" class="mt-2"/>
        </template>
        <template #footer>
            <SecondaryButton @click="closeModal">
                Cancel
            </SecondaryButton>

            <PrimaryButton class="ml-3"
                           :class="{ 'opacity-25': form.processing }"
                           @click="copy"
                           :disable="form.processing">
                Submit
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
import {router, useForm, usePage} from "@inertiajs/vue3";
import {nextTick, ref} from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DialogModal from "@/Components/DialogModal.vue";

const props = defineProps({
    modelValue: Boolean,
});

// prendo il currentFoldeId dalle props della pagina base
const page = usePage();
const currentFolder = page.props.currentFolder;

const emit = defineEmits(['update:modelValue', 'copy']);

const newFolderNameInput = ref(null);

const form = useForm({
    _method: 'POST',
    newFolderName: '',
    currentFolderId: currentFolder ? currentFolder.data.id : page.props.auth.user.root_folder_id,
    copy: true,
});

function onShow() {
    nextTick(() => newFolderNameInput.value.focus());
}

function copy() {
    console.log('Copy');

    form.post(route('createFolder'), {
        preserveState: true,
        onSuccess: (data) => {
            console.log(data);

            emit('copy');

            closeModal();

            // ToDo show success notification
        },
        onError: (error) => {
            console.log(error);

            form.errors.name = error.message;

            newFolderNameInput.value.focus();
        }
    })
}

function closeModal() {
    emit('update:modelValue');
    form.clearErrors();
    form.reset();
}

console.log(currentFolder);
</script>
