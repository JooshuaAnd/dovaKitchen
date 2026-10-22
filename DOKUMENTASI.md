# DOKUMENTASI SISTEM DOVA KITCHEN: INTEGRASI WHATSAPP BOT & WEB ADMIN

Dokumen ini menjelaskan arsitektur, alur kerja (*flow*), integrasi AI, serta spesifikasi teknis untuk sistem pemesanan katering cerdas **Dova Kitchen**.

---

## 1. Arsitektur & Teknologi

Sistem Dova Kitchen menggabungkan dua lingkungan utama yang saling terhubung:

1. **Web Dashboard & Core Engine (Laravel 12 + Vue 3 / Inertia.js):**
   * Bertindak sebagai *Single Source of Truth* (Pusat Database).
   * Mengelola data master: Akun Pelanggan, Profil Alergi (*Allergens*), Katalog Menu, Jadwal Menu Mingguan (*Daily Menus* Senin - Jumat), Verifikasi Pembayaran, dan Laporan Dapur (*Kitchen Report*).
   * Menyediakan REST API internal untuk Bot WhatsApp.
2. **WhatsApp Assistant Bot (Node.js + whatsapp-web.js + Google Gemini AI):**
   * Berfungsi sebagai garda depan komunikasi pelanggan melalui chat WhatsApp.
   * **Google Gemini AI (1.5 Flash):** Menganalisis pesan teks bahasa manusia sehari-hari, mengidentifikasi niat pelanggan (*Intent Detection*), dan mengekstrak rincian pesanan multi-hari ke format JSON terstruktur.
   * **Express Webhook Server:** Menerima notifikasi otomatis dari Laravel saat admin melakukan verifikasi pembayaran di website.

```
┌─────────────────────────┐
│     Pelanggan (WA)      │
└────────────┬────────────┘
             │ (Chat / Foto Bukti Transfer)
             ▼
┌─────────────────────────┐         ┌────────────────────────┐
│   Node.js WhatsApp Bot  │◄───────►│    Google Gemini AI    │
│  (State & Session Mgmt) │         │ (NLP & JSON Extraction)│
└────────────┬────────────┘         └────────────────────────┘
             │ (REST API via Axios)
             ▼
┌─────────────────────────┐
│  Laravel 12 Backend API │
│  (/api/bot/...)         │
└────────────┬────────────┘
             │
     ┌───────┴────────┐
     ▼                ▼
┌──────────┐   ┌────────────────────────┐
│ Database │   │ Web Admin (Vue 3/Inertia)│
│ (SQLite/ │   │ - Jadwal Menu Mingguan │
│  MySQL)  │   │ - Verifikasi Bukti WA  │
└──────────┘   │ - Laporan Dapur        │
               └───────────┬────────────┘
                           │ (Saat Admin Klik "Verify/Approve")
                           ▼
               [ Webhook Callback ] ───► [ Bot Kirim WA ke Pelanggan ]
```

---

## 2. Alur Kerja Sistem (End-to-End Flow)

> **Catatan Skenario:** Seluruh pelanggan diasumsikan sudah terdaftar di sistem beserta profil alerginya (`user_allergens`) dan nomor WhatsApp yang valid.

### Langkah 1: Admin Mengatur Jadwal Menu Mingguan (Website)
1. Admin membuka dashboard `/admin/weekly-management` di website Dova Kitchen.
2. Admin memilih menu aktif untuk hari **Senin sampai Jumat**.
3. Data tersimpan di tabel `daily_menus`.

### Langkah 2: Pelanggan Bertanya Menu Mingguan (WhatsApp)
1. Pelanggan mengirim chat santai ke WhatsApp, misalnya:
   > *"Halo Dova Kitchen, menu minggu ini apa aja ya?"*
2. Bot mendeteksi nomor pengirim dan mencocokkannya ke database Laravel (`POST /api/bot/customer/check`).
3. Gemini AI mengenali bahwa intensi pelanggan adalah `ASK_MENU`.
4. Bot mengambil jadwal menu aktif via `GET /api/bot/menus/weekly`.
5. Bot membalas dengan daftar menu per hari (Senin s/d Jumat) lengkap dengan harga dan label alergen, serta mengirimkan foto menu.

### Langkah 3: Pelanggan Memesan Multi-Hari dengan Teks Santai
1. Pelanggan mengetik pesan pesanan, misalnya:
   > *"Aku mau hari Senin menu A 2 porsi, menu B 1 porsi; hari Rabu menu B dan menu C 1 porsi ya"*
2. Gemini AI membaca pesan tersebut dan memetakan nama menu dan hari ke dalam format JSON:
   ```json
   {
     "intent": "ORDER",
     "order_items": [
       { "day_name": "Senin", "date": "2026-09-07", "menu_id": 1, "menu_name": "Menu A", "portion_quantity": 2 },
       { "day_name": "Senin", "date": "2026-09-07", "menu_id": 2, "menu_name": "Menu B", "portion_quantity": 1 },
       { "day_name": "Rabu",  "date": "2026-09-09", "menu_id": 2, "menu_name": "Menu B", "portion_quantity": 1 },
       { "day_name": "Rabu",  "date": "2026-09-09", "menu_id": 3, "menu_name": "Menu C", "portion_quantity": 1 }
     ]
   }
   ```

### Langkah 4: Deteksi Alergi & Pantangan Otomatis (Smart Dietary Guard)
1. Bot mengirim data pesanan ke Laravel: `POST /api/bot/orders/validate-allergens`.
2. Laravel mencocokkan `menu_allergens` dengan riwayat alergi pelanggan (`user_allergens`).
3. **Jika ada bentrok (misal: Pelanggan alergi Udang, dan Menu C di hari Rabu mengandung Udang):**
   * Bot mengubah state sesi menjadi `WAITING_ALLERGEN_CONFIRM`.
   * Bot mengirimkan pesan peringatan ramah ke WhatsApp:
     > ⚠️ *PERINGATAN ALERGI / PANTANGAN*  
     > *Halo Kak Budi, sistem mendeteksi ada menu pilihanmu yang mengandung bahan alergen sesuai profilmu:*  
     > *• Menu C (Rabu, 2026-09-09) mengandung: Udang (Seafood)*  
     > *Apakah Kakak tetap ingin memesan menu ini?*  
     > *Ketik Ya untuk lanjut, atau Batal untuk mengganti.*
4. **Jika pelanggan konfirmasi "Ya" (atau jika sejak awal tidak ada alergen bentrok):**
   * Sistem melanjutkan ke pembuatan pesanan resmi.

### Langkah 5: Pembuatan Pesanan & Instruksi Pembayaran
1. Bot memanggil `POST /api/bot/orders`.
2. Laravel membuat data di tabel `orders` (`order_code`: misal `BOT-ABC12345`, status: `pending_payment`) dan mencatat masing-masing item di `order_items` beserta tanggal layanannya (`service_date`).
3. Bot menyimpan state pelanggan menjadi `WAITING_PAYMENT_PROOF` dan membalas dengan rincian total tagihan serta nomor rekening (Bank Jago / E-Wallet).

### Langkah 6: Pengiriman & Penyimpanan Bukti Pembayaran
1. Pelanggan mentransfer uang dan **mengirimkan foto bukti transfer** langsung di chat WhatsApp.
2. Bot mendeteksi pesan bertipe gambar/media pada state `WAITING_PAYMENT_PROOF`.
3. Bot mengunduh gambar tersebut dan mengirimkannya ke Laravel via `POST /api/bot/orders/{order}/payment-proof` (multipart/form-data).
4. Laravel menyimpan file ke folder `storage/app/public/payment_proofs/` dan mengubah status pesanan menjadi `awaiting_verification`.
5. Bot membalas ke pelanggan bahwa bukti sudah diterima dan sedang menunggu verifikasi admin.

### Langkah 7: Verifikasi Manual oleh Admin di Website
1. Admin membuka halaman `/admin/orders` di website Dova Kitchen.
2. Foto bukti pembayaran yang dikirim dari WhatsApp tadi langsung terlihat di dashboard admin.
3. Admin memverifikasi keabsahan dana di mutasi rekening.
4. Admin mengklik tombol **"Approve / Paid"**.

### Langkah 8: Webhook Otomatis & Notifikasi Sukses ke Dapur
1. Saat admin klik Approve, `OrderVerificationController` di Laravel memperbarui status pesanan menjadi `paid`.
2. Secara otomatis, Laravel menembak webhook ke server bot: `POST http://127.0.0.1:3000/webhook/order-status`.
3. Bot langsung mengirim chat ke WhatsApp pelanggan:
   > 🎉 *PEMBAYARAN DIVERIFIKASI!*  
   > *Halo Kak Budi, pembayaran untuk pesanan #BOT-ABC12345 sudah berhasil diverifikasi oleh admin Dova Kitchen.*  
   > 🍳 *Pesanan Anda sudah resmi masuk dalam daftar dapur (Kitchen List).*  
   > *Terima kasih banyak telah berlangganan di Dova Kitchen! Selamat menikmati! ❤️*
4. Pesanan otomatis masuk ke halaman **Kitchen Report** di website admin untuk persiapan bahan baku dan proses memasak.

---

## 3. Spesifikasi Endpoint API Laravel (Bot Routes)

Seluruh route ini didaftarkan di `routes/api.php`:

| Method | Endpoint | Deskripsi | Parameter Utama |
|---|---|---|---|
| `GET` | `/api/bot/menus/weekly` | Mengambil jadwal menu Senin - Jumat minggu ini, harga, foto, & alergen | `?date=YYYY-MM-DD` (opsional) |
| `POST` | `/api/bot/customer/check` | Memeriksa apakah nomor WhatsApp terdaftar dan mengambil daftar alergennya | `phone` (string) |
| `POST` | `/api/bot/orders/validate-allergens` | Mengecek apakah menu yang dipilih bertentangan dengan profil alergi user | `phone` / `user_id`, `items` (array) |
| `POST` | `/api/bot/orders` | Membuat transaksi order baru dan item-itemnya | `phone`, `items` (menu_id, service_date, qty) |
| `POST` | `/api/bot/orders/{order}/payment-proof` | Mengunggah foto bukti pembayaran dari WA ke penyimpanan storage | `payment_proof` (file image) |

---

## 4. Panduan Menjalankan Sistem

### A. Sisi Web Laravel:
1. Pastikan server Laravel berjalan:
   ```bash
   php artisan serve
   ```
2. Pastikan symlink storage aktif agar gambar menu & bukti transfer bisa diakses:
   ```bash
   php artisan storage:link
   ```

### B. Sisi WhatsApp Bot (Node.js):
1. Buka folder `whatsapp-bot`:
   ```bash
   cd "D:\Web Dova Kitchen\whatsapp-bot"
   ```
2. Salin file environment:
   ```bash
   cp .env.example .env
   ```
3. Masukkan **Gemini API Key** kamu di file `.env` pada variabel `GEMINI_API_KEY`. *(Bisa didapatkan gratis melalui Google AI Studio)*.
4. Install dependensi bot:
   ```bash
   npm install
   ```
5. Jalankan bot:
   ```bash
   npm start
   ```
6. Terminal akan menampilkan **QR Code**. Buka WhatsApp di ponsel, pilih **Perangkat Tertaut (Linked Devices)**, lalu scan QR Code tersebut.
7. Selesai! Bot sekarang aktif, cerdas, dan terintegrasi penuh dengan website Dova Kitchen.
