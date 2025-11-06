<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RubroStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * En un proyecto real, se verifica el rol (ej: 'Administrador').
     * Por ahora, lo mantenemos en true.
     */
    public function authorize(): bool
    {
        return $this->user()->can('gestionar_rubros');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Reglas de validación basadas en los campos del modelo Rubro
        return [
            // Campos obligatorios y únicos
            'codigo' => [
                'required',
                'string',
                'max:10',
                // Asegura que el código no exista ya en la tabla 'rubros'
                Rule::unique('rubros', 'codigo'),
            ],
            'nombre' => 'required|string|max:100',
            
            // Campo opcional
            'descripcion' => 'nullable|string',

            // Los promedios ya no forman parte del payload de Rubro. Si se desea
            // crear benchmarks para un rubro, use el endpoint de benchmarks o el
            // proceso administrativo correspondiente que persista en `benchmarks_rubro`.
        ];
    }
    
    /**
     * Personaliza los mensajes de error.
     * * @return array
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El campo código es obligatorio.',
            'codigo.unique' => 'Este código de rubro ya existe en el sistema.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'max' => 'El campo :attribute no debe exceder los :max caracteres.',
            'numeric' => 'El campo :attribute debe ser un valor numérico válido.',
            'decimal' => 'El campo :attribute debe tener hasta 2 decimales.',
        ];
    }
}
