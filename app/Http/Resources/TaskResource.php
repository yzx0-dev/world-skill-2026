<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => $this->description,
            'frontend_requirements' => $this->frontend_requirements ?? [],
            'backend_requirements' => $this->backend_requirements ?? [],
            'business_rules' => $this->business_rules ?? [],
        ];
    }
}
