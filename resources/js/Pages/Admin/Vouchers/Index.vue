<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Ticket, Users, Trash2 } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    segments: { type: Array, default: () => [] },
    selectedSegment: { type: String, default: '' },
    templates: { type: Array, default: () => [] },
});

const form = useForm({
    title: '', rfm_segment: '', discount_type: 'percentage',
    discount_value: '', min_purchase: 0, valid_until: '', notes: '',
});
const submit = () => form.post(route('admin.vouchers.store'), {
    onSuccess: () => form.reset(),
});
const remove = (template) => {
    if (confirm(`Hapus template ${template.title}?`)) form.delete(route('admin.voucher-templates.destroy', template.id));
};
const formatPrice = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
const formatDate = (value) => new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value));
const rfmFilter = ref(props.selectedSegment);
const filterTemplates = () => router.get(
    route('admin.vouchers.index'),
    rfmFilter.value ? { rfm_segment: rfmFilter.value } : {},
    { preserveState: true, preserveScroll: true, replace: true }
);
const filteredTemplates = computed(() => rfmFilter.value
    ? props.templates.filter(template => template.rfm_segment === rfmFilter.value)
    : props.templates
);
</script>

<template>
    <Head title="Manajemen Voucher" />
    <AdminLayout>
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-900 text-white"><Ticket class="h-5 w-5" /></div>
                <div><h1 class="text-2xl font-black text-gray-900 sm:text-3xl">Manajemen Voucher</h1><p class="mt-1 text-sm text-gray-500">Buat template voucher berdasarkan kategori RFM, lalu terbitkan dari detail pelanggan yang sesuai.</p></div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)]">
                <form @submit.prevent="submit" class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <h2 class="font-bold text-gray-900">Buat Template Voucher</h2>
                    <label class="block text-sm font-medium text-gray-700">Nama voucher<input v-model="form.title" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Promo Pelanggan Loyal" required /></label>
                    <label class="block text-sm font-medium text-gray-700">Kategori RFM<select v-model="form.rfm_segment" class="mt-1 w-full rounded-lg border-gray-300" required><option value="">Pilih kategori</option><option v-for="segment in segments" :key="segment" :value="segment">{{ segment }}</option></select></label>
                    <div class="grid grid-cols-2 gap-3"><label class="text-sm font-medium text-gray-700">Jenis potongan<select v-model="form.discount_type" class="mt-1 w-full rounded-lg border-gray-300"><option value="percentage">Persentase</option><option value="fixed">Nominal</option></select></label><label class="text-sm font-medium text-gray-700">Potongan<input v-model="form.discount_value" type="number" min="1" class="mt-1 w-full rounded-lg border-gray-300" required /></label></div>
                    <div class="grid grid-cols-2 gap-3"><label class="text-sm font-medium text-gray-700">Min. belanja<input v-model="form.min_purchase" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300" /></label><label class="text-sm font-medium text-gray-700">Berlaku sampai<input v-model="form.valid_until" type="date" class="mt-1 w-full rounded-lg border-gray-300" required /></label></div>
                    <label class="block text-sm font-medium text-gray-700">Catatan<textarea v-model="form.notes" rows="2" class="mt-1 w-full rounded-lg border-gray-300" /></label>
                    <p v-if="form.errors.rfm_segment" class="text-sm text-red-600">{{ form.errors.rfm_segment }}</p>
                    <button :disabled="form.processing" class="w-full rounded-xl bg-gray-900 px-4 py-3 font-bold text-white disabled:opacity-50">{{ form.processing ? 'Menyimpan...' : 'Simpan Template' }}</button>
                </form>

                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div><h2 class="font-bold text-gray-900">Template Voucher</h2><p class="text-sm text-gray-500">{{ filteredTemplates.length }} template ditampilkan</p></div>
                        <div class="flex items-center gap-2"><label for="rfm-filter" class="text-sm font-medium text-gray-700">Kategori</label><select id="rfm-filter" v-model="rfmFilter" @change="filterTemplates" class="rounded-lg border-gray-300 text-sm"><option value="">Semua kategori</option><option v-for="segment in segments" :key="segment" :value="segment">{{ segment }}</option></select><Users class="h-5 w-5 text-gray-400" /></div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 font-bold">Voucher</th>
                                    <th class="px-4 py-3 font-bold">Kategori RFM</th>
                                    <th class="px-4 py-3 font-bold">Potongan</th>
                                    <th class="px-4 py-3 font-bold">Min. belanja</th>
                                    <th class="px-4 py-3 font-bold">Berlaku sampai</th>
                                    <th class="px-4 py-3 text-right font-bold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="template in filteredTemplates" :key="template.id" class="align-top hover:bg-gray-50">
                                    <td class="px-4 py-4"><p class="font-bold text-gray-900">{{ template.title }}</p><p class="mt-1 font-mono text-xs text-gray-600">{{ template.code_prefix }}-ID-XXXXXX</p></td>
                                    <td class="px-4 py-4 text-gray-700">{{ template.rfm_segment }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-gray-700">{{ template.discount_type === 'percentage' ? Math.round(template.discount_value) + '%' : formatPrice(template.discount_value) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-gray-700">{{ formatPrice(template.min_purchase) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-gray-700">{{ formatDate(template.valid_until) }}</td>
                                    <td class="px-4 py-4 text-right"><button @click="remove(template)" class="rounded-lg p-2 text-red-600 hover:bg-red-50" title="Hapus template"><Trash2 class="h-4 w-4" /></button></td>
                                </tr>
                                <tr v-if="filteredTemplates.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">{{ rfmFilter ? 'Belum ada template untuk kategori ini.' : 'Belum ada template voucher.' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
