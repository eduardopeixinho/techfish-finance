<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as GoogleUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Login exclusivo via Google OAuth (não há cadastro/login por senha).
 */
class AuthController extends Controller
{
    /**
     * Redireciona para o Google
     */
    public function redirectToGoogle(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! config('services.google.enabled')) {
            return redirect()->route('login')->with('error', 'O login com Google não está configurado nesta instalação.');
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Manipula o retorno do Google
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error('Erro no Google OAuth: '.$e->getMessage());

            return redirect('/login')->with('error', 'Falha ao autenticar com o Google. Tente novamente.');
        }

        // Valida o domínio ANTES de criar/logar: e-mail fora da lista não deixa rastro no banco.
        if (! self::isAllowedEmail((string) $googleUser->getEmail())) {
            Log::warning('Tentativa de acesso negada para domínio: '.Str::after((string) $googleUser->getEmail(), '@'));

            return redirect('/login')->with('error', 'Acesso permitido apenas para e-mails autorizados.');
        }

        $user = $this->findOrCreateUser($googleUser);

        if ($user->isDeactivated()) {
            Log::warning('Login recusado: conta desativada (usuário '.$user->id.').');

            return redirect('/login')->with('error', 'Sua conta foi desativada. Fale com o administrador.');
        }

        Auth::login($user, true);
        // Evita fixação de sessão (Auth::login não regenera o id da sessão sozinho).
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Verifica o e-mail contra `services.google.allowed_domains` (vazio = todos).
     */
    public static function isAllowedEmail(string $email): bool
    {
        $allowedDomains = config('services.google.allowed_domains', []);

        if (empty($allowedDomains)) {
            return true;
        }

        return in_array(strtolower(Str::after($email, '@')), $allowedDomains, true);
    }

    /**
     * Encontra o usuário pelo google_id (ou pelo e-mail, no primeiro vínculo) e sincroniza o perfil.
     *
     * No primeiro vínculo de uma conta que já existia (ex.: criada pelo antigo cadastro
     * por senha), a senha e o remember_token são trocados: quem tivesse criado a conta
     * com o e-mail de outra pessoa perde o acesso por senha/"lembrar de mim".
     */
    private function findOrCreateUser(GoogleUser $googleUser): User
    {
        $profile = [
            'google_id' => $googleUser->getId(),
            'nickname' => $googleUser->getNickname() ?? $googleUser->getName(),
            'name' => $googleUser->getName(),
            'avatar' => $googleUser->getAvatar(),
        ];

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $user = new User(array_merge($profile, [
                'email' => $googleUser->getEmail(),
                'password' => Str::random(40),
            ]));
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        }

        $isFirstGoogleLink = $user->google_id !== $googleUser->getId();

        $user->fill($profile);

        if ($isFirstGoogleLink) {
            $user->password = Str::random(40);
            $user->setRememberToken(Str::random(60));
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()]);
        }

        $user->save();

        return $user;
    }
}
