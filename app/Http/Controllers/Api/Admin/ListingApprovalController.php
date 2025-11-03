<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Listing;
use App\Traits\ApiResponse;
use App\Http\Resources\ListingResource;
use Laravel\Sanctum\PersonalAccessToken;


class ListingApprovalController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        if (! $user->is_admin) {
            return $this->error('Forbidden', [], 403);
        }

        $pendingListings = Listing::where('status', 'pending')->get();
        return $this->success(ListingResource::collection($pendingListings), 'Pending listings');
    }

    public function approve(Request $request, Listing $listing)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        if (! $user->is_admin) {
            return $this->error('Forbidden', [], 403);
        }

        $listing->update(['status' => 'approved']);

        return $this->success(new ListingResource($listing->refresh()), 'Listing approved');
    }

    public function reject(Request $request, Listing $listing)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return $this->error('Unauthorized', [], 401);
        }

        if (! $user->is_admin) {
            return $this->error('Forbidden', [], 403);
        }

        $listing->update(['status' => 'rejected']);

        return $this->success(new ListingResource($listing->refresh()), 'Listing rejected');
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
