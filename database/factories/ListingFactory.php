<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'unique_code' => 'ILN-' . strtoupper($this->faker->bothify('########')),
            'category_id' => Category::first()?->id ?? 1,
            'subcategory_id' => Subcategory::first()?->id ?? 1,
            'description' => $this->faker->paragraph(),
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'image' => 'listings/default.jpg',
            'user_id' => User::factory(),
            'status' => 'pending'
        ];
    }
}
