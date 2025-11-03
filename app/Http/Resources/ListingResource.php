<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'unique_code' => $this->unique_code,
            'category' => optional($this->category)->name,
            'subcategory' => optional($this->subcategory)->name,
            'description' => $this->description,
            'city' => $this->city,
            'district' => $this->district,
            'image_url' => asset('storage/'.$this->image),
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
