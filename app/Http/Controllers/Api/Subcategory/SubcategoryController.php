<?php

namespace App\Http\Controllers\Api\Subcategory;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class SubcategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $subcategories = Subcategory::query()
            ->when(
                $request->filled('category_id'),
                fn ($query) => $query->where('category_id', $request->integer('category_id'))
            )
            ->with('category')
            ->orderBy('name')
            ->get();

        $transformed = $subcategories->map(fn (Subcategory $subcategory) => $this->transformSubcategory($subcategory));

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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subcategories', 'name')->where(
                    fn ($query) => $query->where('category_id', $request->input('category_id'))
                ),
            ],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $data = $validator->validated();

        $subcategory = Subcategory::create($data)->load('category');

        return $this->success($this->transformSubcategory($subcategory), 'Subcategory created', 201);
    }

    public function show(Subcategory $subcategory)
    {
        $subcategory->load('category');

        return $this->success($this->transformSubcategory($subcategory));
    }

    public function update(Request $request, Subcategory $subcategory)
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
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('subcategories', 'name')
                    ->where(fn ($query) => $query->where('category_id', $request->input('category_id', $subcategory->category_id)))
                    ->ignore($subcategory->id),
            ],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $data = $validator->validated();

        $subcategory->update($data);

        $subcategory->load('category');

        return $this->success($this->transformSubcategory($subcategory), 'Subcategory updated');
    }

    public function destroy(Request $request, Subcategory $subcategory)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        if (! $user->is_admin) {
            return $this->error('Forbidden', [], 403);
        }

        $subcategory->delete();

        return $this->success(null, 'Subcategory deleted');
    }

    private function transformSubcategory(Subcategory $subcategory): array
    {
        return [
            'id' => $subcategory->id,
            'name' => $subcategory->name,
            'category_id' => $subcategory->category_id,
            'category_name' => $subcategory->relationLoaded('category')
                ? $subcategory->category?->name
                : null,
            'created_at' => $subcategory->created_at?->format('Y-m-d H:i:s'),
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
