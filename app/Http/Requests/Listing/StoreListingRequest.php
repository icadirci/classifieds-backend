<?php

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Sanctum\PersonalAccessToken;

class StoreListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($user = $this->user()) {
            return true;
        }

        $token = $this->bearerToken();

        if (! $token) {
            return false;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return false;
        }

        $user = $accessToken->tokenable;

        $this->setUserResolver(fn () => $user);

        return $user !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'description' => 'required|string|min:10',
            'city' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ];
    }
}
