<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarV1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrar:v1 {--dry : Simula a execução sem gravar no banco}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa as migrations de dados da V1 para a V2 (pasta database/migracao) em ordem numérica';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pasta = database_path('migracao');

        if (! is_dir($pasta)) {
            $this->error("Pasta de migração não encontrada: {$pasta}");
            return self::FAILURE;
        }

        $arquivos = glob($pasta . '/*.php');
        sort($arquivos);

        if (empty($arquivos)) {
            $this->warn('Nenhum script de migração encontrado.');
            return self::SUCCESS;
        }

        $dryRun = $this->option('dry');
        $total = 0;

        foreach ($arquivos as $arquivo) {
            $nome = basename($arquivo);
            $this->info("== Executando: {$nome} " . ($dryRun ? '(DRY RUN)' : ''));

            $closure = require $arquivo;

            $resultado = $dryRun
                ? $closure(dryRun: true)
                : DB::transaction(fn () => $closure());

            if (is_array($resultado)) {
                foreach ($resultado as $chave => $valor) {
                    $this->line("    - {$chave}: {$valor}");
                }
            }

            $total++;
        }

        $this->newLine();
        $this->info("Migração V1 concluída ({$total} script(s) processado(s)).");
        return self::SUCCESS;
    }
}
