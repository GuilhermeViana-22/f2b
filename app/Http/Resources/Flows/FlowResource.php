<?php

namespace App\Http\Resources\Flows;

use Illuminate\Http\Resources\Json\JsonResource;

class FlowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->_id,
            'name' => $this->name,
            'note' => $this->note,
            'active' => $this->active,
            'selection_template_id' => $this->selection_template_id,
            'quantities_template_id' => $this->quantities_template_id,
            'external_integration_id' => $this->external_integration_id,
            'webhook_dataset_id' => $this->webhook_dataset_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'steps' => FlowStepResource::collection($this->whenLoaded('steps')),
        ];
    }
}
