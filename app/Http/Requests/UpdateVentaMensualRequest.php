<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVentaMensualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Se controla por middleware de permisos en las rutas
    }

    public function rules(): array
    {
        return [
            'anio' => ['nullable','integer','digits:4','min:1900','max:2100'],
            'mes' => ['nullable','integer','min:1','max:12'],
            'monto' => ['nullable','numeric'],
        ];
    }
}
