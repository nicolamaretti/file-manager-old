<template>
    <div>
        <DialogModal :show="show"
                     @close="closeModal()">
            <template #title>
                {{ title }}
            </template>

            <template #content>
                {{ content }}

                <div>
                    <TextInput
                        ref="inputName"
                        v-model="form.name"
                        type="text"
                        class="mt-4 block w-3/4"
                        @keyup.enter="confirmName()"
                        @keyup.esc="closeModal()"
                    />

                    <InputError :message="form.error" class="mt-2" />
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal()">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    class="ml-3"
                    :class="{ 'opacity-25':form.processing }"
                    :disabled="form.processing"
                    @click.prevent="confirmName()"
                >
                    {{ buttonName }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </div>
</template>

<script setup>
import {nextTick, reactive, ref} from "vue";
import DialogModal from '@/Components/DialogModal.vue';
import TextInput from "@/Components/TextInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import {router} from "@inertiajs/vue3";

const emit = defineEmits(['updated', 'close']);

const props = defineProps({
    title: {
        type: String,
        default: 'RENAME FILE'
    },
    content: {
        type: String,
        default: 'Please enter a new name for the selected file.'
    },
    buttonName: {
        type: String,
        default: 'Confirm'
    },
    show: {
        type: Boolean,
        default: false
    },
    folderId: {
        // type: Number,
        default: null,
        required: true
    }
});

const form = reactive({
    name: '',
    error: '',
    processing: false
});

const inputName = ref(null);

const closeModal = () => {
    emit('close');
    form.name = '';
    form.error = '';
}

const confirmName = () => {
    router.put(route('rename-file', props.folderId), { newName: form.name },
        {
            onSuccess: () => {
                closeModal();
                nextTick().then(() => emit('updated'))
            },
            onError: (error) => {
                if (error.missingName) {
                    form.error = 'Please enter a name.';
                }

                if (error.fileAlreadyExists) {
                    form.error = 'A file with this name already exists in this folder. Please choose another name or move the file into another folder.';
                }

                // reset
                form.name = '';
                inputName.value.focus();
            }
        });
}

// console.log(props);

</script>
