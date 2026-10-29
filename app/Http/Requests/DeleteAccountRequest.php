<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Exclusão da própria conta.
 *
 * Login é só via Google (ninguém sabe a senha aleatória), então a confirmação
 * é digitar o próprio e-mail em vez da senha atual.
 */
class DeleteAccountRequest extends FormRequest
{
    /**
     * Mantém o bag que o modal de exclusão (profile/partials/delete-user-form) exibe.
     *
     * @var string
     */
    protected $errorBag = 'userDeletion';

    public function authorize(): bool
    {
        // A conta de demonstração é compartilhada entre visitantes
        return $this->user() !== null && ! $this->user()->isDemo();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email_confirmation' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (strcasecmp(trim((string) $value), $this->user()->email) !== 0) {
                        $fail('O e-mail digitado não confere com o da sua conta.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email_confirmation.required' => 'Digite o seu e-mail para confirmar a exclusão.',
        ];
    }
}
