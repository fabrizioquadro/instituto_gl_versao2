<?php

namespace App\Services;

use App\Models\Configuracao;
use App\Models\Paciente;
use App\Models\Sincronizacao;
use Illuminate\Support\Facades\DB;

class PacienteSincronizacaoService
{
    protected FeegowService $feegow;

    public function __construct(FeegowService $feegow)
    {
        $this->feegow = $feegow;
    }

    /**
     * Sincroniza os pacientes com a Feegow (incremental, upsert por paciente_id_feegow).
     * Retorna ['criados', 'atualizados', 'erros'].
     */
    public function sincronizar(): array
    {
        $sinc = Sincronizacao::create([
            'tipo' => 'pacientes-feegow',
            'data_inicio' => now(),
            'status' => 'rodando',
        ]);

        $criados = 0;
        $atualizados = 0;
        $erros = 0;
        $errosDetalhes = [];

        $config = Configuracao::find(1);
        $ultima = $config ? $config->ultima_atualizacao_pacientes : null;

        try {
            $pacientesLista = $this->feegow->pacientesDesde($ultima);

            foreach ($pacientesLista as $linha) {
                try {
                    $detalhe = $this->feegow->detalhePaciente($linha['paciente_id']);
                    if (! $detalhe) {
                        $erros++;
                        continue;
                    }

                    $dados = $this->montarDados($detalhe);
                    $dados['sincronizado_em'] = now();

                    $paciente = Paciente::where('paciente_id_feegow', $dados['paciente_id_feegow'])->first();

                    if ($paciente) {
                        $paciente->update($dados);
                        $atualizados++;
                    } else {
                        $dados['ativo'] = true;
                        Paciente::create($dados);
                        $criados++;
                    }
                } catch (\Throwable $e) {
                    $erros++;
                    if (count($errosDetalhes) < 20) {
                        $errosDetalhes[] = "Paciente {$linha['paciente_id']}: ".$e->getMessage();
                    }
                }
            }

            if ($config) {
                $config->update(['ultima_atualizacao_pacientes' => date('Y-m-d')]);
            }

            $sinc->update([
                'data_fim' => now(),
                'status' => 'sucesso',
                'criados' => $criados,
                'atualizados' => $atualizados,
                'erros' => $erros,
                'detalhes' => $errosDetalhes ? implode("\n", $errosDetalhes) : null,
            ]);

            return ['criados' => $criados, 'atualizados' => $atualizados, 'erros' => $erros];
        } catch (\Throwable $e) {
            $sinc->update([
                'data_fim' => now(),
                'status' => 'erro',
                'erros' => $erros + 1,
                'detalhes' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Monta os dados locais a partir do detalhe retornado pela Feegow.
     */
    private function montarDados(array $p): array
    {
        $nome = ! empty($p['nome']) ? $p['nome'] : ($p['nome_social'] ?? '');
        $dtNascimento = null;
        if (! empty($p['nascimento'])) {
            $dtNascimento = $this->formatarData((string) $p['nascimento']);
        }

        $telefones = null;
        if (! empty($p['telefones']) || ! empty($p['celulares'])) {
            $telefones = trim(
                ($p['telefones'][0] ?? '').' '.($p['telefones'][1] ?? '').
                ' '.($p['celulares'][0] ?? '').' '.($p['celulares'][1] ?? '')
            ) ?: null;
        }

        $email = null;
        if (! empty($p['email'])) {
            $email = trim(($p['email'][0] ?? '').' '.($p['email'][1] ?? '')) ?: null;
        }

        return [
            'paciente_id_feegow' => $p['id'] ?? null,
            'nm_paciente' => $nome,
            'dt_nascimento' => $dtNascimento,
            'cpf' => isset($p['documentos']['cpf']) ? preg_replace('/\D/', '', (string) $p['documentos']['cpf']) : null,
            'endereco' => $p['endereco'] ?? null,
            'numero' => $p['numero'] ?? null,
            'complemento' => $p['complemento'] ?? null,
            'bairro' => $p['bairro'] ?? null,
            'cidade' => $p['cidade'] ?? null,
            'estado' => $p['estado'] ?? null,
            'cep' => $p['cep'] ?? null,
            'telefone' => $telefones,
            'email' => $email,
        ];
    }

    /**
     * Converte data da Feegow (dd/mm/aaaa ou dd-mm-aaaa) para Y-m-d.
     * Mantém Y-m-d se já estiver nesse formato.
     */
    private function formatarData(string $data): ?string
    {
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $data, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return $data;
        }

        return null;
    }
}
