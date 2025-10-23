<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpresaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Asumiendo que se requiere autenticación y un permiso específico
        return $this->user()->can('gestionar_empresas');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Capturamos el ID de la empresa de las rutas
        $empresaId = $this->route('empresa'); 

        return [
            // rubro_id: Requerido y debe existir en la tabla rubros
            'rubro_id' => ['required', 'integer', 'exists:rubros,id'],
            
            // codigo: Requerido, máximo 20 caracteres. Debe ser único, ignorando
            // el registro actual de la empresa que se está actualizando.
            'codigo' => [
                'required', 
                'string', 
                'max:20', 
                Rule::unique('empresas', 'codigo')->ignore($empresaId)
            ],
            
            // nombre: Requerido, máx 150 caracteres
            'nombre' => ['required', 'string', 'max:150'],
            
            // descripcion: Opcional
            'descripcion' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'rubro_id.exists' => 'El rubro seleccionado no es válido.',
            'codigo.unique' => 'Ya existe otra empresa con este código.',
        ];
    }
}
