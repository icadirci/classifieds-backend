<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySubcategoryTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('api')->plainTextToken;

        return ['Authorization' => 'Bearer '.$token];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_lists_categories_with_subcategories(): void
    {
        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        Subcategory::create([
            'name' => 'Phones',
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'name' => 'Electronics',
                        'subcategories' => [
                            [
                                'name' => 'Phones',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_category(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $headers = $this->authHeaders($admin);

        $response = $this->postJson('/api/categories', ['name' => 'Vehicles'], $headers);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Vehicles',
                    'slug' => 'vehicles',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Vehicles',
            'slug' => 'vehicles',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_admin_cannot_create_category(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $headers = $this->authHeaders($user);

        $response = $this->postJson('/api/categories', ['name' => 'Services'], $headers);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_category(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $headers = $this->authHeaders($admin);

        $category = Category::create([
            'name' => 'Property',
            'slug' => 'property',
        ]);

        $response = $this->putJson("/api/categories/{$category->id}", ['name' => 'Real Estate'], $headers);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Real Estate',
                    'slug' => 'real-estate',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Real Estate',
            'slug' => 'real-estate',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_delete_category_and_its_subcategories(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $headers = $this->authHeaders($admin);

        $category = Category::create([
            'name' => 'Furniture',
            'slug' => 'furniture',
        ]);

        $subcategory = Subcategory::create([
            'name' => 'Chairs',
            'category_id' => $category->id,
        ]);

        $response = $this->deleteJson("/api/categories/{$category->id}", [], $headers);

        $response->assertStatus(200);

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);

        $this->assertSoftDeleted('subcategories', [
            'id' => $subcategory->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_lists_subcategories_optionally_filtered_by_category(): void
    {
        $firstCategory = Category::create([
            'name' => 'Jobs',
            'slug' => 'jobs',
        ]);

        $secondCategory = Category::create([
            'name' => 'Services',
            'slug' => 'services',
        ]);

        $first = Subcategory::create([
            'name' => 'Full Time',
            'category_id' => $firstCategory->id,
        ]);

        Subcategory::create([
            'name' => 'Consulting',
            'category_id' => $secondCategory->id,
        ]);

        $response = $this->getJson('/api/subcategories?category_id='.$firstCategory->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'id' => $first->id,
                        'name' => 'Full Time',
                        'category_id' => $firstCategory->id,
                    ],
                ],
            ])
            ->assertJsonMissing([
                'name' => 'Consulting',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_manage_subcategories(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $headers = $this->authHeaders($admin);

        $category = Category::create([
            'name' => 'Pets',
            'slug' => 'pets',
        ]);

        $createResponse = $this->postJson('/api/subcategories', [
            'name' => 'Cats',
            'category_id' => $category->id,
        ], $headers);

        $createResponse->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Cats',
                    'category_id' => $category->id,
                ],
            ]);

        $subcategoryId = $createResponse->json('data.id');

        $updateResponse = $this->putJson("/api/subcategories/{$subcategoryId}", [
            'name' => 'Kittens',
        ], $headers);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Kittens',
                ],
            ]);

        $deleteResponse = $this->deleteJson("/api/subcategories/{$subcategoryId}", [], $headers);

        $deleteResponse->assertStatus(200);

        $this->assertSoftDeleted('subcategories', [
            'id' => $subcategoryId,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_admin_cannot_manage_subcategories(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $headers = $this->authHeaders($user);

        $category = Category::create([
            'name' => 'Garden',
            'slug' => 'garden',
        ]);

        $createResponse = $this->postJson('/api/subcategories', [
            'name' => 'Plants',
            'category_id' => $category->id,
        ], $headers);

        $createResponse->assertStatus(403);
    }
}
