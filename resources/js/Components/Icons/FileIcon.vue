<template>
    <span class="w-6 h-6 inline-flex items-center justify-center mr-2">
        <svg v-if="file.is_folder" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5"
             viewBox="0 0 24 24"
             xmlns="http://www.w3.org/2000/svg">
            <path
                d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"
                stroke-linecap="round"
                stroke-linejoin="round"/>
        </svg>

        <template v-else>
            <img v-if="isImage(file)" :src="`/images/icon/image.png`" alt="image" class="max-w-full">
            <img v-else-if="isPDF(file)" :src="`/images/icon/pdf.png`" alt="pdf" class="max-w-full">
            <img v-else-if="isAudio(file)" :src="`/images/icon/audio.png`" alt="audio" class="max-w-full">
            <img v-else-if="isVideo(file)" :src="`/images/icon/video.png`" alt="video" class="max-w-full">
            <img v-else-if="isWord(file)" :src="`/images/icon/word.png`" alt="word" class="max-w-full">
            <img v-else-if="isExcel(file)" :src="`/images/icon/excel.png`" alt="excel" class="max-w-full">
            <img v-else-if="isZip(file)" :src="`/images/icon/zip.png`" alt="zip" class="max-w-full">
            <img v-else-if="isText(file)" :src="`/images/icon/txt-file.png`" alt="text" class="max-w-full">
            <img v-else :src="`/images/icon/attach-file.png`" alt="attach-file" class="max-w-full">
        </template>
    </span>
</template>

<script setup>
// Imports
import {isAudio, isExcel, isImage, isPDF, isText, isVideo, isWord, isZip} from "@/Helper/file-helper.js";

// Props & Emit
const props = defineProps({
    file: Object,
});

</script>
