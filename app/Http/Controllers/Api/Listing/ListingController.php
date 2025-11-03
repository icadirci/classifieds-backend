<?php

namespace App\Http\Controllers\Api\Listing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\StoreListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use App\Http\Requests\Listing\UpdateListingRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ListingController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function index()
    {
        $listings = Listing::where('status', 'approved')->latest()->paginate(10);
        return $this->success(ListingResource::collection($listings));
    }

    public function store(StoreListingRequest $request)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        $data = $request->validated();

        $imagePath = $request->file('image')->store('listings', 'public');

        $listing = Listing::create([
            ...$data,
            'image' => $imagePath,
            'user_id' => $user->id,
            'unique_code' => 'ILN-' . strtoupper(Str::random(8)),
            'status' => 'pending'
        ]);

        return $this->success(new ListingResource($listing), 'Listing created. Waiting for admin approval.');
    }

    public function show(Listing $listing)
    {
        return $this->success(new ListingResource($listing));
    }

    public function update(UpdateListingRequest $request, Listing $listing)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        if ((int) $listing->user_id !== (int) $user->id) {
            return $this->error('Forbidden', [], 403);
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('listings', 'public');
        }

        $listing->update($data);

        return $this->success(new ListingResource($listing), 'Listing updated');
    }

    public function destroy(Request $request, Listing $listing)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        if ((int) $listing->user_id !== (int) $user->id) {
            return $this->error('Forbidden', [], 403);
        }

        $listing->delete();

        return $this->success(null, 'Listing deleted');
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
