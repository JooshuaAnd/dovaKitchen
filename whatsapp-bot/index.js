require('dotenv').config();
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const { GoogleGenerativeAI } = require('@google/generative-ai');
const axios = require('axios');
const express = require('express');

const LARAVEL_API = process.env.LARAVEL_API_URL || 'http://localhost:8000/api';
const GEMINI_KEY = process.env.GEMINI_API_KEY || '';
const PORT = process.env.PORT || 3000;

// Inisialisasi Gemini AI
const genAI = new GoogleGenerativeAI(GEMINI_KEY);
const model = genAI.getGenerativeModel({ model: 'gemini-1.5-flash' });

// Express Server untuk menerima webhook/notifikasi dari Laravel
const app = express();
app.use(express.json());

// Inisialisasi WhatsApp Client
const client = new Client({
    authStrategy: new LocalAuth({ dataPath: './.wwebjs_auth' }),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    }
});

// Memory State Tracking per Customer Phone Number
// State: 'IDLE' | 'WAITING_ALLERGEN_CONFIRM' | 'WAITING_PAYMENT_PROOF'
const userSessions = new Map();

function getSession(phone) {
    if (!userSessions.has(phone)) {
        userSessions.set(phone, { state: 'IDLE', draftOrder: null, activeOrder: null });
    }
    return userSessions.get(phone);
}

function resetSession(phone) {
    userSessions.set(phone, { state: 'IDLE', draftOrder: null, activeOrder: null });
}

// Helper jeda waktu (delay)
const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// -------------------------------------------------------------
// EVENT WHATSAPP WEB
// -------------------------------------------------------------
client.on('qr', (qr) => {
    console.log('>>> Scan QR Code berikut untuk login WhatsApp Bot Dova Kitchen:');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    console.log('✅ WhatsApp Bot Dova Kitchen siap & terhubung!');
});

// -------------------------------------------------------------
// LOGIKA PEMROSESAN PESAN
// -------------------------------------------------------------
client.on('message', async (msg) => {
    try {
        const sender = msg.from; // misal: 628123456789@c.us
        if (sender.includes('@g.us') || msg.isStatus) return; // Abaikan pesan grup atau status

        // Anti-spam & Human-like typing:
        // Memunculkan status "Sedang mengetik..." dengan jeda acak 5 - 10 detik sebelum bot membalas
        const originalReply = msg.reply.bind(msg);
        msg.reply = async (...args) => {
            try {
                const chat = await msg.getChat();
                await chat.sendStateTyping();
                const randomDelay = Math.floor(Math.random() * 5001) + 5000; // 5000 - 10000 ms (5 - 10 detik)
                console.log(`[Human Delay] Mengetik selama ${(randomDelay / 1000).toFixed(1)} detik sebelum membalas...`);
                await delay(randomDelay);
                await chat.clearState();
            } catch (e) {
                // Abaikan jika status typing terkendala
            }
            return originalReply(...args);
        };

        const rawPhone = sender.replace('@c.us', '');
        const text = msg.body ? msg.body.trim() : '';
        const session = getSession(rawPhone);

        console.log(`[Pesan Masuk] Dari: ${rawPhone} | State: ${session.state} | Pesan: ${text || '[Media]'}`);

        // 1. Cek apakah customer terdaftar di Laravel
        let customer = null;
        try {
            const checkRes = await axios.post(`${LARAVEL_API}/bot/customer/check`, { phone: rawPhone });
            customer = checkRes.data.user;
        } catch (err) {
            console.log(`Customer ${rawPhone} belum terdaftar di database Laravel.`);
            // Jika belum terdaftar, bisa beri tahu untuk daftar via website
            return msg.reply('Halo! Nomor WhatsApp kamu belum terdaftar di sistem Dova Kitchen. Silakan daftar dan isi profil alergen terlebih dahulu di website ya!');
        }

        // 2. JIKA CUSTOMER SEDANG DI TAHAP UPLOAD BUKTI PEMBAYARAN
        if (session.state === 'WAITING_PAYMENT_PROOF') {
            if (msg.hasMedia) {
                const media = await msg.downloadMedia();
                if (media && media.mimetype.startsWith('image/')) {
                    // Kirim gambar ke Laravel
                    const FormData = require('form-data');
                    const form = new FormData();
                    const buffer = Buffer.from(media.data, 'base64');
                    form.append('payment_proof', buffer, { filename: 'payment_proof.jpg', contentType: media.mimetype });

                    await axios.post(
                        `${LARAVEL_API}/bot/orders/${session.activeOrder.id}/payment-proof`,
                        form,
                        { headers: form.getHeaders() }
                    );

                    resetSession(rawPhone);
                    return msg.reply(
                        `Terima kasih Kak ${customer.name}! 📸 Bukti pembayaran sudah kami terima dan tersimpan di sistem.\n\n` +
                        `Admin Dova Kitchen akan segera melakukan verifikasi pembayaran melalui dashboard. Nanti kami kabari lagi ya! 🙏`
                    );
                } else {
                    return msg.reply('Mohon kirimkan bukti pembayaran dalam format foto/gambar ya Kak.');
                }
            } else if (text.toLowerCase().includes('batal')) {
                resetSession(rawPhone);
                return msg.reply('Pesanan telah dibatalkan.');
            } else {
                return msg.reply(
                    `Kami menunggu foto bukti transfer Kak.\n` +
                    `Total: ${session.activeOrder.formatted_total}\n\n` +
                    (process.env.PAYMENT_INSTRUCTIONS || 'Silakan transfer ke Bank Jago: 1234-5678-9012 a.n Dova Kitchen.') +
                    `\n\n(Ketik *Batal* jika ingin membatalkan)`
                );
            }
        }

        // 3. JIKA CUSTOMER DALAM TAHAP KONFIRMASI ALERGI
        if (session.state === 'WAITING_ALLERGEN_CONFIRM') {
            const lower = text.toLowerCase();
            const isConfirm = ['ya', 'lanjut', 'tetap pesan', 'ok', 'oke', 'iya', 'siap'].some(w => lower.includes(w));
            const isCancel = ['batal', 'ganti', 'tidak', 'gak', 'batalin'].some(w => lower.includes(w));

            if (isConfirm) {
                // Buat order resmi
                const orderRes = await axios.post(`${LARAVEL_API}/bot/orders`, {
                    phone: rawPhone,
                    items: session.draftOrder.items
                });

                const order = orderRes.data.order;
                session.state = 'WAITING_PAYMENT_PROOF';
                session.activeOrder = order;

                let replyMsg = `Baik Kak, pesanan dicatat dengan catatan persetujuan alergi.\n\n`;
                replyMsg += `🧾 *RINCIAN PESANAN (#${order.order_code})*\n`;
                order.items.forEach(it => {
                    replyMsg += `- ${it.service_date} : ${it.menu_name} (${it.portion_quantity} porsi) - Rp ${Number(it.subtotal).toLocaleString('id-ID')}\n`;
                });
                replyMsg += `\n*Total Pembayaran: ${order.formatted_total}*\n\n`;
                replyMsg += (process.env.PAYMENT_INSTRUCTIONS || 'Silakan transfer ke Bank Jago: 1234-5678-9012 (a.n Dova Kitchen)') + '\n';
                replyMsg += `\nSilakan kirimkan *FOTO BUKTI TRANSFER* ke chat ini ya Kak!`;

                return msg.reply(replyMsg);
            } else if (isCancel) {
                resetSession(rawPhone);
                return msg.reply('Baik Kak, pesanan dibatalkan demi keamanan alergi kamu. Silakan lihat menu lain yang aman ya! 🙏');
            } else {
                return msg.reply('Ketik *Ya* jika tetap ingin memesan menu tersebut, atau ketik *Batal* untuk memilih menu lain.');
            }
        }

        // 4. ANALISIS PESAN MENGGUNAKAN GEMINI AI UNTUK INTENT DETECTION & EXTRACTION
        // Ambil data menu mingguan terlebih dahulu sebagai konteks
        const weeklyMenuRes = await axios.get(`${LARAVEL_API}/bot/menus/weekly`);
        const weeklySchedule = weeklyMenuRes.data.schedule;

        const prompt = `
Kamu adalah asisten cerdas pemesanan catering bernama "Dova Kitchen Bot".
Berikut adalah jadwal menu Senin sampai Jumat minggu ini:
${JSON.stringify(weeklySchedule, null, 2)}

User Profile:
- Nama: ${customer.name}
- Alergen / Pantangan: ${JSON.stringify(customer.allergens.map(a => a.name))}

Pesan dari customer: "${text}"

Tugasmu adalah menganalisis pesan customer dan menghasilkan output HANYA dalam bentuk format JSON murni tanpa markdown, tanpa backtick:
{
  "intent": "ASK_MENU" | "ORDER" | "OTHER",
  "reply_text": "balasan singkat jika intent OTHER atau sapaan",
  "order_items": [
    {
      "day_name": "Senin|Selasa|Rabu|Kamis|Jumat",
      "date": "YYYY-MM-DD",
      "menu_id": number,
      "menu_name": "string",
      "portion_quantity": number
    }
  ]
}

Aturan Pencocokan:
- Jika customer bertanya tentang menu (misal: "menunya apa aja", "menu minggu ini apa", "ada menu apa aja"): intent = "ASK_MENU".
- Jika customer berniat memesan menu tertentu untuk hari tertentu: cocokkan nama menu dan hari dengan jadwal menu di atas, lalu isi "order_items", intent = "ORDER". Jika quantity tidak disebut, anggap 1.
- Jika hanya menyapa atau hal lain: intent = "OTHER".
        `;

        const aiResponse = await model.generateContent(prompt);
        let parsed = null;
        try {
            const cleanText = aiResponse.response.text().replace(/```json/g, '').replace(/```/g, '').trim();
            parsed = JSON.parse(cleanText);
        } catch (e) {
            console.error('Gagal mem-parsing response AI:', aiResponse.response.text());
        }

        // 4A. INTENT: MENANYAKAN MENU MINGGU INI
        if (parsed && parsed.intent === 'ASK_MENU') {
            let menuText = `Halo Kak ${customer.name}! 🥗 Berikut menu Dova Kitchen minggu ini (Senin - Jumat):\n\n`;
            
            weeklySchedule.forEach(day => {
                menuText += `📅 *${day.day_name} (${day.date})*:\n`;
                if (day.menus.length === 0) {
                    menuText += `  _(Belum ada menu yang dijadwalkan)_\n`;
                } else {
                    day.menus.forEach(m => {
                        const allergenInfo = m.allergens.length ? ` [Alergen: ${m.allergens.join(', ')}]` : ' [Bebas Alergen]';
                        menuText += `  • *${m.name}* - ${m.formatted_price}${allergenInfo}\n`;
                    });
                }
                menuText += `\n`;
            });

            menuText += `Untuk memesan, Kakak bisa ketik pesan santai seperti:\n` +
                        `_"Aku mau hari Senin menu A 2 porsi, hari Rabu menu B 1 porsi"_\n` +
                        `Sistem kami akan langsung mendeteksi dan mengecek alergi Kakak! 😉`;

            await msg.reply(menuText);

            // Opsional: Kirim gambar menu jika ada foto menu aktif
            for (const day of weeklySchedule) {
                for (const m of day.menus) {
                    if (m.photo_url) {
                        try {
                            await delay(Math.floor(Math.random() * 2001) + 2000); // jeda 2 - 4 detik antar kirim foto
                            const media = await MessageMedia.fromUrl(m.photo_url);
                            await client.sendMessage(sender, media, { caption: `${day.day_name}: ${m.name}` });
                        } catch (imgErr) {
                            // Abaikan jika gambar gagal diunduh
                        }
                    }
                }
            }
            return;
        }

        // 4B. INTENT: MEMESAN MENU (ORDER)
        if (parsed && parsed.intent === 'ORDER' && parsed.order_items && parsed.order_items.length > 0) {
            // Validasi alergi ke Laravel
            const validateRes = await axios.post(`${LARAVEL_API}/bot/orders/validate-allergens`, {
                phone: rawPhone,
                items: parsed.order_items.map(it => ({
                    menu_id: it.menu_id,
                    service_date: it.date
                }))
            });

            const validation = validateRes.data;

            // JIKA TERDAPAT ALERGI / PANTANGAN YANG COCOK
            if (validation.has_conflict) {
                session.state = 'WAITING_ALLERGEN_CONFIRM';
                session.draftOrder = { items: parsed.order_items };

                let warningMsg = `⚠️ *PERINGATAN ALERGI / PANTANGAN*\n\n`;
                warningMsg += `Halo Kak ${customer.name}, sistem mendeteksi ada menu pilihanmu yang mengandung bahan alergen sesuai profilmu:\n\n`;
                validation.conflicts.forEach(c => {
                    warningMsg += `• *${c.menu_name}* (${c.service_date})\n  Mengandung: ${c.conflicting_allergens.join(', ')}\n`;
                });
                warningMsg += `\nApakah Kakak tetap ingin memesan menu ini?\n` +
                              `Ketik *Ya* untuk lanjut, atau *Batal* untuk memilih menu lain.`;

                return msg.reply(warningMsg);
            }

            // JIKA AMAN (TIDAK ADA ALERGI) -> LANGSUNG BUAT ORDER
            const orderRes = await axios.post(`${LARAVEL_API}/bot/orders`, {
                phone: rawPhone,
                items: parsed.order_items
            });

            const order = orderRes.data.order;
            session.state = 'WAITING_PAYMENT_PROOF';
            session.activeOrder = order;

            let replyMsg = `✅ Pesanan berhasil dicatat!\n\n`;
            replyMsg += `🧾 *RINCIAN PESANAN (#${order.order_code})*\n`;
            order.items.forEach(it => {
                replyMsg += `- ${it.service_date} : ${it.menu_name} (${it.portion_quantity} porsi) - Rp ${Number(it.subtotal).toLocaleString('id-ID')}\n`;
            });
            replyMsg += `\n*Total Pembayaran: ${order.formatted_total}*\n\n`;
            replyMsg += (process.env.PAYMENT_INSTRUCTIONS || 'Silakan transfer ke Bank Jago: 1234-5678-9012 (a.n Dova Kitchen)') + '\n';
            replyMsg += `\nSilakan kirimkan *FOTO BUKTI TRANSFER* ke chat ini ya Kak!`;

            return msg.reply(replyMsg);
        }

        // 4C. RESPON DEFAULT
        if (parsed && parsed.reply_text) {
            return msg.reply(parsed.reply_text);
        } else {
            return msg.reply(
                `Halo Kak ${customer.name}! Ada yang bisa dibantu?\n\n` +
                `Ketik *Menu minggu ini* untuk melihat daftar catering kami, atau langsung ketik pesanan Kakak ya! 😊`
            );
        }

    } catch (error) {
        console.error('Error saat memproses pesan:', error.response?.data || error.message);
        return msg.reply('Maaf, terjadi kendala pada sistem. Silakan coba beberapa saat lagi ya!');
    }
});

// -------------------------------------------------------------
// WEBHOOK DARI LARAVEL (Saat Admin Klik Approval di Website)
// -------------------------------------------------------------
app.post('/webhook/order-status', async (req, res) => {
    try {
        const { order_code, status, phone, customer_name } = req.body;
        console.log(`[Webhook Diterima] Order: ${order_code} | Status: ${status} | Phone: ${phone}`);

        if (!phone) {
            return res.status(400).json({ success: false, message: 'Nomor telepon tidak ditemukan' });
        }

        let formattedPhone = phone.replace(/[^0-9]/g, '');
        if (formattedPhone.startsWith('0')) formattedPhone = '62' + formattedPhone.slice(1);
        const chatId = `${formattedPhone}@c.us`;

        if (status === 'paid') {
            const approvalMessage = 
                `🎉 *PEMBAYARAN DIVERIFIKASI!*\n\n` +
                `Halo Kak ${customer_name},\n` +
                `Pembayaran untuk pesanan *#${order_code}* sudah berhasil diverifikasi oleh admin Dova Kitchen.\n\n` +
                `🍳 Pesanan Anda sudah resmi masuk dalam daftar dapur (*Kitchen List*).\n\n` +
                `Terima kasih banyak telah berlangganan di Dova Kitchen! Selamat menikmati! ❤️`;

            await client.sendMessage(chatId, approvalMessage);
        } else if (status === 'rejected') {
            const rejectedMessage = 
                `⚠️ *PEMBERITAHUAN PEMBAYARAN*\n\n` +
                `Halo Kak ${customer_name},\n` +
                `Mohon maaf, bukti pembayaran untuk pesanan *#${order_code}* belum dapat kami verifikasi oleh admin.\n` +
                `Silakan hubungi customer service kami atau kirim ulang bukti transfer yang valid. Terima kasih!`;

            await client.sendMessage(chatId, rejectedMessage);
        }

        res.json({ success: true, message: 'Notifikasi berhasil dikirim ke WhatsApp' });
    } catch (err) {
        console.error('Gagal mengirim pesan webhook:', err.message);
        res.status(500).json({ success: false, error: err.message });
    }
});

// Jalankan Webhook Server & WhatsApp Client
app.listen(PORT, () => {
    console.log(`📡 Bot Webhook Server berjalan di http://localhost:${PORT}`);
    client.initialize();
});
