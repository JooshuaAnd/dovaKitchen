<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Allergen;

class MenuController extends Controller
{
    // 1. MENAMPILKAN SEMUA DATA MENU
    public function index()
    {
        // Mengambil semua menu beserta data relasi 'allergens' (alergi)
        // Diurutkan berdasarkan tanggal pembuatan dari yang terbaru ke terlama
        $menus = Menu::with('allergens')->orderBy('created_at', 'desc')->get();
        
        // Mengirimkan data menu tersebut ke halaman frontend (Vue.js) menggunakan Inertia
        return Inertia::render('Admin/Menus/Index', [
            'menus' => $menus
        ]);
    }

    // 2. MENAMPILKAN HALAMAN FORM TAMBAH MENU BARU
    public function create()
    {
        // Mengirimkan seluruh data 'Allergen' agar bisa dipilih di form (checkbox/select)
        return Inertia::render('Admin/Menus/Create', [
            'allergens' => Allergen::all()
        ]);
    }

    // 3. MENYIMPAN DATA MENU BARU KE DATABASE
    public function store(Request $request)
    {
        // Melakukan validasi input dari user
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'photo' => 'nullable|image|max:2048', // Foto harus berupa gambar maksimal 2MB
            'is_active' => 'boolean',
            'allergen_ids' => 'nullable|array', // Harus berupa array (kumpulan ID alergi)
            'allergen_ids.*' => 'exists:allergens,id', // Setiap ID harus ada di tabel allergens
        ]);

        // Mengecek apakah ada file foto yang diunggah
        if ($request->hasFile('photo')) {
            // Menyimpan file foto ke dalam folder 'storage/app/public/menus'
            $path = $request->file('photo')->store('menus', 'public');
            // Menambahkan path foto hasil unggahan ke dalam array data yang tervalidasi
            $validated['photo_path'] = '/storage/' . $path;
        }
        // Menghapus key 'photo' dari array karena kolom di tabelnya adalah 'photo_path'
        unset($validated['photo']);

        // Membuat record menu baru di database
        $menu = Menu::create($validated);
        
        // Mengecek jika ada alergi yang dipilih pada form
        if (isset($validated['allergen_ids'])) {
            // Menyimpan relasi many-to-many (menu_id dan allergen_id) di tabel pivot
            $menu->allergens()->sync($validated['allergen_ids']);
        }

        // Mengembalikan user ke halaman daftar menu dengan pesan sukses
        return redirect()->route('admin.menus.index')->with('success', 'Menu created successfully.');
    }

    // 4. MENAMPILKAN HALAMAN FORM EDIT MENU
    public function edit(Menu $menu)
    {
        // Memuat ulang data relasi 'allergens' milik menu ini agar ter-centang di form edit
        $menu->load('allergens');
        
        // Merender halaman edit beserta data menu terkait dan daftar seluruh alergen
        return Inertia::render('Admin/Menus/Edit', [
            'menu' => $menu,
            'allergens' => Allergen::all()
        ]);
    }

    // 5. MEMPERBARUI DATA MENU YANG SUDAH ADA DI DATABASE
    public function update(Request $request, Menu $menu)
    {
        // Validasi aturan input sama dengan saat membuat baru
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'allergen_ids' => 'nullable|array',
            'allergen_ids.*' => 'exists:allergens,id',
        ]);

        // Mengecek jika pengguna mengunggah foto baru saat mengedit
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('menus', 'public');
            $validated['photo_path'] = '/storage/' . $path;
        }
        unset($validated['photo']);

        // Memperbarui record menu di database dengan data yang lolos validasi
        $menu->update($validated);
        
        // Mensinkronkan data alergi yang baru. 
        // Jika 'allergen_ids' kosong, maka menggunakan array kosong [] (akan menghapus semua relasi lama)
        $menu->allergens()->sync($validated['allergen_ids'] ?? []);

        // Redirect kembali dengan pesan sukses
        return redirect()->route('admin.menus.index')->with('success', 'Menu updated successfully.');
    }

    // 6. MENGHAPUS DATA MENU DARI DATABASE
    public function destroy(Menu $menu)
    {
        // Mengeksekusi perintah hapus record dari database
        $menu->delete();
        
        // Redirect kembali ke halaman index dengan pesan bahwa menu berhasil dihapus
        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted successfully.');
    }
}
