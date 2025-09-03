<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Acesso;
use App\Models\User;
use Carbon\Carbon;

class AcessoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Verificar se existem usuários antes de tentar usá-los
        $users = User::all();
        $hasUsers = $users->isNotEmpty();

        // Listas de dados para randomização
        $rotas = [
            '/dashboard',
            '/perfil',
            '/configuracoes',
            '/relatorios',
            '/clientes',
            '/produtos',
            '/vendas',
            '/login',
            '/api/pedidos',
            '/api/clientes'
        ];

        $metodos = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        $dispositivos = ['desktop', 'mobile', 'tablet', 'outro'];

        $navegadores = [
            'Chrome',
            'Firefox',
            'Safari',
            'Edge',
            'Opera',
            'Internet Explorer'
        ];

        $sistemasOperacionais = [
            'Windows 10',
            'Windows 11',
            'macOS',
            'Linux',
            'iOS',
            'Android'
        ];

        $paises = ['Brasil', 'EUA', 'Portugal', 'Argentina', 'Espanha', 'Alemanha'];
        $regioes = [
            'Brasil' => ['São Paulo', 'Rio de Janeiro', 'Minas Gerais', 'Bahia'],
            'EUA' => ['Califórnia', 'Texas', 'Nova York', 'Florida'],
            'Portugal' => ['Lisboa', 'Porto', 'Braga'],
            'Argentina' => ['Buenos Aires', 'Córdoba', 'Mendoza'],
            'Espanha' => ['Madrid', 'Barcelona', 'Valência'],
            'Alemanha' => ['Berlim', 'Munique', 'Hamburgo']
        ];

        $status = ['sucesso', 'falha', 'negado', 'erro'];

        // Gerar 30 acessos fictícios
        for ($i = 0; $i < 30; $i++) {
            $user = null;
            if ($hasUsers && rand(0, 1)) {
                $user = $users->random();
            }

            $pais = $paises[array_rand($paises)];
            $regiao = $regioes[$pais][array_rand($regioes[$pais])];
            $dataAcesso = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 24));

            Acesso::create([
                'user_id' => $user ? $user->id : null,
                'rota' => $rotas[array_rand($rotas)],
                'metodo' => $metodos[array_rand($metodos)],
                'ip' => '192.168.' . rand(0, 255) . '.' . rand(0, 255),
                'dispositivo' => $dispositivos[array_rand($dispositivos)],
                'user_agent' => $this->generateUserAgent(),
                'navegador' => $navegadores[array_rand($navegadores)],
                'sistema_operacional' => $sistemasOperacionais[array_rand($sistemasOperacionais)],
                'codigo_status' => $this->getStatusCode($status[array_rand($status)]),
                'status' => $status[array_rand($status)],
                'tempo_resposta' => rand(10, 5000) / 1000,
                'pais' => $pais,
                'regiao' => $regiao,
                'ativo' => (bool) rand(0, 1),
                'data_acesso' => $dataAcesso,
                'created_at' => $dataAcesso,
                'updated_at' => $dataAcesso
            ]);
        }
    }

    /**
     * Gerar um user agent fictício
     */
    private function generateUserAgent()
    {
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/{version} Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:{version}) Gecko/20100101 Firefox/{version}',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/{version} Safari/605.1.15',
            'Mozilla/5.0 (iPhone; CPU iPhone OS {version} like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/{version} Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android {version}; SM-G973F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/{version} Mobile Safari/537.36'
        ];

        $agent = $agents[array_rand($agents)];
        return str_replace('{version}', rand(10, 15) . '.' . rand(0, 9), $agent);
    }

    /**
     * Obter código HTTP baseado no status
     */
    private function getStatusCode($status)
    {
        switch ($status) {
            case 'sucesso':
                return rand(0, 1) ? 200 : 201;
            case 'falha':
                return 401;
            case 'negado':
                return 403;
            case 'erro':
                return rand(0, 1) ? 500 : 404;
            default:
                return 200;
        }
    }
}
