<template>
    <DialogModal :show="props.modelValue" max-width=lg>
        <template #title>
            Share
        </template>
        <template #content>
            <InputLabel for="shareEmail" value="Enter Email Address" class="sr-only"/>

            <TextInput type="text"
                       ref="emailInput"
                       id="shareEmail"
                       v-model="email"
                       :class="errorMessage ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                       class="mt-1 block w-full"
                       placeholder="Enter Email Address"
                       @keyup.enter="share"
            />

            <InputError :message="errorMessage" class="mt-2"/>
        </template>
        <template #footer>
            <SecondaryButton @click="closeModal">
                Cancel
            </SecondaryButton>
            <PrimaryButton class="ml-3"
                           @click="share">
                Submit
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {router} from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {ref} from "vue";
import DialogModal from "@/Components/DialogModal.vue";
import {showErrorNotification, showSuccessNotification} from "@/event-bus.js";

// Props & Emit
const props = defineProps({
    modelValue: Boolean,
    fileIds: Array,
});

const emit = defineEmits(['update:modelValue', 'restore']);

// Refs
const emailInput = ref(null);
const email = ref('');
const errorMessage = ref('');

// Methods
function share() {
    console.log('Share');

    const userEmail = email.value;

    router.post(route('share'),
        {
            email: email.value,
            fileIds: props.fileIds,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: [],
            onSuccess: (data) => {
                console.log('shareSuccess', data, email.value);

                closeModal();
                emit('restore');
                showSuccessNotification(`Selected files will be shared to ${userEmail}`);
            },
            onError: (errors) => {
                console.log('shareError', errors);

                if (errors.message) {
                    errorMessage.value = errors.message;

                    emailInput.value.focus();
                }

                 if (errors.error)  {
                     closeModal();
                     emit('restore');
                     showErrorNotification(errors.error);
                 }
            }
        });
}

function closeModal() {
    emit('update:modelValue');
    email.value = '';
    errorMessage.value = '';
}
</script>
