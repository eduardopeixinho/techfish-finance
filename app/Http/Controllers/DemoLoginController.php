<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Entra na conta de demonstração (DEMO_MODE=true). Com o modo desligado, a rota não existe (404).
 */
class DemoLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $user = User::where('email', config('demo.email'))->first();

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Conta de demonstração não encontrada. Rode: php artisan db:seed --class=DemoSeeder');
        }

        // Desativada (antes da proteção no Admin): avisa em vez de entrar e ser derrubado na hora
        if ($user->isDeactivated()) {
            return redirect()->route('login')
                ->with('error', 'A conta de demonstração está desativada. Reative-a em Admin → Usuários.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('finance.dashboard');
    }
}
