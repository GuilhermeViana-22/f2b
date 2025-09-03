<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AcessoController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SelectionController;
use App\Http\Controllers\FlowController;
use App\Http\Controllers\FlowStepController;
use App\Http\Controllers\FlowTagController;
use App\Http\Controllers\FlowDatasetTemplateController;
use App\Http\Controllers\ExternalIntegrationController;
use App\Http\Controllers\VerificationCodeController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookDatasetController;
use Illuminate\Support\Facades\Route;

// ========================================
// ROTAS PÚBLICAS DE AUTENTICAÇÃO
// ========================================

Route::get('/teste', [DashboardController::class, 'teste']);
Route::get('/cors-test', [DashboardController::class, 'corsTest']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/mail-verify', [AuthController::class, 'mailVerify'])->name('api.auth.mail-verify');
    Route::post('/verify-code', [VerificationCodeController::class, 'verifyCode'])->name('api.auth.verify-code');
    Route::post('/validate-token', [AuthController::class, 'validateToken'])->name('api.auth.validate-token');

    // ✅ Agora protegida
    Route::middleware('auth:api')->post('/reset-password', [AuthController::class, 'reset'])->name('api.auth.reset-password');
});



// ========================================
// ROTAS PÚBLICAS GERAIS
// ========================================

Route::prefix('webhook')->group(function () {
    // Rota para interpretação de quantidades via webhook
    Route::post('/interpret-quantities', [WebhookController::class, 'interpretQuantities'])
    ->middleware('auth.webhook')
    ->name('api.interpret-quantities');
});

Route::prefix('logs')->group(function () {
    Route::get('/', [DashboardController::class, 'index']); // Lista todos os logs
    Route::get('/stats', [DashboardController::class, 'stats']); // Estatísticas
    Route::get('/recent/{limit?}', [DashboardController::class, 'recent']); // Recentes
    Route::get('/user-activities', [DashboardController::class, 'userActivities']); // Atividades por usuário
    Route::get('/filter', [DashboardController::class, 'filter']); // Filtro avançado
});

// ROTA TEMPORÁRIA PARA TESTE - REMOVER EM PRODUÇÃO
Route::prefix('permissions-test')->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
});

// ========================================
// ROTAS PROTEGIDAS POR AUTENTICAÇÃO
// ========================================
Route::middleware('auth:api')->group(function () {

    // Rotas de autenticação que precisam de ‘token’
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::delete('/delete-account', [AuthController::class, 'delete'])->name('api.auth.delete-account');
        Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    });

    // Rotas para gestão de cargos
    Route::prefix('positions')->group(function () {
        Route::get('/', [PositionController::class, 'index'])->name('api.positions.index');
        Route::get('/list', [PositionController::class, 'listPermissions'])->name('api.positions.list');
        Route::post('/', [PositionController::class, 'store'])->name('api.positions.store');
        Route::get('/{id}', [PositionController::class, 'show'])->name('api.positions.show');
        Route::put('/{id}', [PositionController::class, 'update'])->name('api.positions.update');
        Route::delete('/{id}', [PositionController::class, 'destroy'])->name('api.positions.destroy');



    });

    // Rotas para o dashboard de acessos
    Route::prefix('acessos')->group(function () {
        Route::get('dashboard', [AcessoController::class, 'dashboard']);
        Route::get('listar', [AcessoController::class, 'filtrar']);
        Route::get('por-hora', [AcessoController::class, 'obterDadosPorHora']);
        Route::get('por-dispositivo', [AcessoController::class, 'obterDadosPorDispositivo']);
        Route::get('por-status', [AcessoController::class, 'obterDadosPorStatus']);
        Route::get('detalhes/{id}', [AcessoController::class, 'detalhes']);
    });

    // Rotas para gestão de utilizadores
    // Rotas para gestão de utilizadores
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::match(['post', 'put'], '/upload-photo', [UserController::class, 'uploadPhoto'])->name('users.upload-photo');
        Route::post('/{id}/activate', [UserController::class, 'activate']);
        Route::post('/{id}/deactivate', [UserController::class, 'deactivate']);
    });

    // Rotas para gestão de unidades
    Route::prefix('company')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('api.company.index');
        Route::post('/', [CompanyController::class, 'store'])->name('api.company.store');
        Route::get('/{id}', [CompanyController::class, 'show'])->name('api.company.show');
        Route::put('/{id}', [CompanyController::class, 'update'])->name('api.company.update');
        Route::delete('/{id}', [CompanyController::class, 'destroy'])->name('api.company.destroy');
        Route::patch('/{id}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('api.company.toggle-status');
    });

    // Rotas para gestão de permissões
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
    });

    // Rotas para gestão de tokens de webhook
    Route::prefix('webhooks')->group(function () {
        Route::get('/tokens', [WebhookController::class, 'listTokens'])->name('api.webhooks.list-tokens');
        Route::post('/tokens', [WebhookController::class, 'generateToken'])->name('api.webhooks.generate-token');
        Route::delete('/tokens/{id}', [WebhookController::class, 'revokeToken'])->name('api.webhooks.revoke-token');

        // Dataset routes
        Route::get('/datasets', [WebhookDatasetController::class, 'index'])->name('api.webhooks.datasets.index');
        Route::get('/datasets/{id}', [WebhookDatasetController::class, 'show'])->name('api.webhooks.datasets.show');
        Route::delete('/datasets/{id}', [WebhookDatasetController::class, 'destroy'])->name('api.webhooks.datasets.destroy');
    });

    // Rotas para gestão de selections
    Route::prefix('selections')->group(function () {
        Route::get('/', [SelectionController::class, 'index'])->name('api.selections.index');
        Route::post('/', [SelectionController::class, 'store'])->name('api.selections.store');
        Route::put('/{id}', [SelectionController::class, 'update'])->name('api.selections.update');
        Route::get('/{id}', [SelectionController::class, 'show'])->name('api.selections.show');
        Route::delete('/{id}', [SelectionController::class, 'destroy'])->name('api.selections.destroy');
        Route::post('/search', [SelectionController::class, 'search'])->name('api.selections.search');
    });

    // Rotas para gestão de flows
    Route::prefix('flows')->group(function () {
        // Rotas de processamento
        Route::post('/interpret', [FlowController::class, 'interpret'])->name('api.flows.interpret');
        Route::post('/test', [FlowController::class, 'test'])->name('api.flows.test');
        Route::get('/functions', [FlowController::class, 'getFunctions'])->name('api.flows.functions');

        // CRUD de Flows
        Route::get('/', [FlowController::class, 'index'])->name('api.flows.index');
        Route::get('/active', [FlowController::class, 'getActiveFlow'])->name('api.flows.active');
        Route::post('/', [FlowController::class, 'store'])->name('api.flows.store');
        Route::get('/{id}', [FlowController::class, 'show'])->name('api.flows.show');
        Route::put('/{id}', [FlowController::class, 'update'])->name('api.flows.update');
        Route::delete('/{id}', [FlowController::class, 'destroy'])->name('api.flows.destroy');
        Route::patch('/{id}/toggle-status', [FlowController::class, 'toggleStatus'])->name('api.flows.toggle-status');
    });

    // Rotas para gestão individual de flow steps
    Route::prefix('flow-steps')->group(function () {
        Route::get('/', [FlowStepController::class, 'index'])->name('api.flow-steps.index');
        Route::post('/', [FlowStepController::class, 'store'])->name('api.flow-steps.store');
        Route::get('/{id}', [FlowStepController::class, 'show'])->name('api.flow-steps.show');
        Route::put('/{id}', [FlowStepController::class, 'update'])->name('api.flow-steps.update');
        Route::delete('/{id}', [FlowStepController::class, 'destroy'])->name('api.flow-steps.destroy');

        // Rotas de controle de versão
        Route::get('/{id}/history', [FlowStepController::class, 'getVersionHistory'])->name('api.flow-steps.history');
        Route::get('/{id}/history/{versionId}', [FlowStepController::class, 'getVersion'])->name('api.flow-steps.version');
        Route::post('/{id}/history/compare', [FlowStepController::class, 'compareVersions'])->name('api.flow-steps.compare');
        Route::post('/{id}/history/{versionId}/restore', [FlowStepController::class, 'restoreVersion'])->name('api.flow-steps.restore');
        Route::get('/{id}/stats', [FlowStepController::class, 'getVersionStats'])->name('api.flow-steps.stats');
    });
    Route::put('/flow-steps-reorder', [FlowStepController::class, 'reorder'])->name('api.flow-steps.reorder');

    // Rotas para gestão completa de flow tags
    Route::prefix('flow-tags')->group(function () {
        Route::get('/', [FlowTagController::class, 'index'])->name('api.flow-tags.index');
        Route::post('/', [FlowTagController::class, 'store'])->name('api.flow-tags.store');
        Route::get('/{id}', [FlowTagController::class, 'show'])->name('api.flow-tags.show');
        Route::put('/{id}', [FlowTagController::class, 'update'])->name('api.flow-tags.update');
        Route::delete('/{id}', [FlowTagController::class, 'destroy'])->name('api.flow-tags.destroy');
        Route::get('/step/{stepId}', [FlowTagController::class, 'getByStepId'])->name('api.flow-tags.step');
    });

    // Rotas para associação de tags com steps
    Route::post('/flow-steps/{stepId}/tags/{tagId}', [FlowTagController::class, 'attachToStep'])->name('api.flow-steps.attach-tag');
    Route::delete('/flow-steps/{stepId}/tags/{tagId}', [FlowTagController::class, 'detachFromStep'])->name('api.flow-steps.detach-tag');
    Route::put('/flow-steps/{stepId}/tags', [FlowTagController::class, 'updateStepTags'])->name('api.flow-steps.update-tags');

    // Rotas para gestão de templates de validação de datasets
    Route::prefix('flow-dataset-templates')->group(function () {
        Route::get('/', [FlowDatasetTemplateController::class, 'index'])->name('api.flow-dataset-templates.index');
        Route::post('/', [FlowDatasetTemplateController::class, 'store'])->name('api.flow-dataset-templates.store');
        Route::get('/{id}', [FlowDatasetTemplateController::class, 'show'])->name('api.flow-dataset-templates.show');
        Route::put('/{id}', [FlowDatasetTemplateController::class, 'update'])->name('api.flow-dataset-templates.update');
        Route::delete('/{id}', [FlowDatasetTemplateController::class, 'destroy'])->name('api.flow-dataset-templates.destroy');
    });

    Route::get('external-integrations/test', [ExternalIntegrationController::class, 'testIntegrationJson']);


    // Rotas para gestão de integrações externas
    Route::prefix('external-integrations')->group(function () {
        Route::get('/', [ExternalIntegrationController::class, 'index'])->name('api.external-integrations.index');
        Route::post('/', [ExternalIntegrationController::class, 'store'])->name('api.external-integrations.store');
        Route::get('/{id}', [ExternalIntegrationController::class, 'show'])->name('api.external-integrations.show');
        Route::put('/{id}', [ExternalIntegrationController::class, 'update'])->name('api.external-integrations.update');
        Route::delete('/{id}', [ExternalIntegrationController::class, 'destroy'])->name('api.external-integrations.destroy');


        // Rotas adicionais para funcionalidades específicas
        Route::post('/{id}/test', [ExternalIntegrationController::class, 'test'])->name('api.external-integrations.test');
        Route::post('/{id}/execute', [ExternalIntegrationController::class, 'execute'])->name('api.external-integrations.execute');
        Route::post('/{id}/debug', [ExternalIntegrationController::class, 'debug'])->name('api.external-integrations.debug');
        Route::post('/{id}/toggle-active', [ExternalIntegrationController::class, 'toggleActive'])->name('api.external-integrations.toggle-active');
    });

    // Rotas de atividades e notificações
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications');
});
