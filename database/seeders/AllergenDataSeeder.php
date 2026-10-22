<?php

namespace Database\Seeders;

use App\Models\Allergen;
use Illuminate\Database\Seeder;

class AllergenDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allergens = [
            // SEAFOOD
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

            // MEAT (Daging & Unggas)
            ['name' => 'Daging Ayam', 'category' => 'Meat'],
            ['name' => 'Daging Sapi', 'category' => 'Meat'],
            ['name' => 'Daging Kambing', 'category' => 'Meat'],
            ['name' => 'Daging Domba', 'category' => 'Meat'],
            ['name' => 'Daging Bebek', 'category' => 'Meat'],
            ['name' => 'Jeroan Sapi (Babat, Hati, Paru)', 'category' => 'Meat'],
            ['name' => 'Jeroan Ayam (Ati, Ampela, Usus)', 'category' => 'Meat'],
            ['name' => 'Daging Olahan (Sosis, Kornet, Ham)', 'category' => 'Meat'],

            // DAIRY & TELUR
            ['name' => 'Susu / Laktosa', 'category' => 'Dairy & Telur'],
            ['name' => 'Keju (Cheddar, Mozzarella, Parmesan)', 'category' => 'Dairy & Telur'],
            ['name' => 'Mentega / Butter', 'category' => 'Dairy & Telur'],
            ['name' => 'Krim Susu (Heavy Cream)', 'category' => 'Dairy & Telur'],
            ['name' => 'Yogurt', 'category' => 'Dairy & Telur'],
            ['name' => 'Telur Ayam', 'category' => 'Dairy & Telur'],
            ['name' => 'Telur Bebek', 'category' => 'Dairy & Telur'],
            ['name' => 'Telur Puyuh', 'category' => 'Dairy & Telur'],
            ['name' => 'Mayones', 'category' => 'Dairy & Telur'],

            // KACANG & BIJI-BIJIAN
            ['name' => 'Kacang', 'category' => 'Kacang & Biji'],
            ['name' => 'Kacang Tanah', 'category' => 'Kacang & Biji'],
            ['name' => 'Kacang Mete (Cashew)', 'category' => 'Kacang & Biji'],
            ['name' => 'Kacang Almond', 'category' => 'Kacang & Biji'],
            ['name' => 'Kedelai & Edamame', 'category' => 'Kacang & Biji'],
            ['name' => 'Tahu & Tempe', 'category' => 'Kacang & Biji'],
            ['name' => 'Biji Wijen & Minyak Wijen', 'category' => 'Kacang & Biji'],
            ['name' => 'Kacang Merah & Kacang Hijau', 'category' => 'Kacang & Biji'],

            // GLUTEN & GANDUM
            ['name' => 'Tepung Terigu / Gandum', 'category' => 'Gluten & Gandum'],
            ['name' => 'Mi & Pasta Gandum', 'category' => 'Gluten & Gandum'],
            ['name' => 'Roti & Pastry', 'category' => 'Gluten & Gandum'],
            ['name' => 'Oat / Havermut', 'category' => 'Gluten & Gandum'],

            // SEASONING (Bumbu & Penyedap)
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

            // LAINNYA & PREFERENSI RASA
            ['name' => 'Pedas', 'category' => 'Lainnya'],
            ['name' => 'Cabai Rawit Ekstrem', 'category' => 'Lainnya'],
            ['name' => 'Jamur (Mushrooms)', 'category' => 'Lainnya'],
            ['name' => 'Pengawet / Pewarna Sintetis', 'category' => 'Lainnya'],
            ['name' => 'Cuka / Makanan Asam', 'category' => 'Lainnya'],
        ];

        foreach ($allergens as $item) {
            Allergen::updateOrCreate(
                ['name' => $item['name']],
                ['category' => $item['category']]
            );
        }
    }
}
