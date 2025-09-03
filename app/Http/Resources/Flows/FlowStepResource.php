<?php

namespace App\Http\Resources\Flows;

use Illuminate\Http\Resources\Json\JsonResource;

class FlowStepResource extends JsonResource
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
            'expression' => $this->expression,
            'description' => $this->description,
            'order' => $this->order,
            'active' => $this->active,
            'flow_id' => $this->flow_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tags' => FlowTagResource::collection($this->tags),
        ];
    }
}
