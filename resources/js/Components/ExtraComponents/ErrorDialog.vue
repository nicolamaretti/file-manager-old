<template>
    <DialogModal :show="show" max-width="md">
        <template #title>
            Error
        </template>
        <template #content>
            {{ message }}
        </template>
        <template #footer>
            <PrimaryButton @click="close">OK</PrimaryButton>
        </template>
    </DialogModal>
<!--    <Modal :show="show" max-width="md">-->
<!--        <div class="p-6">-->
<!--            <h2 class="text-2xl mb-2 text-red-600 font-semibold">Error</h2>-->
<!--            <p>{{message}}</p>-->
<!--            <div class="mt-6 flex justify-end">-->
<!--                <PrimaryButton @click="close">OK</PrimaryButton>-->
<!--            </div>-->
<!--        </div>-->
<!--    </Modal>-->
</template>

<script setup>
// Imports
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {onMounted, ref} from "vue";
import {emitter, SHOW_ERROR_DIALOG} from "@/event-bus.js";
import DialogModal from "@/Components/DialogModal.vue";

// Refs
const show = ref(false);
const message = ref('')

// Props & Emit
const emit = defineEmits(['close'])

// Methods
function close(){
    show.value = false
    message.value = ''
}

// Hooks
onMounted(() => {
    emitter.on(SHOW_ERROR_DIALOG, ({message: msg}) => {
        show.value = true;
        message.value = msg
    })
})

</script>

<style scoped>

</style>
