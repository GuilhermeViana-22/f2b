<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExternalIntegrationResource extends JsonResource
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
            'id' => (string) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'baseUrl' => $this->base_url,
            'dataEndpoint' => $this->data_endpoint,
            'authType' => $this->auth_type,
            'authConfiguration' => $this->auth_configuration,
            'oauthTokenUrl' => $this->oauth_token_url,
            'oauthGrantType' => $this->oauth_grant_type,
            'oauthTokenContentType' => $this->oauth_token_content_type,
            'requestMethod' => $this->request_method,
            'requestContentType' => $this->request_content_type,
            'requestHeaders' => $this->request_headers,
            'requestParameters' => $this->request_parameters,
            'requestBody' => $this->request_body,
            'dataFormat' => $this->data_format,
            'isActive' => $this->is_active,
            'lastUsed' => $this->last_used?->toISOString(),
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
        ];
    }
}
