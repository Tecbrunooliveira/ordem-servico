<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateMarkApplied extends Command
{
    protected $signature = 'migrate:mark-applied
                            {--except= : Ignora arquivos cujo nome contém este texto}
                            {--dry-run : Apenas lista o que seria registrado}';

    protected $description = 'Registra migrations como aplicadas sem executá-las (baseline para bancos criados via SQL)';

    public function handle(): int
    {
        $except = (string) $this->option('except');
        $dryRun = (bool) $this->option('dry-run');

        $files = collect(File::glob(database_path('migrations/*.php')))
            ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
            ->sort()
            ->values();

        $applied = DB::table('migrations')->pluck('migration');
        $batch = (int) (DB::table('migrations')->max('batch') ?? 0) + 1;

        $pending = $files->filter(function (string $name) use ($except, $applied) {
            if ($except !== '' && str_contains($name, $except)) {
                return false;
            }

            return ! $applied->contains($name);
        });

        if ($pending->isEmpty()) {
            $this->info('Nenhuma migration pendente para registrar.');

            return self::SUCCESS;
        }

        $this->info('Migrations a registrar (batch '.$batch.'):');

        foreach ($pending as $name) {
            $this->line('  - '.$name);

            if (! $dryRun) {
                DB::table('migrations')->insert([
                    'migration' => $name,
                    'batch' => $batch,
                ]);
            }
        }

        if ($dryRun) {
            $this->warn('Dry-run: nada foi gravado.');
        } else {
            $this->info($pending->count().' migration(s) registrada(s).');
        }

        return self::SUCCESS;
    }
}
