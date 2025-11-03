<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'SubcategorySeeder']);
    }

    private function authHeaders($user)
    {
        $token = $user->createToken('api')->plainTextToken;
        return ['Authorization' => 'Bearer '.$token];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_listing()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $data = [
            'title' => 'Test Listing',
            'category_id' => 1,
            'subcategory_id' => 1,
            'description' => 'Test description',
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'image' => UploadedFile::fake()->image('test.jpg'),
        ];

        $response = $this->postJson('/api/listings', $data, $headers);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'title',
                        'image_url',
                        'status'
                    ]
                 ]);

        $this->assertDatabaseHas('listings', [
            'title' => 'Test Listing',
            'status' => 'pending',
        ]);

        Storage::disk('public')->assertExists('listings/'.$data['image']->hashName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_own_listing()
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => $user->id]);

        $headers = $this->authHeaders($user);

        $response = $this->putJson("/api/listings/{$listing->id}", [
            'title' => 'Updated title',
        ], $headers);

        $response->assertStatus(200);
        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'title' => 'Updated title'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_update_other_users_listing()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $listing = Listing::factory()->create(['user_id' => $user1->id]);

        $headers = $this->authHeaders($user2);

        $response = $this->putJson("/api/listings/{$listing->id}", [
            'title' => 'Malicious change',
        ], $headers);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_delete_own_listing()
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => $user->id]);

        $headers = $this->authHeaders($user);

        $response = $this->deleteJson("/api/listings/{$listing->id}", [], $headers);

        $response->assertStatus(200);
        $this->assertSoftDeleted('listings', [
            'id' => $listing->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_approve_listing()
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $listing = Listing::factory()->create(['status' => 'pending']);

        $headers = $this->authHeaders($admin);

        $response = $this->postJson("/api/admin/listings/{$listing->id}/approve", [], $headers);

        $response->assertStatus(200);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'status' => 'approved'
        ]);
    }
}
