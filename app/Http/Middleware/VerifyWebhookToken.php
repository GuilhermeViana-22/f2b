<?php

namespace App\Http\Middleware;

use App\Models\CompanyWebhookToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Webhook-Token');
        
        if (!$token) {
            return response()->json(['message' => 'Webhook token not provided'], 401);
        }

        $tokenHash = hash('sha256', $token);
        
        $webhookToken = CompanyWebhookToken::where('token_hash', $tokenHash)
            ->where('revoked', false)
            ->first();

        if (!$webhookToken) {
            return response()->json(['message' => 'Invalid webhook token'], 401);
        }

        // Optional: Validate payload signature if provided
        if ($signature = $request->header('X-Hook-Signature')) {
            $payload = $request->getContent();
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $token);
            
            if (!hash_equals($signature, $expectedSignature)) {
                return response()->json(['message' => 'Invalid webhook signature'], 401);
            }
        }

        // Check if company is active
        $company = $webhookToken->company;
        if (!$company || !$company->status) {
            return response()->json(['message' => 'Company inactive or not found'], 401);
        }

        // Mark token as used and attach company_id to request
        $webhookToken->markAsUsed();
        $request->merge(['company_id' => $company->id]);

        return $next($request);
    }
}
