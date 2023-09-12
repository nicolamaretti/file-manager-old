<template>
    <DialogModal :show="props.modelValue" @show="onShow" max-width=lg>
        <template #title>
            Share Files
        </template>
        <template #content>
            <InputLabel for="shareEmail" value="Enter Email Address" class="sr-only"/>

            <TextInput type="text"
                       ref="emailInput"
                       id="shareEmail"
                       v-model="form.email"
                       :class="form.errors.email ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                       class="mt-1 block w-full"
                       placeholder="Enter Email Address"
                       @keyup.enter="share"
            />

            <InputError :message="form.errors.email" class="mt-2"/>
        </template>
        <template #footer>
            <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
            <PrimaryButton class="ml-3"
                           :class="{ 'opacity-25': form.processing }"
                           @click="share" :disable="form.processing">
                Submit
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
// Imports
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {useForm} from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {nextTick, ref} from "vue";
import DialogModal from "@/Components/DialogModal.vue";
// import {showSuccessNotification} from "@/event-bus.js";

// Props
const props = defineProps({
    modelValue: Boolean,
    shareFolderIds: Array,
    shareFileIds: Array,
});

const emit = defineEmits(['update:modelValue', 'share']);

const emailInput = ref(null);

const form = useForm({
    email: null,
    shareFolderIds: [],
    shareFileIds:[],
});

function onShow() {
    nextTick(() => emailInput.value.focus());
}

function share() {
    form.shareFileIds = props.shareFileIds;
    form.shareFolderIds = props.shareFolderIds;

    const email = form.email

    form.post(route('share'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            emit('share');
            form.reset();
            // showSuccessNotification(`Selected files will be shared to "${email}" if the emails exists in the system`)
        },
        onError: (error) => {
            form.errors.email = error.message;
            emailInput.value.focus()
        }
    });
}

function closeModal() {
    emit('update:modelValue');
    form.clearErrors();
    form.reset();
}

// Hooks
</script>

<style scoped>

</style>
