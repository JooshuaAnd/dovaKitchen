<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    allergens: Array
});

const form = useForm({
    name: '',
    description: '',
    price: '',
    photo: null,
    is_active: true,
    allergen_ids: []
});

const submit = () => {
    form.post(route('admin.menus.store'));
};
</script>

<template>
    <Head title="Buat Menu" />

    <AdminLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-black text-gray-900">Buat Menu Baru</h1>
                <Link :href="route('admin.menus.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">Kembali ke Menu</Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nama Menu</label>
                                <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Harga (Rp)</label>
                                <input v-model="form.price" type="number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <div v-if="form.errors.price" class="text-red-500 text-xs mt-1">{{ form.errors.price }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Foto (Opsional)</label>
                                <input @input="form.photo = $event.target.files[0]" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <div v-if="form.errors.photo" class="text-red-500 text-xs mt-1">{{ form.errors.photo }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bahan Baku</label>
                                <div class="space-y-2 max-h-40 overflow-y-auto p-2 border border-gray-200 rounded-md">
                                    <div v-for="allergen in allergens" :key="allergen.id" class="flex items-center">
                                        <input :id="'a_'+allergen.id" :value="allergen.id" v-model="form.allergen_ids" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <label :for="'a_'+allergen.id" class="ml-2 text-sm text-gray-700">{{ allergen.name }}</label>
                                    </div>
                                    <div v-if="allergens.length === 0" class="text-sm text-gray-500">Bahan Baku Tidak Ditemukan</div>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">Aktif (Terlihat oleh pelanggan)</label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-5 border-t border-gray-200">
                        <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">
                            Simpan Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
