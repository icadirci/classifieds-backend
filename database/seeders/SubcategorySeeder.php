<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
  $subcategories = [
            'Emlak' => [
                'Daire',
                'Müstakil Ev',
                'Arsa',
                'Ofis',
                'Dükkan & Mağaza',
                'Villa'
            ],
            'Vasıta' => [
                'Otomobil',
                'Motorsiklet',
                'Kamyon & Tır',
                'Bisiklet',
                'Karavan'
            ],
            'Elektronik' => [
                'Cep Telefonu',
                'Bilgisayar & Laptop',
                'Televizyon',
                'Fotoğraf Makinesi',
                'Tablet'
            ],
            'Mobilya & Ev Eşyaları' => [
                'Koltuk Takımı',
                'Yatak & Baza',
                'Dolap',
                'Masa & Sandalye',
                'Beyaz Eşya'
            ],
            'İş İlanları' => [
                'Tam Zamanlı',
                'Yarı Zamanlı',
                'Freelance',
                'Staj'
            ],
            'Hizmetler' => [
                'Temizlik Hizmeti',
                'Tadilat & Tamirat',
                'Nakliye',
                'Danışmanlık',
                'Özel Ders'
            ],
            'Hayvanlar' => [
                'Kedi',
                'Köpek',
                'Kuş',
                'Balık'
            ],
            'Diğer' => [
                'Diğer İlanlar'
            ]
        ];


        foreach ($subcategories as $categoryName => $subs) {
            $category = Category::where('slug', Str::slug($categoryName))->first();

            foreach ($subs as $sub) {
                Subcategory::create([
                    'name' => $sub,
                    'category_id' => $category->id
                ]);
            }
        }
    }
}
