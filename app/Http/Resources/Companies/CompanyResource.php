<?php

namespace App\Http\Resources\Companies;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CompanyResource extends JsonResource
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
            'id' => $this->id,
            'company' => $this->company,
            'abn' => $this->abn,
            'admin_email' => $this->admin_email,
            'invoice_email' => $this->invoice_email,
            'status' => $this->status,
            'created_at' => $this->formatDateTime($this->created_at),
            'updated_at' => $this->formatDateTime($this->updated_at),
        ];
    }

    /**
     * Formata data/hora no padrão ISO 8601, ou null se vazio.
     */
    private function formatDateTime($dateTime)
    {
        return $dateTime ? Carbon::parse($dateTime)->toIso8601String() : null;
    }
}
