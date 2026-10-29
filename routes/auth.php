<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de autenticação
|--------------------------------------------------------------------------
|
| Login é EXCLUSIVAMENTE via Google (AuthController, rotas em web.php).
| Cadastro, login por senha, recuperação/troca de senha e confirmação de
| senha do Breeze foram removidos de propósito: o cadastro aberto permitia
| que qualquer pessoa criasse conta (fora do GOOGLE_ALLOWED_DOMAINS) e
| "pré-sequestrasse" o e-mail de alguém que ainda entraria pelo Google.
|
| `GET /login` continua existindo: é a tela com o botão do Google e o
| destino padrão do middleware `auth`.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
