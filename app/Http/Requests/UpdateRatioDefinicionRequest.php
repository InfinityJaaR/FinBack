<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRatioDefinicionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Asumiendo que la gestión de ratios cae bajo la gestión empresarial/financiera
        return $this->user()->can('gestionar_ratios_definicion'); 
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Capturamos el ID de la RatioDefinicion de las rutas
        $ratioDefinicionId = $this->route('ratio_definicion'); 

        // Los valores posibles para el ENUM 'sentido' están en el SQL dump
        $sentidos = ['MAYOR_MEJOR', 'MENOR_MEJOR', 'CERCANO_A_1'];

        return [
            // codigo: Requerido. Debe ser único, ignorando el registro actual.
            'codigo' => [
                'required', 
                'string', 
                'max:30', 
                Rule::unique('ratios_definiciones', 'codigo')->ignore($ratioDefinicionId)
            ],
            
            // nombre: Requerido, máx 120
            'nombre' => ['required', 'string', 'max:120'],
            
            // formula: Requerido, texto
            'formula' => ['required', 'string'],
            
            // sentido: Requerido, debe ser uno de los valores ENUM
            'sentido' => ['required', 'string', Rule::in($sentidos)],
        ];
    }
}