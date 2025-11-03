<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCatalogoCuentaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja por middleware de permisos
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'empresa_id' => 'required|exists:empresas,id',
            'cuentas' => 'required|array|min:1',
            'cuentas.*.codigo' => 'required|string|max:50',
            'cuentas.*.nombre' => 'required|string|max:150',
            'cuentas.*.tipo' => [
                'required',
                Rule::in(['ACTIVO', 'PASIVO', 'PATRIMONIO', 'INGRESO', 'GASTO'])
            ],
            'cuentas.*.es_calculada' => 'sometimes|boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'empresa_id.required' => 'El ID de la empresa es obligatorio',
            'empresa_id.exists' => 'La empresa especificada no existe',
            'cuentas.required' => 'Debe proporcionar al menos una cuenta',
            'cuentas.min' => 'Debe proporcionar al menos una cuenta',
            'cuentas.*.codigo.required' => 'El código de la cuenta es obligatorio',
            'cuentas.*.codigo.max' => 'El código no puede exceder 50 caracteres',
            'cuentas.*.nombre.required' => 'El nombre de la cuenta es obligatorio',
            'cuentas.*.nombre.max' => 'El nombre no puede exceder 150 caracteres',
            'cuentas.*.tipo.required' => 'El tipo de cuenta es obligatorio',
            'cuentas.*.tipo.in' => 'El tipo debe ser: ACTIVO, PASIVO, PATRIMONIO, INGRESO o GASTO',
            'cuentas.*.es_calculada.boolean' => 'El campo es_calculada debe ser verdadero o falso'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'empresa_id' => 'empresa',
            'cuentas' => 'cuentas',
            'cuentas.*.codigo' => 'código',
            'cuentas.*.nombre' => 'nombre',
            'cuentas.*.tipo' => 'tipo',
            'cuentas.*.es_calculada' => 'es calculada'
        ];
    }
}
