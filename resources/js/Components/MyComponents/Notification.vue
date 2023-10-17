<template>
    <transition enter-active-class="duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
        enter-to-class="translate-y-0 opacity-100 sm:scale-100" leave-active-class="duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100 sm:scale-100"
        leave-to-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95">
        <div v-if="show" class="fixed bottom-12 left-4 text-white py-2 px-4 rounded-lg shadow-md w-[220px] text-center"
            :class="{
                'bg-emerald-500': type === 'success',
                'bg-red-500': type === 'error'
            }">
            {{ message }}
        </div>
    </transition>
</template>
<script setup>
import { onMounted, ref } from "vue";
import { emitter, SHOW_NOTIFICATION } from "@/event-bus.js";

// Refs
const show = ref(false);
const type = ref('success');
const message = ref('');

// Methods
function close() {
    show.value = false;
    type.value = '';
    message.value = '';
}

// Hooks
onMounted(() => {
    let timeout;
    emitter.on(SHOW_NOTIFICATION, ({ type: t, message: msg }) => {
        show.value = true;
        type.value = t;
        message.value = msg;

        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(() => {
            close();
        }, 5000);
    });
});

</script>

<style scoped></style>
