<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Shield, Utensils, ReceiptText, ChefHat, LogOut, Menu as MenuIcon, X, Calendar, Users } from 'lucide-vue-next';

const isMobileMenuOpen = ref(false);
const page = usePage();

const navItems = [
    { name: 'Manajemen Menu', routeName: 'admin.menus.index', icon: Utensils },
    { name: 'Manajemen Mingguan', routeName: 'admin.weekly-management.index', icon: Calendar },
        { name: 'Laporan Pemesanan', routeName: 'admin.kitchen-report.index', icon: ChefHat },
    { name: 'Verifikasi Pesanan', routeName: 'admin.orders.index', icon: ReceiptText },
    { name: 'Manajemen Pengguna', routeName: 'admin.users.index', icon: Users },

];
</script>

<template>
    <div class="min-h-screen bg-gray-900 flex text-white">
        <!-- Sidebar for Desktop -->
        <aside class="hidden md:flex flex-col w-64 bg-gray-900 border-r border-gray-800 fixed inset-y-0 z-40">
            <div class="flex items-center justify-center h-20 border-b border-gray-800 px-6">
                <Link :href="route('admin.menus.index')" class="flex items-center space-x-2 text-white">
                    <Shield class="w-8 h-8 text-blue-500" />
                    <span class="text-2xl font-black tracking-tight">Dova Admin</span>
                </Link>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.routeName)"
                    :class="[
                        $page.url.startsWith(route(item.routeName))
                            ? 'bg-blue-600/10 text-blue-400'
                            : 'text-gray-400 hover:bg-gray-800 hover:text-white',
                        'group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-colors'
                    ]"
                >
                    <component 
                        :is="item.icon" 
                        :class="[
                            $page.url.startsWith(route(item.routeName)) ? 'text-blue-500' : 'text-gray-500 group-hover:text-gray-300',
                            'flex-shrink-0 -ml-1 mr-3 h-5 w-5 transition-colors'
                        ]" 
                        aria-hidden="true" 
                    />
                    <span class="truncate">{{ item.name }}</span>
                </Link>
            </nav>
            
            <div class="p-4 border-t border-gray-800">
                <Link 
                    :href="route('/')"
                    class="flex w-full items-center justify-center px-3 py-2 text-sm font-medium text-gray-400 rounded-xl hover:bg-gray-800 hover:text-white transition-colors group border border-gray-700"
                >
                    <LogOut class="mr-2 h-4 w-4" />
                    Keluar ke Aplikasi
                </Link>
            </div>
        </aside>

        <!-- Mobile Header -->
        <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-gray-900 border-b border-gray-800 z-50 flex items-center justify-between px-4">
            <Link :href="route('admin.menus.index')" class="flex items-center space-x-2 text-white">
                <Shield class="w-6 h-6 text-blue-500" />
                <span class="text-xl font-black tracking-tight">Dova Admin</span>
            </Link>
            <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="text-gray-400 hover:text-white p-2">
                <X v-if="isMobileMenuOpen" class="w-6 h-6" />
                <MenuIcon v-else class="w-6 h-6" />
            </button>
        </div>

        <!-- Mobile Menu Overlay -->
        <div v-if="isMobileMenuOpen" class="md:hidden fixed inset-0 z-40 bg-black/60 backdrop-blur-sm" @click="isMobileMenuOpen = false"></div>
        <div 
            :class="[
                'md:hidden fixed inset-y-0 left-0 w-64 bg-gray-900 z-50 transform transition-transform duration-300 ease-in-out border-r border-gray-800',
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <div class="h-16 flex items-center px-6 border-b border-gray-800">
                <span class="text-xl font-black text-blue-500 tracking-tight">Admin Menu</span>
            </div>
            <nav class="px-4 py-6 space-y-2">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.routeName)"
                    @click="isMobileMenuOpen = false"
                    :class="[
                        $page.url.startsWith(route(item.routeName))
                            ? 'bg-blue-600/10 text-blue-400'
                            : 'text-gray-400 hover:bg-gray-800 hover:text-white',
                        'group flex items-center px-3 py-3 text-base font-medium rounded-xl transition-colors'
                    ]"
                >
                    <component :is="item.icon" class="mr-4 h-6 w-6" />
                    {{ item.name }}
                </Link>
                <div class="border-t border-gray-800 pt-4 mt-4">
                    <Link 
                        :href="route('/')"
                        class="flex w-full items-center justify-center px-3 py-3 text-base font-medium text-gray-400 rounded-xl hover:bg-gray-800 hover:text-white transition-colors border border-gray-700"
                    >
                        <LogOut class="mr-3 h-5 w-5" />
                        Keluar ke Aplikasi
                    </Link>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <main class="flex-1 md:ml-64 pt-16 md:pt-0 min-h-screen bg-gray-50 text-gray-900">
            <slot />
        </main>
    </div>
</template>
