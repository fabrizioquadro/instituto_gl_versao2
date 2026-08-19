<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaixaSistemaController;
use App\Http\Controllers\ClinicaAdmController;
use App\Http\Controllers\ComboAdmController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnfermagemController;
use App\Http\Controllers\EntradaSistemaController;
use App\Http\Controllers\EstoqueAjusteController;
use App\Http\Controllers\EstoqueSistemaController;
use App\Http\Controllers\ExtratoController;
use App\Http\Controllers\FornecedorAdmController;
use App\Http\Controllers\GrupoAdmController;
use App\Http\Controllers\MedicamentoAdmController;
use App\Http\Controllers\PacienteSistemaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProcedimentoSistemaController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\SoroAdmController;
use App\Http\Controllers\TransferenciaSistemaController;
use App\Http\Controllers\UsuarioAdmController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Recuperação de senha (padrão Laravel)
Route::get('/password/email', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil');
    Route::post('/perfil', [PerfilController::class, 'update'])->name('perfil.update');

    Route::get('/alterar-senha', [PerfilController::class, 'alterarSenha'])->name('alterar_senha');
    Route::post('/alterar-senha', [PerfilController::class, 'updateSenha'])->name('alterar_senha.update');
});

// Estoque (operacional - autenticado)
Route::middleware('auth')->prefix('estoque')->name('estoque.')->group(function () {
    // Saldo
    Route::get('estoques', [EstoqueSistemaController::class, 'index'])->name('estoques.index');
    Route::get('estoques/codigos', [EstoqueSistemaController::class, 'getCodigosBarras'])->name('estoques.get_codigos_barras');
    Route::get('estoques/lotes', [EstoqueSistemaController::class, 'getLotes'])->name('estoques.get_lotes');
    Route::get('abertos', [EstoqueSistemaController::class, 'abertos'])->name('abertos');

    // Entradas (rotas extras antes do resource para não conflitar)
    Route::get('entradas/gerar-codigo-barras', [EntradaSistemaController::class, 'gerarCodigoBarras'])->name('entradas.gerar_codigo_barras');
    Route::get('entradas/{entrada}/etiquetas', [EntradaSistemaController::class, 'etiquetas'])->name('entradas.etiquetas');
    Route::get('entradas/{entrada}/espelho', [EntradaSistemaController::class, 'espelho'])->name('entradas.espelho');
    Route::resource('entradas', EntradaSistemaController::class);

    // Baixas
    Route::get('baixas/lotes', [BaixaSistemaController::class, 'getLotes'])->name('baixas.get_lotes');
    Route::get('baixas/{baixa}/espelho', [BaixaSistemaController::class, 'espelho'])->name('baixas.espelho');
    Route::resource('baixas', BaixaSistemaController::class);

    // Transferências
    Route::get('transferencias/codigos', [TransferenciaSistemaController::class, 'getCodigosBarras'])->name('transferencias.get_codigos_barras');
    Route::get('transferencias/lotes', [TransferenciaSistemaController::class, 'getLotes'])->name('transferencias.get_lotes');
    Route::get('transferencias/{transferencia}/etiquetas', [TransferenciaSistemaController::class, 'etiquetas'])->name('transferencias.etiquetas');
    Route::get('transferencias/{transferencia}/espelho', [TransferenciaSistemaController::class, 'espelho'])->name('transferencias.espelho');
    Route::resource('transferencias', TransferenciaSistemaController::class);

    // Extrato
    Route::get('extrato', [ExtratoController::class, 'index'])->name('extrato');
    Route::get('extrato/codigos', [ExtratoController::class, 'codigos'])->name('extrato.codigos');
    Route::post('extrato/gerar', [ExtratoController::class, 'gerar'])->name('extrato.gerar');

    // Ajustes de estoque (somente admin)
    Route::resource('ajustes', EstoqueAjusteController::class)->middleware('admin');
});

// Relatórios (autenticado)
Route::middleware('auth')->prefix('relatorios')->name('relatorios.')->group(function () {
    Route::get('/', [RelatorioController::class, 'index'])->name('index');
    Route::get('financeiro', [RelatorioController::class, 'financeiro'])->name('financeiro');
    Route::get('financeiro-simplificado', [RelatorioController::class, 'financeiroSimplificado'])->name('financeiro_simplificado');
    Route::get('vendas', [RelatorioController::class, 'vendas'])->name('vendas');
    Route::get('enfermagem', [RelatorioController::class, 'enfermagem'])->name('enfermagem');
    Route::get('transferencias', [RelatorioController::class, 'transferencias'])->name('transferencias');
    Route::get('baixas', [RelatorioController::class, 'baixas'])->name('baixas');
    Route::get('recepcao', [RelatorioController::class, 'recepcao'])->name('recepcao');
    Route::get('caixa', [RelatorioController::class, 'caixa'])->name('caixa');
    Route::get('estoque', [RelatorioController::class, 'estoque'])->name('estoque');
    Route::get('pacientes', [RelatorioController::class, 'pacientes'])->name('pacientes');
});

// Pacientes (autenticado)
Route::middleware('auth')->prefix('pacientes')->name('pacientes.')->group(function () {
    Route::get('/', [PacienteSistemaController::class, 'index'])->name('index');
    Route::get('dados', [PacienteSistemaController::class, 'datatable'])->name('dados');
    Route::get('buscar/ajax', [PacienteSistemaController::class, 'listarAjax'])->name('buscar');
    Route::post('atualizar', [PacienteSistemaController::class, 'atualizarIntegracao'])->middleware('admin')->name('atualizar');
    Route::put('{paciente}/obs', [PacienteSistemaController::class, 'salvarObs'])->name('obs');
    Route::get('{paciente}/obs', [PacienteSistemaController::class, 'getObs'])->name('obs.get');
    Route::get('{paciente}', [PacienteSistemaController::class, 'show'])->name('show');
});

// Procedimentos (prescrições + financeiro - autenticado)
Route::middleware('auth')->prefix('procedimentos')->name('procedimentos.')->group(function () {
    Route::get('/', [ProcedimentoSistemaController::class, 'index'])->name('index');
    Route::get('dados', [ProcedimentoSistemaController::class, 'datatable'])->name('dados');
    Route::get('medicos', [ProcedimentoSistemaController::class, 'medicos'])->name('medicos');
    Route::get('novo', [ProcedimentoSistemaController::class, 'create'])->name('create');
    Route::post('/', [ProcedimentoSistemaController::class, 'store'])->name('store');

    // Anexos (download/visualizar) - antes do resource {id}
    Route::get('anexos/{anexo}/download', [ProcedimentoSistemaController::class, 'downloadAnexo'])->name('anexos.download');
    Route::get('anexos/{anexo}/visualizar', [ProcedimentoSistemaController::class, 'visualizarAnexo'])->name('anexos.visualizar');

    // Financeiro
    Route::post('{procedimento}/pagamentos', [ProcedimentoSistemaController::class, 'salvarPagamento'])->name('pagamentos.store');
    Route::delete('pagamentos/{pagamento}', [ProcedimentoSistemaController::class, 'excluirPagamento'])->name('pagamentos.destroy');
    Route::post('{procedimento}/parcelas/{parcela}/pagar', [ProcedimentoSistemaController::class, 'pagarParcela'])->name('parcelas.pagar');
    Route::post('{procedimento}/parcelas/{parcela}/atualizar', [ProcedimentoSistemaController::class, 'atualizarParcela'])->name('parcelas.atualizar');
    Route::post('{procedimento}/credito-em-aberto', [ProcedimentoSistemaController::class, 'atualizarCreditoEmAberto'])->name('credito_em_aberto.atualizar');
    Route::post('{procedimento}/pagamento-extra', [ProcedimentoSistemaController::class, 'pagamentoExtra'])->name('pagamento-extra');
    Route::post('{procedimento}/recalcular', [ProcedimentoSistemaController::class, 'recalcularParcelas'])->name('recalcular');

    // Ações
    Route::post('{procedimento}/cancelar', [ProcedimentoSistemaController::class, 'cancelar'])->name('cancelar');
    Route::post('{procedimento}/semanas/adicionar-medicamento', [ProcedimentoSistemaController::class, 'adicionarMedicamentoSemana'])->name('semana.adicionar_medicamento');
    Route::post('{procedimento}/semanas/medicamento/{medicamento}/excluir', [ProcedimentoSistemaController::class, 'excluirMedicamentoSemana'])->name('semana.medicamento.excluir');
    Route::post('{procedimento}/semanas/medicamento/{medicamento}/atualizar', [ProcedimentoSistemaController::class, 'atualizarMedicamentoSemana'])->name('semana.medicamento.atualizar');
    Route::post('{procedimento}/semanas/{semana}/obs', [ProcedimentoSistemaController::class, 'atualizarObsSemana'])->name('semana.obs.atualizar');
    Route::post('{procedimento}/observacoes', [ProcedimentoSistemaController::class, 'adicionarObservacao'])->name('observacoes.store');
    Route::delete('{procedimento}/observacoes/{observacao}', [ProcedimentoSistemaController::class, 'excluirObservacao'])->name('observacoes.destroy');
    Route::get('{procedimento}/semanas/adicionar', [ProcedimentoSistemaController::class, 'adicionarSemanasView'])->name('semana.adicionar');
    Route::post('{procedimento}/semanas/adicionar', [ProcedimentoSistemaController::class, 'adicionarSemanasStore'])->name('semana.adicionar');
    Route::get('{procedimento}/imprimir-detalhes', [ProcedimentoSistemaController::class, 'imprimirDetalhes'])->name('imprimir_detalhes');
    Route::get('{procedimento}/imprimir-detalhes-pdf', [ProcedimentoSistemaController::class, 'imprimirDetalhesPdf'])->name('imprimir_detalhes_pdf');

    Route::get('{procedimento}', [ProcedimentoSistemaController::class, 'show'])->name('show');
});

// Enfermagem (Fila de Atendimento + aplicação/bipagem - autenticado)
Route::middleware('auth')->prefix('enfermagem')->name('enfermagem.')->group(function () {
    Route::get('/', [EnfermagemController::class, 'index'])->name('index');

    // Enviar para a fila (com/sem pagamento)
    Route::post('fila/{semana}/enviar', [EnfermagemController::class, 'enviarFila'])->name('fila.enviar');
    Route::post('fila/enviar-sem-pagamento', [EnfermagemController::class, 'enviarFilaSemPagamento'])->name('fila.enviar_sem_pagamento');

    // Aplicação / bipagem
    Route::get('aplicacao/buscar-lote', [EnfermagemController::class, 'buscarLote'])->name('aplicacao.buscar_lote');
    Route::get('aplicacao/buscar-frasco', [EnfermagemController::class, 'buscarFrasco'])->name('aplicacao.buscar_frasco');
    Route::get('aplicacao/lotes-medicamento', [EnfermagemController::class, 'getLotesMedicamento'])->name('aplicacao.lotes_medicamento');
    Route::post('aplicacao/abrir-frasco', [EnfermagemController::class, 'abrirFrasco'])->name('aplicacao.abrir_frasco');
    Route::post('aplicacao/{semana}/lancar', [EnfermagemController::class, 'lancar'])->name('aplicacao.lancar');
    Route::get('aplicacao/keep-alive', [EnfermagemController::class, 'keepAlive'])->name('aplicacao.keep_alive');
    Route::get('aplicacao/{semana}', [EnfermagemController::class, 'aplicacao'])->name('aplicacao');
});

// Configurações (somente admin)
Route::middleware(['auth', 'admin'])->prefix('configuracoes')->name('config.')->group(function () {
    Route::resource('clinicas', ClinicaAdmController::class);
    Route::resource('usuarios', UsuarioAdmController::class);

    Route::resource('grupos', GrupoAdmController::class);
    Route::resource('medicamentos', MedicamentoAdmController::class);

    Route::resource('combos', ComboAdmController::class);
    Route::get('combos/{combo}/buscar', [ComboAdmController::class, 'buscar'])->name('combos.buscar');
    Route::delete('combos/medicamento/{comboMedicamento}', [ComboAdmController::class, 'deleteMedicamento'])->name('combos.delete_medicamento');

    Route::resource('soros', SoroAdmController::class);
    Route::get('soros/{soro}/buscar', [SoroAdmController::class, 'buscar'])->name('soros.buscar');
    Route::delete('soros/medicamento/{soroMedicamento}', [SoroAdmController::class, 'deleteMedicamento'])->name('soros.delete_medicamento');

    Route::resource('fornecedores', FornecedorAdmController::class);
});
