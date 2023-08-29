<script setup>
import {nextTick, onMounted, reactive, ref} from "vue";
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
        default: 'SHARE FOLDER'
    },
    content: {
        type: String,
        default: 'Please enter the email of the user you want to share the folder with.'
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
    email: '',
    error: '',
    processing: false
});

const inputEmail = ref(null);

const closeModal = () => {
    emit('close');
    form.email = '';
    form.error = '';
}

const confirmEmail = () => {
    console.log(props.folderId, form.email);
    router.post(route('backend.file-manager.share-folder', props.folderId), { email: form.email },
    {
        onSuccess: () => {
            closeModal();
            nextTick().then(() => emit('updated'))
        },
        onError: (error) => {
            if (error.missingEmail) {
                form.error = 'Please enter an email.';
            } else {
                form.error = error.message;
            }

            // reset
            form.email = '';
            inputEmail.value.focus();
        }
    });
}

// console.log(props);

</script>

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
                        ref="inputEmail"
                        v-model="form.email"
                        type="email"
                        class="mt-4 block w-3/4"
                        autofocus
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
                    @click.prevent="confirmEmail()"
                >
                    {{ buttonName }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </div>
</template>
