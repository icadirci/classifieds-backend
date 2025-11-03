<?php

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Sanctum\PersonalAccessToken;

class UpdateListingRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'subcategory_id' => 'sometimes|exists:subcategories,id',
            'description' => 'sometimes|string|min:10',
            'city' => 'sometimes|string|max:100',
            'district' => 'sometimes|string|max:100',
            'image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048'
        ];
    }
}
