<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MenuIngredientsSelector from '@/Components/Admin/MenuIngredientsSelector.vue';
import { ArrowLeft, Utensils, Image as ImageIcon, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    menu: Object,
    allergens: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    name: props.menu.name,
    description: props.menu.description || '',
    price: props.menu.price,
    photo: null,
    _method: 'put',
    is_active: Boolean(props.menu.is_active),
    allergen_ids: props.menu.allergens ? props.menu.allergens.map(a => a.id) : []
});

const photoPreview = ref(null);

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    form.photo = file;
    if (file) {
        photoPreview.value = URL.createObjectURL(file);
    } else {
        photoPreview.value = null;
    }
};

const submit = () => {
    form.post(route('admin.menus.update', props.menu.id));
};
</script>

<template>
    <Head :title="'Edit Menu - ' + menu.name" />

    <AdminLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header Navigation -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link 
                        :href="route('admin.menus.index')" 
                        class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-gray-900 transition-colors mb-2"
                    >
                        <ArrowLeft class="w-4 h-4 mr-1.5" /> Kembali ke Daftar Menu
                    </Link>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                        Edit Menu: {{ menu.name }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Perbarui detail menu dan bahan baku spesifik untuk pencocokan akurat dengan profil diet kustomer.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Section 1: Informasi Dasar Menu -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center space-x-3 pb-4 mb-6 border-b border-gray-100">
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                            <Utensils class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Informasi Utama Menu</h2>
                            <p class="text-xs text-gray-500">Nama, harga, deskripsi porsi, dan foto tampilan menu</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Sub-column -->
                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Nama Menu <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    v-model="form.name" 
                                    type="text" 
                                    required 
                                    placeholder="Contoh: Salmon Teriyaki Bowl" 
                                    class="w-full text-sm rounded-xl border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500"
                                >
                                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Harga (Rp) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative rounded-xl shadow-xs">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-500 text-sm font-semibold">
                                        Rp
                                    </div>
                                    <input 
                                        v-model="form.price" 
                                        type="number" 
                                        required 
                                        placeholder="35000" 
                                        min="0"
                                        class="w-full pl-11 text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    >
                                </div>
                                <div v-if="form.errors.price" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.price }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Status Publikasi
                                </label>
                                <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input 
                                        id="is_active" 
                                        v-model="form.is_active" 
                                        type="checkbox" 
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    <div class="ml-3">
                                        <span class="text-sm font-bold text-gray-800">Aktif</span>
                                        <p class="text-xs text-gray-500">Tampilkan menu ini di katalog pre-order kustomer</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Right Sub-column -->
                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Foto Menu (Opsional)
                                </label>
                                <div class="flex items-center space-x-4">
                                    <div class="h-24 w-24 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50 shrink-0">
                                        <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" alt="Preview Baru">
                                        <img v-else-if="menu.photo_path" :src="menu.photo_path" class="h-full w-full object-cover" alt="Foto Saat Ini">
                                        <ImageIcon v-else class="w-8 h-8 text-gray-400" />
                                    </div>
                                    <div class="flex-1">
                                        <input 
                                            @change="handlePhotoChange" 
                                            type="file" 
                                            accept="image/*" 
                                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                                        >
                                        <p class="text-xs text-gray-400 mt-1">Pilih gambar baru untuk mengganti foto lama.</p>
                                    </div>
                                </div>
                                <div v-if="form.errors.photo" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.photo }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Deskripsi Menu
                                </label>
                                <textarea 
                                    v-model="form.description" 
                                    rows="3" 
                                    placeholder="Deskripsi singkat rasa, kelezatan, dan penyajian menu..."
                                    class="w-full text-sm rounded-xl border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.description }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Bahan Baku & Smart Dietary Selector -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-6 sm:p-8">
                    <MenuIngredientsSelector 
                        v-model="form.allergen_ids" 
                        :allergens="allergens" 
                    />
                    <div v-if="form.errors.allergen_ids" class="text-red-500 text-xs mt-2 font-medium">
                        {{ form.errors.allergen_ids }}
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-4 pb-12">
                    <Link 
                        :href="route('admin.menus.index')" 
                        class="px-5 py-2.5 rounded-xl border border-gray-300 text-sm font-bold text-gray-700 hover:bg-gray-100 transition-colors"
                    >
                        Batal
                    </Link>

                    <button 
                        type="submit" 
                        :disabled="form.processing" 
                        class="inline-flex items-center justify-center rounded-xl border border-transparent bg-blue-600 py-3 px-8 text-sm font-bold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                    >
                        <CheckCircle class="w-4 h-4 mr-2" />
                        {{ form.processing ? 'Menyimpan Perubahan...' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
