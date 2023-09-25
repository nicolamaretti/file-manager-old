<template>
    <!--    <div title="File">-->
    <!--        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">-->
    <!--            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />-->
    <!--        </svg>-->
    <!--    </div>-->

    <span class="w-6 h-6 inline-flex items-center justify-center mr-2">
        <img v-if="isImage(file)" :src="`/images/icon/image.png`" class="max-w-full">
        <img v-else-if="isPDF(file)" :src="`/images/icon/pdf.png`" class="max-w-full">
        <img v-else-if="isAudio(file)" :src="`/images/icon/audio.png`" class="max-w-full">
        <img v-else-if="isVideo(file)" :src="`/images/icon/video.png`" class="max-w-full">
        <img v-else-if="isWord(file)" :src="`/images/icon/word.png`" class="max-w-full">
        <img v-else-if="isExcel(file)" :src="`/images/icon/excel.png`" class="max-w-full">
        <img v-else-if="isZip(file)" :src="`/images/icon/zip.png`" class="max-w-full">
        <img v-else-if="isText(file)" :src="`/images/icon/txt-file.png`" class="max-w-full">
        <img v-else :src="`/images/icon/attach-file.png`" class="max-w-full">
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
