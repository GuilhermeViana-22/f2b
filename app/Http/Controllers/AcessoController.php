<?php

namespace App\Http\Controllers;

use App\Models\Acesso;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AcessoController extends Controller
{
    /**
     * Retorna dados agregados para o dashboard.
     */
    public function dashboard()
    {
        try {
            $agora = Carbon::now();
            $meiaNoite = Carbon::today();
            $ultimas24h = $agora->copy()->subHours(24);

            return response()->json([
                'success' => true,
                'message' => 'Dados do dashboard recuperados com sucesso',
                'data' => [
                    'contadores' => [
                        'usuarios_ativos' => $this->contarUsuariosAtivos(),
                        'acessos_hoje' => $this->contarAcessosDesde($meiaNoite),
                        'ips_distintos' => $this->contarIpsDistintosDesde($meiaNoite),
                        'tentativas_falhas' => $this->contarTentativasFalhasDesde($meiaNoite),
                        'ultimas24h_acessos' => $this->contarAcessosDesde($ultimas24h),
                        'ultimas24h_ips_distintos' => $this->contarIpsDistintosDesde($ultimas24h),
                        'ultimas24h_tentativas_falhas' => $this->contarTentativasFalhasDesde($ultimas24h),
                    ],
                    'acessos_por_hora' => $this->obterDadosPorHora(),
                    'acessos_recentes' => $this->obterAcessosRecentes(),
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao recuperar dados do dashboard',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Conta usuários ativos no sistema.
     */
    public function contarUsuariosAtivos()
    {
        return User::where('ativo', true)->count();
    }

    /**
     * Conta acessos a partir de uma data fornecida.
     */
    public function contarAcessosDesde($data)
    {
        return Acesso::where('data_acesso', '>=', $data)->count();
    }

    /**
     * Conta IPs distintos a partir de uma data fornecida.
     */
    public function contarIpsDistintosDesde($data)
    {
        return Acesso::where('data_acesso', '>=', $data)
            ->distinct('ip')
            ->count('ip');
    }

    /**
     * Conta tentativas de acesso com falha a partir de uma data fornecida.
     */
    public function contarTentativasFalhasDesde($data)
    {
        return Acesso::where('data_acesso', '>=', $data)
            ->where('status', 'falha')
            ->count();
    }

    /**
     * Retorna o total de acessos por hora nas últimas 24 horas.
     */
    public function obterDadosPorHora()
    {
        return Acesso::selectRaw('HOUR(data_acesso) as hora, COUNT(*) as total')
            ->where('data_acesso', '>=', Carbon::now()->subHours(24))
            ->groupBy('hora')
            ->orderBy('hora')
            ->get()
            ->pluck('total', 'hora');
    }

    /**
     * Retorna os últimos 12 acessos registrados.
     */
    public function obterAcessosRecentes()
    {
        return Acesso::with('user')
            ->orderBy('data_acesso', 'desc')
            ->limit(12)
            ->get()
            ->map(function ($acesso) {
                return [
                    'usuario' => $acesso->user ? $acesso->user->name : 'Visitante',
                    'data_hora' => $acesso->data_acesso->format('d/m/Y H:i:s'),
                    'rota' => $acesso->rota,
                    'ip' => $acesso->ip,
                    'dispositivo' => $acesso->dispositivo,
                    'status' => $acesso->status,
                    'navegador' => $acesso->navegador,
                    'tempo_resposta' => $acesso->tempo_resposta
                ];
            });
    }

    /**
     * Retorna dados agregados por dispositivo dos últimos 30 dias.
     */
    public function obterDadosPorDispositivo()
    {
        try {
            $dados = Acesso::selectRaw('dispositivo, COUNT(*) as total')
                ->where('data_acesso', '>=', Carbon::now()->subDays(30))
                ->groupBy('dispositivo')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dados por dispositivo recuperados com sucesso',
                'data' => [
                    'dados' => $dados,
                    'periodo' => 'Últimos 30 dias'
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao recuperar dados por dispositivo',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retorna dados agregados por status dos últimos 30 dias.
     */
    public function obterDadosPorStatus()
    {
        try {
            $dados = Acesso::selectRaw('status, COUNT(*) as total')
                ->where('data_acesso', '>=', Carbon::now()->subDays(30))
                ->groupBy('status')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dados por status recuperados com sucesso',
                'data' => [
                    'dados' => $dados,
                    'periodo' => 'Últimos 30 dias'
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao recuperar dados por status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retorna detalhes de um acesso específico.
     */
    public function detalhes($id)
    {
        try {
            $acesso = Acesso::with('user')->find($id);

            if (!$acesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso não encontrado'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detalhes do acesso recuperados com sucesso',
                'data' => [
                    'id' => $acesso->id,
                    'usuario' => $acesso->user ? $acesso->user->name : 'Visitante',
                    'email' => $acesso->user ? $acesso->user->email : null,
                    'data_hora' => $acesso->data_acesso->format('d/m/Y H:i:s'),
                    'rota' => $acesso->rota,
                    'metodo' => $acesso->metodo,
                    'ip' => $acesso->ip,
                    'localizacao' => [
                        'pais' => $acesso->pais,
                        'regiao' => $acesso->regiao
                    ],
                    'dispositivo' => $acesso->dispositivo,
                    'navegador' => $acesso->navegador,
                    'sistema_operacional' => $acesso->sistema_operacional,
                    'status' => $acesso->status,
                    'codigo_status' => $acesso->codigo_status,
                    'tempo_resposta' => $acesso->tempo_resposta,
                    'user_agent' => $acesso->user_agent
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao recuperar detalhes do acesso',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Registra um novo acesso (pode ser usado por sistemas externos).
     */
    public function registrarAcesso(Request $request)
    {
        try {
            $dados = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'rota' => 'required|string|max:255',
                'metodo' => 'required|string|max:10',
                'ip' => 'required|ip',
                'user_agent' => 'nullable|string',
                'dispositivo' => 'nullable|string|in:desktop,mobile,tablet,outro',
                'status' => 'required|string|in:sucesso,falha,negado,erro',
                'codigo_status' => 'required|integer',
                'tempo_resposta' => 'nullable|numeric'
            ]);

            $acesso = Acesso::create($dados);

            return response()->json([
                'success' => true,
                'message' => 'Acesso registrado com sucesso',
                'data' => [
                    'id' => $acesso->id
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao registrar acesso',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Permite filtrar acessos com base em critérios diversos.
     */
    public function filtrar(Request $request)
    {
        try {
            $query = Acesso::query();

            if ($request->filled('usuario_id')) {
                $query->porUsuario($request->usuario_id);
            }

            if ($request->filled('ip')) {
                $query->porIp($request->ip);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('data_inicio') && $request->filled('data_fim')) {
                $query->whereBetween('data_acesso', [
                    Carbon::parse($request->data_inicio),
                    Carbon::parse($request->data_fim)
                ]);
            }

            $acessos = $query->with('user')
                ->orderBy('data_acesso', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Acessos filtrados recuperados com sucesso',
                'data' => [
                    'acessos' => $acessos->items(),
                    'pagination' => [
                        'total' => $acessos->total(),
                        'per_page' => $acessos->perPage(),
                        'current_page' => $acessos->currentPage(),
                        'last_page' => $acessos->lastPage(),
                        'from' => $acessos->firstItem(),
                        'to' => $acessos->lastItem(),
                    ],
                    'links' => [
                        'first' => $acessos->url(1),
                        'last' => $acessos->url($acessos->lastPage()),
                        'prev' => $acessos->previousPageUrl(),
                        'next' => $acessos->nextPageUrl(),
                    ],
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao filtrar acessos',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
