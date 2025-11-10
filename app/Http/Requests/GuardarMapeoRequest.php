<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class GuardarMapeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;

        // usa el permiso que ya manejas para administración de empresas
        return $user->roles()
            ->with('permisos')
            ->get()
            ->pluck('permisos')
            ->flatten()
            ->pluck('name')
            ->contains('ver_empresas');
    }

    protected function prepareForValidation(): void
    {
        if ($this->query('empresa_id') !== null && $this->input('empresa_id') === null) {
            $this->merge(['empresa_id' => $this->query('empresa_id')]);
        }
    }

    public function rules(): array
    {
        return [
            'empresa_id' => ['required','integer','exists:empresas,id'],
            'mapeos'     => ['required','array'], // { [concepto_id]: catalogo_cuenta_id|null }
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $empresaId = (int) $this->input('empresa_id');
            $mapeos    = $this->input('mapeos', []);

            if (!is_array($mapeos)) {
                $v->errors()->add('mapeos', 'El formato de mapeos es inválido.');
                return;
            }

            $conceptoIds = array_map('intval', array_keys($mapeos));
            if ($conceptoIds) {
                $existen = DB::table('conceptos_financieros')
                    ->whereIn('id', $conceptoIds)
                    ->count();
                if ($existen !== count($conceptoIds)) {
                    $v->errors()->add('mapeos', 'Uno o más conceptos no existen.');
                }
            }

            $cuentasIds = array_values(array_filter($mapeos, fn($val) => !is_null($val)));
            if ($cuentasIds) {
                $count = DB::table('catalogo_cuentas')
                    ->where('empresa_id', $empresaId)
                    ->whereIn('id', $cuentasIds)
                    ->count();

                if ($count !== count($cuentasIds)) {
                    $v->errors()->add('mapeos', 'Hay cuentas que no pertenecen a la empresa seleccionada.');
                }
            }
        });
    }
}
