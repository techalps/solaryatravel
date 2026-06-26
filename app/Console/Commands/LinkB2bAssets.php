<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * (Ri)crea i symlink degli asset nella cartella /b2b (secondo document root del
 * canale agenzie). Da eseguire in deploy, come si fa con `php artisan storage:link`
 * per /public.
 *
 * I link puntano agli stessi asset del dominio principale, così un restyle
 * dell'admin/frontend si propaga al B2B senza duplicazioni.
 *
 *   php artisan b2b:link
 */
class LinkB2bAssets extends Command
{
    protected $signature = 'b2b:link {--force : Sovrascrive i link esistenti}';

    protected $description = 'Crea i symlink degli asset nella cartella /b2b (canale agenzie)';

    /**
     * Mappa: link dentro /b2b => target relativo (stessa profondità di /public).
     */
    private const LINKS = [
        'build' => '../public/build',
        'assets' => '../public/assets',
        'fonts' => '../public/fonts',
        'images' => '../public/images',
        'storage' => '../storage/app/public',
    ];

    public function handle(): int
    {
        $b2b = base_path('b2b');

        if (! is_dir($b2b)) {
            $this->error("Cartella b2b/ non trovata in {$b2b}. Crearla prima del deploy.");
            return self::FAILURE;
        }

        foreach (self::LINKS as $name => $target) {
            $link = $b2b.DIRECTORY_SEPARATOR.$name;

            if (is_link($link) || file_exists($link)) {
                if (! $this->option('force')) {
                    $this->line("• {$name} già presente (usa --force per ricreare)");
                    continue;
                }
                @unlink($link);
            }

            symlink($target, $link);
            $this->info("✓ b2b/{$name} → {$target}");
        }

        $this->newLine();
        $this->info('Symlink B2B pronti. Punta il document root del sottodominio su b2b/.');

        return self::SUCCESS;
    }
}
