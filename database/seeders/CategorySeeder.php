<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Emlak',
            'Vasıta',
            'Elektronik',
            'Mobilya & Ev Eşyaları',
            'İş İlanları',
            'Hizmetler',
            'Hayvanlar',
            'Diğer'
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'slug' => Str::slug($category)
            ]);
        }
    }
}
