<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RubroUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('gestionar_rubros');return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // NOTA CLAVE: Para acceder al ID del rubro que se está actualizando,
        // usamos $this->route('rubro'), ya que Laravel inyecta el modelo en la ruta.
        $rubroId = $this->route('rubro')->id;

        return [
            // El código es requerido y debe ser único, EXCEPTO para el ID que estamos actualizando.
            'codigo' => [
                'required',
                'string',
                'max:10',
                Rule::unique('rubros', 'codigo')->ignore($rubroId),
            ],
            'nombre' => 'required|string|max:100',
            
            // Campos opcionales.
            'descripcion' => 'nullable|string',
        ];
    }
    
    /**
     * Personaliza los mensajes de error.
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