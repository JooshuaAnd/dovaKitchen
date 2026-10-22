<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Menu;
use App\Models\Allergen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Menu::truncate();
        Allergen::truncate();
        DB::table('user_allergens')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrator',
                'password' => \Illuminate\Support\Facades\Hash::make('12345678'),
                'is_admin' => true,
            ]
        );

        // Allergens
        $allergens = [
            // Seafood
            ['name' => 'Ikan', 'category' => 'Seafood'],
            ['name' => 'Ikan Laut (Tuna, Salmon, Tongkol)', 'category' => 'Seafood'],
            ['name' => 'Ikan Air Tawar (Gurame, Nila, Lele)', 'category' => 'Seafood'],
            ['name' => 'Udang', 'category' => 'Seafood'],
            ['name' => 'Cumi', 'category' => 'Seafood'],
            ['name' => 'Kepiting & Rajungan', 'category' => 'Seafood'],
            ['name' => 'Kerang & Tiram', 'category' => 'Seafood'],
            ['name' => 'Gurita', 'category' => 'Seafood'],
            ['name' => 'Terasi & Ebi', 'category' => 'Seafood'],
            ['name' => 'Petis Udang', 'category' => 'Seafood'],

            // Meat (Daging & Unggas)
            ['name' => 'Daging Ayam', 'category' => 'Meat'],
            ['name' => 'Daging Sapi', 'category' => 'Meat'],
            ['name' => 'Daging Kambing', 'category' => 'Meat'],
            ['name' => 'Daging Domba', 'category' => 'Meat'],
            ['name' => 'Daging Bebek', 'category' => 'Meat'],
            ['name' => 'Jeroan Sapi (Babat, Hati, Paru)', 'category' => 'Meat'],
            ['name' => 'Jeroan Ayam (Ati, Ampela, Usus)', 'category' => 'Meat'],
            ['name' => 'Daging Olahan (Sosis, Kornet, Ham)', 'category' => 'Meat'],

            // Dairy & Telur
            ['name' => 'Susu / Laktosa', 'category' => 'Dairy & Telur'],
            ['name' => 'Keju (Cheddar, Mozzarella, Parmesan)', 'category' => 'Dairy & Telur'],
            ['name' => 'Mentega / Butter', 'category' => 'Dairy & Telur'],
            ['name' => 'Krim Susu (Heavy Cream)', 'category' => 'Dairy & Telur'],
            ['name' => 'Yogurt', 'category' => 'Dairy & Telur'],
            ['name' => 'Telur Ayam', 'category' => 'Dairy & Telur'],
            ['name' => 'Telur Bebek', 'category' => 'Dairy & Telur'],
            ['name' => 'Telur Puyuh', 'category' => 'Dairy & Telur'],
            ['name' => 'Mayones', 'category' => 'Dairy & Telur'],

            // Kacang & Biji-bijian
            ['name' => 'Kacang', 'category' => 'Kacang & Biji'],
            ['name' => 'Kacang Tanah', 'category' => 'Kacang & Biji'],
            ['name' => 'Kacang Mete (Cashew)', 'category' => 'Kacang & Biji'],
            ['name' => 'Kacang Almond', 'category' => 'Kacang & Biji'],
            ['name' => 'Kedelai & Edamame', 'category' => 'Kacang & Biji'],
            ['name' => 'Tahu & Tempe', 'category' => 'Kacang & Biji'],
            ['name' => 'Biji Wijen & Minyak Wijen', 'category' => 'Kacang & Biji'],
            ['name' => 'Kacang Merah & Kacang Hijau', 'category' => 'Kacang & Biji'],

            // Gluten & Gandum
            ['name' => 'Tepung Terigu / Gandum', 'category' => 'Gluten & Gandum'],
            ['name' => 'Mi & Pasta Gandum', 'category' => 'Gluten & Gandum'],
            ['name' => 'Roti & Pastry', 'category' => 'Gluten & Gandum'],
            ['name' => 'Oat / Havermut', 'category' => 'Gluten & Gandum'],

            // Seasoning (Bumbu & Penyedap)
            ['name' => 'Garam', 'category' => 'Seasoning'],
            ['name' => 'Gula', 'category' => 'Seasoning'],
            ['name' => 'Kaldu Jamur', 'category' => 'Seasoning'],
            ['name' => 'MSG / Micin', 'category' => 'Seasoning'],
            ['name' => 'Bawang Merah & Bawang Putih', 'category' => 'Seasoning'],
            ['name' => 'Saus Tiram', 'category' => 'Seasoning'],
            ['name' => 'Kecap Kedelai', 'category' => 'Seasoning'],
            ['name' => 'Santan Kelapa', 'category' => 'Seasoning'],
            ['name' => 'Lada & Merica', 'category' => 'Seasoning'],
            ['name' => 'Ketumbar', 'category' => 'Seasoning'],

            // Lainnya (Preferensi & Rasa)
            ['name' => 'Pedas', 'category' => 'Lainnya'],
            ['name' => 'Cabai Rawit Ekstrem', 'category' => 'Lainnya'],
            ['name' => 'Jamur (Mushrooms)', 'category' => 'Lainnya'],
            ['name' => 'Pengawet / Pewarna Sintetis', 'category' => 'Lainnya'],
            ['name' => 'Cuka / Makanan Asam', 'category' => 'Lainnya'],
        ];

        foreach ($allergens as $allergen) {
            Allergen::updateOrCreate(
                ['name' => $allergen['name']],
                ['category' => $allergen['category']]
            );
        }

        $udang = Allergen::where('name', 'Udang')->first();
        $cumi = Allergen::where('name', 'Cumi')->first();
        $sapi = Allergen::where('name', 'Daging Sapi')->first();
        $ayam = Allergen::where('name', 'Daging Ayam')->first();
        $kacang = Allergen::where('name', 'Kacang')->first();
        $pedas = Allergen::where('name', 'Pedas')->first();
        $susu = Allergen::where('name', 'Susu / Laktosa')->first();

        // Weekly Menu (Monday - Friday)
        $monday = Carbon::now('Asia/Jakarta')->startOfWeek();

        $menus = [
            // MONDAY
            [
                'name' => 'Ayam Bakar Madu',
                'description' => 'Ayam bakar manis gurih dengan nasi hangat dan bumbu madu.',
                'price' => 35000,
                'photo_path' => 'https://images.unsplash.com/photo-1598514982205-f36b96d1e8d4?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'monday',
                'service_date' => $monday->copy()->toDateString(),
                'is_active' => true,
                'allergens' => [$ayam->id]
            ],
            [
                'name' => 'Spaghetti Bolognese',
                'description' => 'Pasta Italia dengan saus daging sapi giling dan keju parmesan.',
                'price' => 45000,
                'photo_path' => 'https://images.unsplash.com/photo-1598866594230-a7c12756260f?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'monday',
                'service_date' => $monday->copy()->toDateString(),
                'is_active' => true,
                'allergens' => [$sapi->id, $susu->id]
            ],
            [
                'name' => 'Salad Ayam Kacang Polong',
                'description' => 'Salad sayuran segar dengan dada ayam panggang dan dressing kacang.',
                'price' => 30000,
                'photo_path' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'monday',
                'service_date' => $monday->copy()->toDateString(),
                'is_active' => true,
                'allergens' => [$ayam->id, $kacang->id]
            ],

            // TUESDAY
            [
                'name' => 'Beef Teriyaki',
                'description' => 'Irisan daging sapi tumis dengan saus teriyaki dan sayuran.',
                'price' => 45000,
                'photo_path' => 'https://images.unsplash.com/photo-1627885747683-176aeec9ed4d?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'tuesday',
                'service_date' => $monday->copy()->addDays(1)->toDateString(),
                'is_active' => true,
                'allergens' => [$sapi->id]
            ],
            [
                'name' => 'Nasi Goreng Seafood',
                'description' => 'Nasi goreng bumbu rempah dengan potongan cumi dan udang.',
                'price' => 40000,
                'photo_path' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'tuesday',
                'service_date' => $monday->copy()->addDays(1)->toDateString(),
                'is_active' => true,
                'allergens' => [$udang->id, $cumi->id]
            ],
            [
                'name' => 'Mac & Cheese Pedas',
                'description' => 'Makaroni saus keju super lumer dengan taburan bubuk cabai jalapeno.',
                'price' => 38000,
                'photo_path' => 'https://images.unsplash.com/photo-1543339494-b4cd4f7ba686?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'tuesday',
                'service_date' => $monday->copy()->addDays(1)->toDateString(),
                'is_active' => true,
                'allergens' => [$susu->id, $pedas->id]
            ],

            // WEDNESDAY
            [
                'name' => 'Udang Saus Padang',
                'description' => 'Udang segar ukuran besar masak saus padang yang sangat pedas.',
                'price' => 42000,
                'photo_path' => 'https://images.unsplash.com/photo-1625938146369-adc83368bda2?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'wednesday',
                'service_date' => $monday->copy()->addDays(2)->toDateString(),
                'is_active' => true,
                'allergens' => [$udang->id, $pedas->id]
            ],
            [
                'name' => 'Ayam Kremes Spesial',
                'description' => 'Ayam goreng dengan taburan kremesan renyah, nasi, dan timun.',
                'price' => 32000,
                'photo_path' => 'https://images.unsplash.com/photo-1698204689404-b91c13cb24ec?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'wednesday',
                'service_date' => $monday->copy()->addDays(2)->toDateString(),
                'is_active' => true,
                'allergens' => [$ayam->id]
            ],
            [
                'name' => 'Rendang Daging',
                'description' => 'Daging sapi olahan bumbu rendang khas Minang yang kaya rempah.',
                'price' => 48000,
                'photo_path' => 'https://images.unsplash.com/photo-1634563462678-c0b82ebf32b6?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'wednesday',
                'service_date' => $monday->copy()->addDays(2)->toDateString(),
                'is_active' => true,
                'allergens' => [$sapi->id, $pedas->id]
            ],

            // THURSDAY
            [
                'name' => 'Gado-gado Siram',
                'description' => 'Sayuran rebus dengan saus kacang kental khas Indonesia.',
                'price' => 25000,
                'photo_path' => 'https://images.unsplash.com/photo-1548943487-a2e4e43b4850?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'thursday',
                'service_date' => $monday->copy()->addDays(3)->toDateString(),
                'is_active' => true,
                'allergens' => [$kacang->id]
            ],
            [
                'name' => 'Sate Taichan',
                'description' => 'Sate dada ayam bakar putih dengan perasan jeruk nipis dan sambal ulek pedas.',
                'price' => 35000,
                'photo_path' => 'https://images.unsplash.com/photo-1542316492-a169b5c2ebce?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'thursday',
                'service_date' => $monday->copy()->addDays(3)->toDateString(),
                'is_active' => true,
                'allergens' => [$ayam->id, $pedas->id]
            ],
            [
                'name' => 'Creamy Mushroom Soup',
                'description' => 'Sup jamur kental hangat dengan krim susu dan roti panggang bawang.',
                'price' => 32000,
                'photo_path' => 'https://images.unsplash.com/photo-1547592180-85f173990554?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'thursday',
                'service_date' => $monday->copy()->addDays(3)->toDateString(),
                'is_active' => true,
                'allergens' => [$susu->id]
            ],

            // FRIDAY
            [
                'name' => 'Nasi Kuning Komplit',
                'description' => 'Nasi kuning, ayam goreng, telur dadar iris, dan sambal terasi.',
                'price' => 30000,
                'photo_path' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'friday',
                'service_date' => $monday->copy()->addDays(4)->toDateString(),
                'is_active' => true,
                'allergens' => [$ayam->id, $pedas->id]
            ],
            [
                'name' => 'Burger Sapi Keju',
                'description' => 'Roti bun panggang dengan patty sapi tebal, saus tomat, dan lelehan keju laktosa.',
                'price' => 45000,
                'photo_path' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'friday',
                'service_date' => $monday->copy()->addDays(4)->toDateString(),
                'is_active' => true,
                'allergens' => [$sapi->id, $susu->id]
            ],
            [
                'name' => 'Sate Ayam Bumbu Kacang',
                'description' => 'Sate ayam bakar kecap dengan siraman bumbu kacang tanah manis gurih.',
                'price' => 38000,
                'photo_path' => 'https://images.unsplash.com/photo-1628294793617-64df7a1923ce?q=80&w=600&auto=format&fit=crop',
                'service_day' => 'friday',
                'service_date' => $monday->copy()->addDays(4)->toDateString(),
                'is_active' => true,
                'allergens' => [$ayam->id, $kacang->id]
            ],
        ];

        foreach ($menus as $menuData) {
            $allergenIds = $menuData['allergens'];
            unset($menuData['allergens']);

            $menu = Menu::create($menuData);
            $menu->allergens()->attach($allergenIds);
        }
    }
}
