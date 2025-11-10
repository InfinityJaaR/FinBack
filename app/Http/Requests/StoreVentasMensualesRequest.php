<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentasMensualesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Se controla por middleware de permisos en las rutas
    }

    public function rules(): array
    {
        return [
            'ventas' => ['required','array','min:1'],
            'ventas.*.anio' => ['required','integer','digits:4','min:1900','max:2100'],
            'ventas.*.mes' => ['required','integer','min:1','max:12'],
            'ventas.*.monto' => ['required','numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'ventas.*.anio.required' => 'El año es obligatorio.',
            'ventas.*.mes.required' => 'El mes es obligatorio.',
        ];
    }
}
