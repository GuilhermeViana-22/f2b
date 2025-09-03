<?php

namespace App\Http\Resources\Flows;

use Illuminate\Http\Resources\Json\JsonResource;

class FlowStepHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->_id,
            'flow_step_id' => $this->flow_step_id,
            'flow_id' => $this->flow_id,
            'version' => $this->version,
            'name' => $this->name,
            'expression' => $this->expression,
            'description' => $this->description,
            'order' => $this->order,
            'active' => $this->active,
            'user_id' => $this->user_id,
            'tag_ids' => $this->tag_ids,
            'tags' => $this->whenLoaded('tags', function() {
                return $this->tags;
            }),
            'change_type' => $this->change_type,
            'change_description' => $this->change_description,
            'is_current' => $this->is_current,
            'previous_version_id' => $this->previous_version_id,
            'snapshot_data' => $this->snapshot_data,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i:s'),
            'created_at_human' => $this->created_at?->diffForHumans(),
            
            // Dados relacionais quando carregados
            'flow_step' => $this->whenLoaded('flowStep', function() {
                return new FlowStepResource($this->flowStep);
            }),
            'flow' => $this->whenLoaded('flow', function() {
                return new FlowResource($this->flow);
            }),
        ];
    }
}
