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
        // Solo los usuarios con rol 'Administrador' pueden actualizar definiciones de ratios.
        $user = $this->user();
        if (! $user) return false;
        return $user->roles()->where('name', 'Administrador')->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Capturamos el ID de la RatioDefinicion de las rutas para ignorarlo en la validación 'unique'
        // NOTA: $this->route('ratio_definicion') resuelve el modelo gracias al Route Model Binding
        $ratioDefinicionId = $this->route('ratio_definicion')->id; 

        // Categorías de ratios
    $categorias = ['LIQUIDEZ', 'ENDEUDAMIENTO', 'RENTABILIDAD', 'EFICIENCIA', 'COBERTURA'];

        // Valores posibles para el ENUM 'rol' en la tabla pivote ratio_componentes
        $roles_componente = ['NUMERADOR', 'DENOMINADOR', 'OPERANDO'];


        return [
            // --- Reglas para la Definición del Ratio (Tabla ratios_definiciones) ---
            // codigo: Debe ser único, ignorando el registro actual.
            'codigo' => [
                'required', 
                'string', 
                'max:30', 
                Rule::unique('ratios_definiciones', 'codigo')->ignore($ratioDefinicionId)
            ],
            'nombre' => ['required', 'string', 'max:120'],
            'formula' => ['required', 'string'], // Texto visible de la fórmula
            // categoría del ratio
            'categoria' => ['required', 'string', Rule::in($categorias)],
            // multiplicadores opcionales
            'multiplicador_numerador' => ['sometimes', 'numeric'],
            'multiplicador_denominador' => ['sometimes', 'numeric'],
            'multiplicador_resultado' => ['sometimes', 'numeric'],
            
            // --- Reglas para los Componentes (Tabla pivote ratio_componentes) ---
            'componentes' => ['required', 'array', 'min:2'], // Debe ser un array y tener al menos 2 elementos
            'componentes.*.concepto_id' => ['required', 'integer', 'exists:conceptos_financieros,id'], // Debe ser un concepto válido
            'componentes.*.rol' => ['required', 'string', Rule::in($roles_componente)], // Debe ser un rol válido
            'componentes.*.orden' => ['required', 'integer', 'min:1'], // La posición en la fórmula
            'componentes.*.operacion' => ['required', 'string', Rule::in(['ADD','SUB','MUL','DIV'])],
            'componentes.*.factor' => ['sometimes', 'numeric'],
            // Debe ser un booleano, obligatorio, para indicar si se promedia
            'componentes.*.requiere_promedio' => ['required', 'boolean'], 
        ];
    }
    
    /**
     * Personaliza los mensajes de error.
     */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe otra definición de ratio con este código.',
            'categoria.in' => 'La categoría no es válida. Debe ser LIQUIDEZ, ENDEUDAMIENTO, RENTABILIDAD, EFICIENCIA o COBERTURA.',
            'categoria.required' => 'Debe indicar la categoría del ratio.',
            'multiplicador_numerador.numeric' => 'El multiplicador del numerador debe ser un número válido.',
            'multiplicador_denominador.numeric' => 'El multiplicador del denominador debe ser un número válido.',
            'multiplicador_resultado.numeric' => 'El multiplicador del resultado debe ser un número válido.',
            
            'componentes.required' => 'La definición de un ratio debe incluir al menos dos componentes (Numerador y Denominador).',
            'componentes.min' => 'La definición de un ratio debe incluir al menos dos componentes (Numerador y Denominador).',
            'componentes.*.concepto_id.exists' => 'Uno de los conceptos financieros seleccionados no es válido.',
            'componentes.*.rol.in' => 'El rol de un componente es inválido. Debe ser NUMERADOR, DENOMINADOR u OPERANDO.',
            'componentes.*.operacion.in' => 'La operación de un componente es inválida. Debe ser ADD, SUB, MUL o DIV.',
            'componentes.*.factor.numeric' => 'El factor de un componente debe ser un número válido.',
            'componentes.*.requiere_promedio.required' => 'Debe indicar si el componente requiere ser promediado.',
            'componentes.*.requiere_promedio.boolean' => 'El valor para \'requiere_promedio\' debe ser verdadero o falso.',
        ];
    }
}
