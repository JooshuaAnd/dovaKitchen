<script setup>
import { ref } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { ChefHat, LayoutDashboard, Clock, User, LogOut, Menu as MenuIcon, X } from 'lucide-vue-next';

const isMobileMenuOpen = ref(false);
const page = usePage();

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post(route('logout'));
};

const navItems = [
    { name: 'Katalog Menu', routeName: 'catalog', icon: LayoutDashboard },
    { name: 'Riwayat Pesanan', routeName: 'history.index', icon: Clock },
    { name: 'Profil Saya', routeName: 'profile.edit', icon: User },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar for Desktop -->
        <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-100 fixed inset-y-0 z-40">
            <div class="flex items-center justify-center h-20 border-b border-gray-100 px-6">
                <Link :href="route('catalog')" class="flex items-center space-x-2 text-red-600">
                    <ChefHat class="w-8 h-8" />
                    <span class="text-2xl font-black tracking-tight">Dova Kitchen</span>
                </Link>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.routeName)"
                    :class="[
                        route().current === item.routeName
                            ? 'bg-red-50 text-red-600'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                        'group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-colors'
                    ]"
                >
                    <component 
                        :is="item.icon" 
                        :class="[
                            route().current === item.routeName ? 'text-red-600' : 'text-gray-400 group-hover:text-gray-600',
                            'flex-shrink-0 -ml-1 mr-3 h-5 w-5 transition-colors'
                        ]" 
                        aria-hidden="true" 
                    />
                    <span class="truncate">{{ item.name }}</span>
                </Link>
            </nav>

            <div class="p-4 border-t border-gray-100">
                <div class="flex items-center mb-4 px-2">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold uppercase text-sm">
                        {{ page.props.auth?.user?.name?.charAt(0) || 'U' }}
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-700 truncate w-32">{{ page.props.auth?.user?.name || 'User' }}</p>
                    </div>
                </div>
                <button 
                    @click="logout"
                    class="flex w-full items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-xl hover:bg-red-50 hover:text-red-600 transition-colors group"
                >
                    <LogOut class="flex-shrink-0 -ml-1 mr-3 h-5 w-5 text-gray-400 group-hover:text-red-600" />
                    Keluar
                </button>
            </div>
        </aside>

        <!-- Mobile Header -->
        <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-100 z-50 flex items-center justify-between px-4">
            <Link :href="route('catalog')" class="flex items-center space-x-2 text-red-600">
                <ChefHat class="w-6 h-6" />
                <span class="text-xl font-black tracking-tight">Dova</span>
            </Link>
            <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="text-gray-500 hover:text-gray-700 p-2">
                <X v-if="isMobileMenuOpen" class="w-6 h-6" />
                <MenuIcon v-else class="w-6 h-6" />
            </button>
        </div>

        <!-- Mobile Menu Overlay -->
        <div v-if="isMobileMenuOpen" class="md:hidden fixed inset-0 z-40 bg-gray-900/50" @click="isMobileMenuOpen = false"></div>
        <div 
            :class="[
                'md:hidden fixed inset-y-0 left-0 w-64 bg-white z-50 transform transition-transform duration-300 ease-in-out',
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <div class="h-16 flex items-center px-6 border-b border-gray-100">
                <span class="text-xl font-black text-red-600 tracking-tight">Menu</span>
            </div>
            <nav class="px-4 py-6 space-y-2">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.routeName)"
                    @click="isMobileMenuOpen = false"
                    :class="[
                        route().current === item.routeName
                            ? 'bg-red-50 text-red-600'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                        'group flex items-center px-3 py-3 text-base font-medium rounded-xl transition-colors'
                    ]"
                >
                    <component :is="item.icon" class="mr-4 h-6 w-6" />
                    {{ item.name }}
                </Link>
                <div class="border-t border-gray-100 pt-4 mt-4">
                    <button 
                        @click="logout"
                        class="flex w-full items-center px-3 py-3 text-base font-medium text-gray-600 rounded-xl hover:bg-red-50 hover:text-red-600"
                    >
                        <LogOut class="mr-4 h-6 w-6" />
                        Keluar
                    </button>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <main class="flex-1 md:ml-64 pt-16 md:pt-0 min-h-screen">
            <slot />
        </main>
    </div>
</template>
