<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_code' => $this->candidate_code,
            'display_name' => $this->display_name,
            'region_name' => $this->region_name,
            'seat_number' => $this->seat_number,
            'workstation_ip' => $this->workstation_ip,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
