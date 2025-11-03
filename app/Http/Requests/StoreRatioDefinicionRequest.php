<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRatioDefinicionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo los usuarios con rol 'Administrador' pueden crear definiciones de ratios.
        $user = $this->user();
        if (! $user) return false;
        return $user->roles()->where('name', 'Administrador')->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
    // Valores posibles para el ENUM 'sentido'
    $sentidos = ['MAYOR_MEJOR', 'MENOR_MEJOR', 'CERCANO_A_1'];

    // Categorías de ratios (protector para clasificación en frontend)
    $categorias = ['LIQUIDEZ', 'ENDEUDAMIENTO', 'RENTABILIDAD', 'EFICIENCIA', 'COBERTURA'];

        // Valores posibles para el ENUM 'rol' en la tabla pivote ratio_componentes
        $roles_componente = ['NUMERADOR', 'DENOMINADOR', 'OPERANDO'];

        return [
            // --- Reglas para la Definición del Ratio (Tabla ratios_definiciones) ---
            'codigo' => ['required', 'string', 'max:30', 'unique:ratios_definiciones,codigo'],
            'nombre' => ['required', 'string', 'max:120'],
            'formula' => ['required', 'string'], // Texto visible de la fórmula
            'sentido' => ['required', 'string', Rule::in($sentidos)],
            // NUEVO: categoría del ratio (p.ej. LIQUIDEZ, ENDEUDAMIENTO...)
            'categoria' => ['required', 'string', Rule::in($categorias)],
            // multiplicador opcional (factor aplicado al resultado)
            'multiplicador' => ['sometimes', 'numeric'],
            // bandera de protección (solo admin puede crear, por eso permitimos su envío)
            'is_protected' => ['sometimes', 'boolean'],
            
            // --- Reglas para los Componentes (Tabla pivote ratio_componentes) ---
            'componentes' => ['required', 'array', 'min:2'], // Debe ser un array y tener al menos 2 elementos (Num y Den)
            'componentes.*.concepto_id' => ['required', 'integer', 'exists:conceptos_financieros,id'], // Debe ser un concepto válido
            'componentes.*.rol' => ['required', 'string', Rule::in($roles_componente)], // Debe ser un rol válido
            'componentes.*.orden' => ['required', 'integer', 'min:1'], // La posición en la fórmula
            
            // NUEVO CAMPO: Debe ser un booleano, obligatorio, para indicar si se promedia
            'componentes.*.requiere_promedio' => ['required', 'boolean'], 
        ];
    }

    /**
     * Personaliza los mensajes de error.
     */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe una definición de ratio con este código.',
            'sentido.in' => 'El valor para sentido no es válido. Debe ser MAYOR_MEJOR, MENOR_MEJOR o CERCANO_A_1.',
            'categoria.in' => 'La categoría no es válida. Debe ser LIQUIDEZ, ENDEUDAMIENTO, RENTABILIDAD, EFICIENCIA o COBERTURA.',
            'categoria.required' => 'Debe indicar la categoría del ratio.',
            'multiplicador.numeric' => 'El multiplicador debe ser un número válido.',
            'is_protected.boolean' => 'is_protected debe ser verdadero o falso.',
            
            'componentes.required' => 'La definición de un ratio debe incluir al menos dos componentes (Numerador y Denominador).',
            'componentes.min' => 'La definición de un ratio debe incluir al menos dos componentes (Numerador y Denominador).',
            'componentes.*.concepto_id.exists' => 'Uno de los conceptos financieros seleccionados no es válido.',
            'componentes.*.rol.in' => 'El rol de un componente es inválido. Debe ser NUMERADOR, DENOMINADOR u OPERANDO.',
            'componentes.*.requiere_promedio.required' => 'Debe indicar si el componente requiere ser promediado.',
            'componentes.*.requiere_promedio.boolean' => 'El valor para \'requiere_promedio\' debe ser verdadero o falso.',
        ];
    }
}
