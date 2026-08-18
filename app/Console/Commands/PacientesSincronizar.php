<?php

namespace App\Console\Commands;

use App\Services\PacienteSincronizacaoService;
use Illuminate\Console\Command;

class PacientesSincronizar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pacientes:sincronizar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza os pacientes com a Feegow (incremental, upsert por paciente_id_feegow)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        set_time_limit(0);

        $this->info('Sincronizando pacientes com a Feegow...');

        try {
            $resultado = app(PacienteSincronizacaoService::class)->sincronizar();
            $this->info("Concluído: {$resultado['criados']} criados, {$resultado['atualizados']} atualizados, {$resultado['erros']} erros.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
