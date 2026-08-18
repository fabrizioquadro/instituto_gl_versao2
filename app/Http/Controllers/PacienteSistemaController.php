<?php

namespace App\Http\Controllers;

use App\Models\Configuracao;
use App\Models\Paciente;
use App\Models\Sincronizacao;
use App\Services\PacienteSincronizacaoService;
use Illuminate\Http\Request;

class PacienteSistemaController extends Controller
{
    /**
     * Lista os pacientes ativos. A tabela é renderizada via DataTables server-side
     * (rota pacientes.dados).
     */
    public function index(Request $request)
    {
        $totalPacientes = Paciente::ativos()->count();

        $ultimasSinc = Sincronizacao::where('tipo', 'pacientes-feegow')->orderByDesc('id')->limit(5)->get();
        $ultimaAtualizacao = Configuracao::find(1)?->ultima_atualizacao_pacientes;

        return view('pacientes.index', compact('totalPacientes', 'ultimasSinc', 'ultimaAtualizacao'));
    }

    /**
     * Endpoint server-side do DataTables (busca, ordenação e paginação).
     */
    public function datatable(Request $request)
    {
        $colunas = ['nm_paciente', 'cpf', 'dt_nascimento', 'telefone', 'paciente_id_feegow'];

        $query = Paciente::ativos();
        $recordsTotal = (clone $query)->count();

        if ($request->filled('search.value')) {
            $busca = trim($request->input('search.value'));
            $query->where(function ($q) use ($busca) {
                $q->where('nm_paciente', 'like', '%'.$busca.'%')
                    ->orWhere('cpf', 'like', '%'.$busca.'%')
                    ->orWhere('paciente_id_feegow', 'like', '%'.$busca.'%')
                    ->orWhere('telefone', 'like', '%'.$busca.'%');
            });
        }
        $recordsFiltered = (clone $query)->count();

        $indiceColuna = (int) $request->input('order.0.column', 0);
        $direcao = strtolower($request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (isset($colunas[$indiceColuna])) {
            $query->orderBy($colunas[$indiceColuna], $direcao);
        } else {
            $query->orderBy('nm_paciente', 'asc');
        }

        $inicio = (int) $request->input('start', 0);
        $tamanho = (int) $request->input('length', 25);
        if ($tamanho < 0) {
            $tamanho = $recordsFiltered ?: 25;
        }

        $pacientes = $query->offset($inicio)->limit($tamanho)->get();

        $data = $pacientes->map(function ($p) {
            return [
                $p->nm_paciente,
                $p->cpf ?: '-',
                $p->dt_nascimento ? dataDbForm($p->dt_nascimento) : '-',
                $p->telefone ?: '-',
                (string) $p->paciente_id_feegow,
                '<div class="d-flex align-items-center gap-1">'
                    .'<a href="'.route('pacientes.show', $p->id).'" class="btn btn-sm btn-icon btn-outline-primary" title="Visualizar">'
                    .'<i class="ri-eye-line"></i></a>'
                    .'<a href="'.route('procedimentos.index', ['paciente_id' => $p->id]).'" class="btn btn-sm btn-icon btn-outline-info" title="Listar procedimentos (prescrições)">'
                    .'<i class="ri-file-list-3-line"></i></a>'
                .'</div>',
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Exibe um paciente (dados + obs local).
     */
    public function show($id)
    {
        $paciente = Paciente::ativos()->findOrFail($id);

        return view('pacientes.show', compact('paciente'));
    }

    /**
     * Atualiza a observação local do paciente.
     */
    public function salvarObs(Request $request, $id)
    {
        $paciente = Paciente::ativos()->findOrFail($id);

        $request->validate([
            'obs' => 'nullable|string|max:5000',
        ]);

        $paciente->update(['obs' => $request->obs ?: null]);

        return redirect()->back()->with('mensagem', 'Observação atualizada com sucesso.');
    }

    /**
     * Retorna a observação do paciente (usado no cadastro de procedimentos
     * para exibir em destaque quando houver obs).
     */
    public function getObs($id)
    {
        $paciente = Paciente::ativos()->find($id);

        return response()->json([
            'id' => (int) $id,
            'obs' => $paciente?->obs ?: null,
        ]);
    }

    /**
     * Dispara a sincronização com a Feegow (somente admin).
     */
    public function atualizarIntegracao()
    {
        set_time_limit(0);

        try {
            $resultado = app(PacienteSincronizacaoService::class)->sincronizar();

            return redirect()->route('pacientes.index')->with(
                'mensagem',
                "Pacientes atualizados da Feegow: {$resultado['criados']} criados, {$resultado['atualizados']} atualizados, {$resultado['erros']} erros."
            );
        } catch (\Exception $e) {
            return redirect()->route('pacientes.index')->with('mensagem_erro', 'Erro na sincronização: '.$e->getMessage());
        }
    }

    /**
     * Busca de pacientes (Select2) para uso em outros módulos.
     */
    public function listarAjax(Request $request)
    {
        $termo = $request->q ?? '';

        $pacientes = Paciente::ativos()
            ->where('nm_paciente', 'like', '%'.$termo.'%')
            ->orderBy('nm_paciente')
            ->limit(20)
            ->get(['id', 'nm_paciente', 'cpf', 'dt_nascimento']);

        return response()->json(
            $pacientes->map(fn ($p) => [
                'id' => $p->id,
                'text' => trim($p->nm_paciente.' - '.($p->cpf ?: '').' - '.($p->dt_nascimento ? dataDbForm($p->dt_nascimento) : '')),
            ])
        );
    }
}
