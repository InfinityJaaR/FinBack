<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerarProyeccionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Ajusta o utiliza middleware para permisos más finos.
     */
    public function authorize()
    {
        return true; // la autorización se puede aplicar vía middleware (permiso:calcular_proyecciones)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'metodo_usado' => ['required', 'string', 'in:minimos_cuadrados,incremento_porcentual,incremento_absoluto'],
            'periodo_proyectado' => ['required', 'integer', 'digits:4'],
            'base_periodo_inicio' => ['nullable', 'date'],
            'base_periodo_fin' => ['nullable', 'date'],
            'options' => ['nullable', 'array'],
        ];
    }
}
