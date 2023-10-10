<template>
    <MenuItem v-slot="{ active }">
    <a href="#" class="relative block px-4 py-2 text-sm text-gray-700">
        Upload Files
        <input @change="onChange" type="file" class="absolute top-0 bottom-0 left-0 right-0 opacity-0 cursor-pointer" multiple>
    </a>
    </MenuItem>
</template>

<script setup>
import { MenuItem } from "@headlessui/vue";
import { emitter, FILE_UPLOAD_STARTED } from "@/event-bus.js";

function onChange(ev) {
    emitter.emit(FILE_UPLOAD_STARTED, ev.target.files);
}
</script>
