<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MenuIngredientsSelector from '@/Components/Admin/MenuIngredientsSelector.vue';
import { ArrowLeft, Image as ImageIcon, CheckCircle } from 'lucide-vue-next';

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
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header Navigation -->
            <div class="mb-8 pb-4 border-b border-gray-200">
                <Link 
                    :href="route('admin.menus.index')" 
                    class="inline-flex items-center text-xs font-semibold text-gray-500 hover:text-gray-900 transition-colors mb-2.5"
                >
                    <ArrowLeft class="w-4 h-4 mr-1.5" /> Kembali ke Daftar Menu
                </Link>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                    Edit Menu: {{ menu.name }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Perbarui informasi hidangan dan bahan baku untuk menu katering Dova Kitchen.
                </p>
            </div>

            <!-- Frameless Form Flow -->
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Section 1: Informasi Utama Menu -->
                <div class="space-y-5">
                    <div class="pb-2 border-b border-gray-200">
                        <h2 class="text-base font-bold text-gray-900">Informasi Menu</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Nama menu, harga satuan, status ketersediaan, dan deskripsi sajian</p>
                    </div>

                    <!-- Nama Menu -->
                    <div>
                        <label for="name" class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                            Nama Menu <span class="text-red-500">*</span>
                        </label>
                        <input 
                            id="name"
                            v-model="form.name" 
                            type="text" 
                            required 
                            placeholder="Contoh: Salmon Teriyaki Bowl" 
                            class="block w-full border border-gray-300 bg-white text-gray-900 rounded-xl shadow-xs px-4 py-3 text-sm font-medium transition-colors focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                        >
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1.5 font-medium">{{ form.errors.name }}</div>
                    </div>

                    <!-- Grid 2 Kolom: Harga & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Harga -->
                        <div>
                            <label for="price" class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                                Harga Satuan (Rp) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-500 text-sm font-semibold">
                                    Rp
                                </span>
                                <input 
                                    id="price"
                                    v-model="form.price" 
                                    type="number" 
                                    required 
                                    placeholder="35000" 
                                    min="0"
                                    class="pl-10 block w-full border border-gray-300 bg-white text-gray-900 rounded-xl shadow-xs px-4 py-3 text-sm font-medium transition-colors focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                                >
                            </div>
                            <div v-if="form.errors.price" class="text-red-500 text-xs mt-1.5 font-medium">{{ form.errors.price }}</div>
                        </div>

                        <!-- Status Publikasi -->
                        <div>
                            <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                                Status Publikasi
                            </label>
                            <label class="flex items-center h-[46px] px-4 border border-gray-300 rounded-xl bg-white cursor-pointer hover:bg-gray-50 transition-colors shadow-xs">
                                <input 
                                    id="is_active" 
                                    v-model="form.is_active" 
                                    type="checkbox" 
                                    class="h-4 w-4 rounded border-gray-300 text-[#0D542B] focus:ring-[#0D542B]"
                                >
                                <span class="ml-3 text-sm font-semibold text-gray-800">Menu Aktif (Tampil di katalog)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Deskripsi Menu -->
                    <div>
                        <label for="description" class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                            Deskripsi Menu
                        </label>
                        <textarea 
                            id="description"
                            v-model="form.description" 
                            rows="3" 
                            placeholder="Deskripsi singkat rasa, porsi, atau penyajian menu..."
                            class="block w-full border border-gray-300 bg-white text-gray-900 rounded-xl shadow-xs px-4 py-3 text-sm font-medium transition-colors focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-red-500 text-xs mt-1.5 font-medium">{{ form.errors.description }}</div>
                    </div>
                </div>

                <!-- Section 2: Foto Menu -->
                <div class="space-y-4 pt-6 border-t border-gray-200">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Foto Menu</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Unggah gambar baru untuk mengganti foto hidangan saat ini (opsional)</p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-xs">
                        <div class="h-24 w-24 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50 shrink-0">
                            <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" alt="Preview Baru">
                            <img v-else-if="menu.photo_path" :src="menu.photo_path" class="h-full w-full object-cover" alt="Foto Saat Ini">
                            <ImageIcon v-else class="w-8 h-8 text-gray-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <input 
                                @change="handlePhotoChange" 
                                type="file" 
                                accept="image/*" 
                                class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border file:border-gray-300 file:text-xs file:font-bold file:bg-gray-100 file:text-gray-800 hover:file:bg-gray-200 cursor-pointer"
                            >
                            <p class="text-xs text-gray-400 mt-1.5">Pilih file baru jika ingin mengganti gambar lama (PNG, JPG, WEBP maks. 2MB).</p>
                        </div>
                    </div>
                    <div v-if="form.errors.photo" class="text-red-500 text-xs font-medium">{{ form.errors.photo }}</div>
                </div>

                <!-- Section 3: Bahan Baku & Alergen -->
                <div class="space-y-4 pt-6 border-t border-gray-200">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Bahan Baku & Pantangan Diet</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Tandai bahan baku yang terkandung dalam menu ini agar tersaring otomatis sesuai profil diet pelanggan</p>
                    </div>

                    <MenuIngredientsSelector 
                        v-model="form.allergen_ids" 
                        :allergens="allergens" 
                    />
                    <div v-if="form.errors.allergen_ids" class="text-red-500 text-xs font-medium">
                        {{ form.errors.allergen_ids }}
                    </div>
                </div>

                <!-- Section 4: Tombol Aksi di Bawah -->
                <div class="pt-8 border-t border-gray-200 flex items-center justify-between gap-4 pb-16">
                    <Link 
                        :href="route('admin.menus.index')" 
                        class="px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-sm font-bold text-gray-700 hover:bg-gray-100 shadow-xs transition-colors"
                    >
                        Batal
                    </Link>

                    <button 
                        type="submit" 
                        :disabled="form.processing" 
                        class="inline-flex items-center justify-center rounded-xl bg-[#0D542B] hover:bg-[#0a4222] px-6 py-2.5 text-sm font-bold text-white shadow-xs focus:outline-none focus:ring-2 focus:ring-[#0D542B] focus:ring-offset-2 disabled:opacity-50 transition-colors cursor-pointer"
                    >
                        <CheckCircle class="w-4 h-4 mr-2" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
