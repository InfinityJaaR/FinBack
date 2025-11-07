<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerMapeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;

        return $user->roles()
            ->with('permisos')
            ->get()
            ->pluck('permisos')
            ->flatten()
            ->pluck('name')
            ->contains('ver_empresas'); // o el permiso que uses para ver
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('empresa') && !$this->input('empresa_id')) {
            $this->merge(['empresa_id' => $this->route('empresa')]);
        }
    }

    public function rules(): array
    {
        return [
            'empresa_id' => ['required','integer','exists:empresas,id'],
        ];
    }
}
