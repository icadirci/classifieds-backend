<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $categories = Category::with('subcategories')->orderBy('name')->get();

        $transformed = $categories->map(fn (Category $category) => $this->transformCategory($category));

        return $this->success($transformed);
    }

    public function store(Request $request)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        if (! $user->is_admin) {
            return $this->error('Forbidden', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $data = $validator->validated();

        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ])->load('subcategories');

        return $this->success($this->transformCategory($category), 'Category created', 201);
    }

    public function show(Category $category)
    {
        $category->load('subcategories');

        return $this->success($this->transformCategory($category));
    }

    public function update(Request $request, Category $category)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        if (! $user->is_admin) {
            return $this->error('Forbidden', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $data = $validator->validated();

        $payload = ['name' => $data['name']];

        if ($category->name !== $data['name']) {
            $payload['slug'] = Str::slug($data['name']);
        }

        $category->update($payload);

        $category->load('subcategories');

        return $this->success($this->transformCategory($category), 'Category updated');
    }

    public function destroy(Request $request, Category $category)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        if (! $user->is_admin) {
            return $this->error('Forbidden', [], 403);
        }

        $category->subcategories()->delete();
        $category->delete();

        return $this->success(null, 'Category deleted');
    }

    private function transformCategory(Category $category): array
    {
        $category->setRelation('subcategories', $category->subcategories->sortBy('name')->values());

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'subcategories' => $category->subcategories->map(fn ($subcategory) => [
                'id' => $subcategory->id,
                'name' => $subcategory->name,
                'category_id' => $subcategory->category_id,
            ]),
            'created_at' => $category->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function resolveAuthenticatedUser(Request $request)
    {
        if ($request->user()) {
            return $request->user();
        }

        $token = $request->bearerToken();

        if (! $token) {
            return null;
        }

        return PersonalAccessToken::findToken($token)?->tokenable;
    }
}
