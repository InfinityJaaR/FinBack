<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerRatiosEmpresaRequest extends FormRequest
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
            ->contains('ver_ratios');
    }

    // Copia periodo_id de la query al input antes de validar (GET)
    protected function prepareForValidation(): void
    {
        if ($this->query('periodo_id') !== null && $this->input('periodo_id') === null) {
            $this->merge(['periodo_id' => $this->query('periodo_id')]);
        }
    }

    public function rules(): array
    {
        return [
            'periodo_id' => ['required','integer','exists:periodos,id'],
        ];
    }
}
