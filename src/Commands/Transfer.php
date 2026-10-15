<?php

namespace NineteenNinetyFour\PromoBar\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use NineteenNinetyFour\PromoBar\Database\BarModel;
use NineteenNinetyFour\PromoBar\ServiceProvider;
use Statamic\Console\RunsInPlease;

/**
 * Copy every bar from one driver to the other, as when moving a site from
 * flat files to the database (or back). Bars keep their IDs, so visitors'
 * dismissals still apply.
 */
class Transfer extends Command
{
    use RunsInPlease;

    protected $signature = 'promo-bar:transfer
        {--from= : file or database}
        {--to= : file or database}
        {--force : Don\'t ask before copying}';

    protected $description = 'Copy promo bars from one storage driver to another';

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        foreach ([$from, $to] as $driver) {
            if (! in_array($driver, ['file', 'database'], true)) {
                $this->components->error('Pass --from and --to as "file" or "database".');

                return self::FAILURE;
            }
        }

        if ($from === $to) {
            $this->components->error('--from and --to are the same driver.');

            return self::FAILURE;
        }

        if (! $this->tableExists()) {
            $this->components->error('The promo bars table doesn\'t exist yet. Publish the migration and run it first:');
            $this->line('  php please vendor:publish --tag=promo-bar-migrations');
            $this->line('  php artisan migrate');

            return self::FAILURE;
        }

        $source = app(ServiceProvider::repositoryFor($from));
        $target = app(ServiceProvider::repositoryFor($to));
        $bars = $source->all();

        if ($bars->isEmpty()) {
            $this->components->info("There are no bars in the {$from} driver.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Copy {$bars->count()} bars from {$from} to {$to}? Bars with the same ID there are replaced.", true)) {
            return self::FAILURE;
        }

        $this->withProgressBar($bars, fn ($bar) => $target->save($bar));
        $this->newLine(2);

        $this->components->info("Copied {$bars->count()} bars to the {$to} driver. Set PROMO_BAR_DRIVER={$to} to use them.");

        return self::SUCCESS;
    }

    private function tableExists(): bool
    {
        $model = new BarModel;

        try {
            return Schema::connection($model->getConnectionName())->hasTable($model->getTable());
        } catch (\Throwable) {
            return false;
        }
    }
}
