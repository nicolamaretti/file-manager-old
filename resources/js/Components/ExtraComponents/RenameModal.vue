<template>
    <DialogModal :show="modelValue" @show="onShow" max-width="lg">
        <template #title>
            Rename
        </template>
        <template #content>
            <InputLabel for="newName" value="Folder Name" class="sr-only"/>

            <TextInput type="text"
                       ref="newNameInput"
                       id="newName" v-model="form.newName"
                       class="mt-1 block w-full"
                       :class="form.errors.name ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                       placeholder="Name"
                       autofocus
                       @keyup.enter="rename"
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
                           @click="rename"
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
    folderId: Number,
    fileId: Number,
});

const emit = defineEmits(['update:modelValue', 'rename']);

const newNameInput = ref(null);

const form = useForm({
    _method: 'POST',
    newName: '',
    folderId: null,
    fileId: null,
});

function onShow() {
    console.log('onShow');

    nextTick(() => newNameInput.value.focus());
}

function rename() {
    console.log('Rename');

    form.folderId = props.folderId;
    form.fileId = props.fileId;

    console.log('pippoooooooo', form.folderId, form.fileId);

    form.post(route('rename'), {
        preserveState: true,
        onSuccess: (data) => {
            console.log(data);

            emit('rename');

            closeModal();

            // ToDo show success notification
        },
        onError: (error) => {
            console.log(error);

            form.errors.name = error.message;

            newNameInput.value.focus();
        }
    });
}

function closeModal() {
    emit('update:modelValue');
    form.clearErrors();
    form.reset();
}

</script>
