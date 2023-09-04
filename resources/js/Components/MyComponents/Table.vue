<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="mt-2 flow-root">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle px-1">
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-100">
              <tr>
                <th scope="col" class="py-3.5 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                  <Checkbox @change="onSelectAllChange" v-model:checked="allSelected"/>
                </th>
                <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                  <!-- Empty for the second column with the star -->
                </th>
                <th scope="col" class="pl-6 px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Name</th>
                <th v-if="rootFolderId == null" scope="col" class="pl-6 px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Owner</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Last Modified</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Size</th>
              </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
              <!-- Righe folders -->
              <tr v-if="folders"
                  v-for="folder in folders.data"
                  :key="folder.id"
                  class="transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                  @dblclick.prevent="openFolder(folder.id)">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                  <Checkbox/>
                </td>
                <td class="px-6 py-4 max-w-[40px] text-sm font-medium text-yellow-500">
                  <div @click.stop.prevent="addRemoveFavourite(folder)">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="1.5"
                         stroke="currentColor"
                         class="w-6 h-6">
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                  </div>
                </td>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ folder.name }}</td>
                <td v-if="rootFolderId == null" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ folder.owner }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ folder.updated_at }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">12345</td>
              </tr>
              <!-- Righe files -->
              <tr v-if="files"
                  v-for="file in files.data"
                  :key="file.id"
                  class="transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                  <Checkbox/>
                </td>
                <td class="px-6 py-4 max-w-[40px] text-sm font-medium text-yellow-500">
                  <div @click.stop.prevent="addRemoveFavourite(file)">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke-width="1.5"
                         stroke="currentColor"
                         class="w-6 h-6">
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                  </div>
                </td>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ file.file_name }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ file.updated_at }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ file.size }} KB</td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import {router, usePage} from "@inertiajs/vue3";

const page = usePage();

const propy = page.props;
const rootFolderId = propy.rootFolderId;
const folders = propy.folders;
const files = propy.files;

// const props = defineProps({
//   currentUserName: String,
//   currentFolderId: Number,
//   currentFolderName: String,
//   currentFolderFullPath: String,
//   rootFolderId: Number,
//   isUserAdmin: Boolean,
//   parent: Object,
//   folders: Object,
//   folder: Object,
//   files: Object,
//   folderIsRoot: Boolean,
// });

const openFolder = (folderId = null) => {
  if (folderId != null) {
    // ritorna la cartella selezionata
    router.get(route('new'), {
      folderId: folderId
    });
  } else {
    // ritorna le cartelle di root
    router.get(route('new'));
  }
}

console.log('propy');
console.log(propy);
console.log(folders);

</script>
