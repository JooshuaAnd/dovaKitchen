<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    }
});

const emit = defineEmits(['close']);
const waMessage = ref('');

watch(() => props.user, (newUser) => {
    if (newUser) {
        waMessage.value = `Halo ${newUser.name} sudah lama tidak jumpa, yuk kunjungi website Dova Kitchen dan dapatkan voucher untuk makananmu.`;
    }
}, { immediate: true });

const close = () => {
    emit('close');
};

const sendWhatsApp = () => {
    if (props.user && props.user.phone) {
        let phone = String(props.user.phone).replace(/[^0-9]/g, '');
        if (phone.startsWith('0')) {
            phone = '62' + phone.substring(1);
        }
        const text = encodeURIComponent(waMessage.value);
        window.open(`https://wa.me/${phone}?text=${text}`, '_blank');
        close();
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="wa-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/20 transition-opacity backdrop-blur-md" @click="close" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-5">
                        <h3 class="text-xl font-black text-gray-900 flex items-center" id="wa-modal-title">
                            <svg class="w-6 h-6 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                            </svg>
                            Kirim Pesan WhatsApp
                        </h3>
                        <button @click="close" class="text-gray-400 hover:text-gray-500 p-1 bg-gray-50 rounded-full hover:bg-gray-100 transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    
                    <div v-if="user" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Kustomer</label>
                                <input type="text" disabled :value="user.name" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nomor Telepon</label>
                                <input type="text" disabled :value="user.phone" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 cursor-not-allowed">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Pesan (Teks)</label>
                            <textarea v-model="waMessage" rows="4" class="w-full border border-gray-300 rounded-xl shadow-sm px-4 py-3 text-sm focus:border-green-500 focus:ring-green-500"></textarea>
                            <p class="mt-1.5 text-xs text-gray-500">Anda dapat mengubah teks pesan ini sebelum mengirimnya.</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-4 sm:px-6 flex justify-end space-x-3 border-t border-gray-100">
                    <button type="button" class="px-5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm" @click="close">
                        Batal
                    </button>
                    <button type="button" class="px-5 py-2.5 bg-green-600 text-white rounded-xl text-sm font-bold hover:bg-green-700 transition-colors shadow-sm shadow-green-200 flex items-center" @click="sendWhatsApp">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
