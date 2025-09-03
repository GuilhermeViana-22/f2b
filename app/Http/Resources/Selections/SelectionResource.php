<?php

namespace App\Http\Resources\Selections;

use Illuminate\Http\Resources\Json\JsonResource;

class SelectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->_id,
            'jobNo' => $this->jobNo,
            'client' => $this->client,
            'houseType' => $this->houseType,
            'specification' => $this->specification,
            'lotAddress' => $this->lotAddress,
            'elevation' => $this->elevation,
            'choices_count' => $this->choices_count,
            'deleted_at' => $this->when($this->deleted_at !== null, $this->deleted_at),

            // Relacionamentos condicionais usando whenLoaded
            'choices' => $this->whenLoaded('choices', function() {
                return $this->choices->map(function($choice) {
                    return [
                        'id' => $choice->_id,
                        'selection_id' => $choice->name,
                        'selection' => $choice->selection ?? null,
                        'selectionGroup' => $choice->selectionGroup ?? null,
                        'area' => $choice->area ?? null,
                        'cadRef' => $choice->cadRef ?? null,
                        'clientNote' => $choice->clientNote ?? null,
                        'adminNote' => $choice->adminNote ?? null,
                        'item' => $choice->item ?? null,
                        'itemId' => $choice->itemId ?? null,
                        'itemThumbnail' => $choice->itemThumbnail ?? null,
                        'manufacturer' => $choice->manufacturer ?? null,
                        'productCode' => $choice->productCode ?? null,
                        'relativePrice' => $choice->relativePrice ?? null,
                        'schedulingRef' => $choice->schedulingRef ?? null,
                        'totalCost' => $choice->totalCost ?? null,
                        'totalQuantity' => $choice->totalQuantity ?? null,
                    ];
                });
            }),

        ];
    }
}
