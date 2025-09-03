<?php

namespace App\Traits;

use App\Models\Acesso;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

trait RegistraAcesso
{
    private function salvarRegistroAcesso(array $dados): void
    {
        // Log de depuração - verifique o storage/logs/laravel.log
        Log::debug('Tentativa de registrar acesso', ['dados_recebidos' => $dados]);

        try {
            $agent = new Agent();
            $userAgent = request()->header('User-Agent');

            $dadosAcesso = [
                'user_id' => $dados['user_id'] ?? auth()->id(),
                'rota' => $dados['rota'] ?? request()->path(),
                'metodo' => $dados['metodo'] ?? request()->method(),
                'ip' => $dados['ip'] ?? request()->ip(),
                'dispositivo' => $this->detectarDispositivo($agent),
                'user_agent' => $userAgent,
                'navegador' => $agent->browser() ?? 'Desconhecido',
                'sistema_operacional' => $agent->platform() ?? 'Desconhecido',
                'status' => $dados['status'] ?? 'sucesso',
                'name' => $dados['name'] ?? null,
                'data_acesso' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            Log::debug('Dados preparados para inserção', ['dados_acesso' => $dadosAcesso]);

            // Verificação adicional antes de inserir
            if (empty($dadosAcesso['user_id'])) {
                Log::warning('User ID está vazio', $dadosAcesso);
            }

            $acesso = Acesso::create($dadosAcesso);

            Log::info('Acesso registrado com sucesso', ['id' => $acesso->id]);

        } catch (\Exception $e) {
            Log::error('Falha ao registrar acesso', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'dados' => $dados,
                'request' => [
                    'ip' => request()->ip(),
                    'path' => request()->path(),
                    'method' => request()->method(),
                    'user_agent' => request()->header('User-Agent'),
                ]
            ]);
        }
    }

    private function detectarDispositivo(Agent $agent): string
    {
        if ($agent->isMobile()) return 'mobile';
        if ($agent->isTablet()) return 'tablet';
        return 'desktop';
    }
}
