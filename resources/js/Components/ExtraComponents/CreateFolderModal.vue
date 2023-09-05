<template>
    <Modal :show="modelValue" @show="onShow()" max-width="lg">
        <div class="p-6">
            <!-- Titolo -->
            <h2 class="text-lg font-medium text-gray-900">
                Create New Folder
            </h2>

            <!-- Input -->
            <div class="mt-6">
                <InputLabel for="folderName" value="Folder Name" class="sr-only"/>

                <TextInput type="text"
                           ref="folderNameInput"
                           id="folderName" v-model="form.newFolderName"
                           class="mt-1 block w-full"
                           :class="form.errors.name ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                           placeholder="Folder Name"
                           autofocus
                           @keyup.enter="createFolder()"
                           @keyup.esc="closeModal()"
                />

                <InputError :message="form.errors.name" class="mt-2"/>
            </div>

            <!-- Bottoni -->
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal()">
                    Cancel
                </SecondaryButton>

                <PrimaryButton class="ml-3"
                               :class="{ 'opacity-25': form.processing }"
                               @click="createFolder()"
                               :disable="form.processing">
                    Submit
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import {router, useForm, usePage} from "@inertiajs/vue3";
import {nextTick, ref} from "vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    modelValue: Boolean,
});

const page = usePage();

// prendo il currentFoldeId dalle props della pagina base
const currentFolder = page.props.currentFolder;

const emit = defineEmits(['update:modelValue'])

const form = useForm({
    _method: 'POST',
    newFolderName: '',
    currentFolderId: currentFolder ? currentFolder.data.id : page.props.auth.user.root_folder_id,
});

const folderNameInput = ref(null)

function onShow() {
    console.log('onShow');

    nextTick(() => folderNameInput.value.focus())
}

function createFolder() {
    console.log('Create Folder');

    form.post(route('folder.create'), {
        preserveState: true,
        onSuccess: (data) => {
            console.log(data);

            closeModal();
        },
        onError: (error) => {
            console.log(error);

            form.reset('newFolderName');

            if (error.folderExistsError) {
                form.errors.name = 'Folder already exists';
            }
        }
    })
}

function closeModal() {
    emit('update:modelValue');
    form.clearErrors();
    form.reset();
}

</script>
