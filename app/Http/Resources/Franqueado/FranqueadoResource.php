<?php

namespace App\Http\Resources\Franqueado;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FranqueadoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('d/m/Y H:i:s') : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('d/m/Y H:i:s') : null,
            'deleted_at' => $this->deleted_at ? Carbon::parse($this->deleted_at)->format('d/m/Y H:i:s') : null,
        ];
    }
}