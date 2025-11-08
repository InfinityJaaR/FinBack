<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BenchmarkRubroRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;

        // Cualquier rol con el permiso ver_ratios
        return $user->roles()
            ->with('permisos')
            ->get()
            ->pluck('permisos')
            ->flatten()
            ->pluck('name')
            ->contains('ver_ratios');
    }

    public function rules(): array
    {
        return [
            'rubro_id'   => ['required', 'integer', 'exists:rubros,id'],
            'ratio_id'   => ['required', 'integer', 'exists:ratios_definiciones,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
        ];
    }
}
