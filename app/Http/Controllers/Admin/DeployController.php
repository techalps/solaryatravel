<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class DeployController extends Controller
{
    private const ALLOWED_COMMANDS = [
        'cache:clear'    => 'Svuota la cache applicazione',
        'config:clear'   => 'Pulisce la cache della configurazione',
        'config:cache'   => 'Ricrea la cache della configurazione',
        'route:clear'    => 'Pulisce la cache delle route',
        'route:cache'    => 'Ricrea la cache delle route',
        'view:clear'     => 'Pulisce la cache delle view Blade',
        'optimize:clear' => 'Cancella tutte le cache (config + route + view + event)',
        'optimize'       => 'Ricrea tutte le cache per la produzione',
    ];

    public function index(): View
    {
        $migrator = app('migrator');
        $files    = $migrator->getMigrationFiles(database_path('migrations'));
        $ran      = $migrator->getRepository()->getRan();

        $migrations = [];
        foreach ($files as $name => $path) {
            $migrations[] = [
                'name' => $name,
                'ran'  => in_array($name, $ran),
            ];
        }

        // Più recenti in cima
        $migrations = array_reverse($migrations);
        $pendingCount = count(array_filter($migrations, fn ($m) => !$m['ran']));

        return view('admin.deploy.index', [
            'migrations'   => $migrations,
            'pendingCount' => $pendingCount,
            'commands'     => self::ALLOWED_COMMANDS,
            'phpVersion'   => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'env'          => app()->environment(),
        ]);
    }

    public function migrate(): RedirectResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
            return back()->with('deploy_success', nl2br(e($output ?: 'Nessuna migrazione pendente.')));
        } catch (\Throwable $e) {
            return back()->with('deploy_error', $e->getMessage());
        }
    }

    public function artisan(Request $request): RedirectResponse
    {
        $cmd = $request->validate([
            'command' => ['required', 'string', 'in:' . implode(',', array_keys(self::ALLOWED_COMMANDS))],
        ])['command'];

        try {
            Artisan::call($cmd);
            $output = trim(Artisan::output());
            return back()->with('deploy_success', nl2br(e($output ?: $cmd . ' completato.')));
        } catch (\Throwable $e) {
            return back()->with('deploy_error', $e->getMessage());
        }
    }
}
