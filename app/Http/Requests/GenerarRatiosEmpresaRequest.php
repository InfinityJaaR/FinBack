<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerarRatiosEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;

        // Debe tener el permiso calcular_ratios
        return $user->roles()
            ->with('permisos')
            ->get()
            ->pluck('permisos')
            ->flatten()
            ->pluck('name')
            ->contains('calcular_ratios');
    }

    // (Opcional) por si alguna vez te llega periodo_id por query en lugar de body
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
