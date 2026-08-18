<?php

namespace App\Services;

use GuzzleHttp\Client;
use RuntimeException;

class FeegowService
{
    protected Client $client;
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('feegow.base_url'), '/');
        $this->token = (string) config('feegow.token');
        $this->client = new Client([
            'timeout' => 120,
            'connect_timeout' => 30,
        ]);
    }

    /**
     * Chamada GET à API Feegow.
     */
    private function get(string $endpoint, array $query = [], ?int $timeout = null): array
    {
        $url = $this->baseUrl.'/'.$endpoint;
        if ($query) {
            $url .= '?'.http_build_query($query);
        }

        $client = $this->client;
        if ($timeout !== null) {
            $client = new Client([
                'timeout' => $timeout,
                'connect_timeout' => min($timeout, 10),
            ]);
        }

        try {
            $res = $client->get($url, [
                'headers' => [
                    'x-access-token' => $this->token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode((string) $res->getBody(), true) ?: [];
        } catch (\Throwable $e) {
            throw new RuntimeException('Erro na API Feegow: '.$e->getMessage());
        }
    }

    /**
     * Lista pacientes paginada; opcionalmente apenas os alterados desde $data (Y-m-d).
     */
    public function pacientesDesde(?string $data): array
    {
        $pacientes = [];
        $offset = 0;
        $limit = 5000;

        while (true) {
            $params = ['limit' => $limit, 'offset' => $offset];
            if ($data) {
                $params['alterado_em'] = $data;
            }

            $retorno = $this->get('patient/list', $params);
            $content = $retorno['content'] ?? [];

            if (empty($content)) {
                break;
            }

            foreach ($content as $p) {
                $pacientes[] = [
                    'paciente_id' => $p['patient_id'] ?? null,
                    'paciente_nome' => $p['nome'] ?? '',
                    'nascimento' => $p['nascimento'] ?? null,
                ];
            }

            $offset += $limit;
        }

        return $pacientes;
    }

    /**
     * Detalhe completo de um paciente (sem foto).
     */
    public function detalhePaciente($pacienteId): ?array
    {
        $retorno = $this->get('patient/search', ['paciente_id' => $pacienteId]);

        if (! isset($retorno['success']) || $retorno['success'] !== true) {
            return null;
        }

        return $retorno['content'] ?? null;
    }

    /**
     * Lista os profissionais (médicos) da Feegow, ordenados por nome.
     * Mesma origem usada na V1 (professional/list).
     */
    public function medicos(): array
    {
        // Timeout 20s p/ aguentar a Feegow lenta (~12s) sem travar a página
        // (a chamada é via AJAX; a página já renderizou).
        $retorno = $this->get('professional/list', [], 20);

        $medicos = [];
        foreach ($retorno['content'] ?? [] as $prof) {
            $medicos[] = [
                'profissional_id' => $prof['profissional_id'] ?? null,
                'profissional_nome' => trim((string) ($prof['nome'] ?? '')),
            ];
        }

        $medicos = array_values(array_filter($medicos, fn ($m) => $m['profissional_nome'] !== ''));
        usort($medicos, fn ($a, $b) => strcasecmp($a['profissional_nome'], $b['profissional_nome']));

        return $medicos;
    }

    /**
     * Registra um agendamento na Feegow (backup da aplicação/medicações) e
     * atualiza o status. Replica o comportamento da V1
     * (appoints/new-appoint + appoints/statusUpdate).
     */
    public function novoAgendamento(array $params): ?int
    {
        // Prefixo configurável (FEEGOW_OBS_PREFIX) nas notas/obs do agendamento
        $prefixo = trim((string) config('feegow.obs_prefix'));
        if ($prefixo !== '') {
            $notas = trim((string) ($params['notas'] ?? ''));
            $params['notas'] = $notas !== '' ? $prefixo.' — '.$notas : $prefixo;
        }

        $url = $this->baseUrl.'/appoints/new-appoint?'.http_build_query($params);

        try {
            $res = $this->client->post($url, [
                'headers' => [
                    'x-access-token' => $this->token,
                    'Content-Type' => 'application/json',
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true) ?: [];
        } catch (\Throwable $e) {
            throw new RuntimeException('Erro na API Feegow (agendamento): '.$e->getMessage());
        }

        $agendamentoId = $data['content']['agendamento_id'] ?? null;

        if ($agendamentoId) {
            try {
                $this->client->post($this->baseUrl.'/appoints/statusUpdate?'.http_build_query([
                    'AgendamentoID' => $agendamentoId,
                    'StatusID' => '3',
                    'Obs' => 'Informação enviada pelo sistema',
                ]), [
                    'headers' => [
                        'x-access-token' => $this->token,
                        'Content-Type' => 'application/json',
                    ],
                ]);
            } catch (\Throwable $e) {
                // a atualização de status não é crítica
            }
        }

        return $agendamentoId;
    }
}
