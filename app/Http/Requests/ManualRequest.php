<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['members', 'units'] as $group) {
            $values = $this->input($group, []);
            foreach ($this->input('present_'.$group, []) as $type) {
                if (is_array($values) && is_scalar($type) && ! isset($values[$type])) {
                    $values[$type] = [];
                }
            }
            if ($this->has($group) || $this->has('present_'.$group)) {
                $this->merge([$group => $values]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manual_name' => ['required', 'string', 'max:250'],
            'observations' => ['nullable', 'string', 'max:255'],
            'doctrinal_map_id' => ['required', 'integer', 'exists:doctrinal_maps,id'],
            'manual_types_id' => ['required', 'integer', Rule::exists('manual_types', 'id')->where('doctrinal_map_id', $this->input('doctrinal_map_id'))],
            'research_advances' => ['required', 'integer', 'between:2,6'],
            'experimentation_advances' => ['required', 'integer', 'between:2,8'],
            'process_started_on' => [$this->route('id') ? 'sometimes' : 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'version' => [$this->route('id') ? 'required' : 'sometimes', 'integer', 'min:0'],
            'members' => ['sometimes', 'array:1,2,3,4'],
            'members.*' => ['array'],
            'members.*.*' => ['integer', 'exists:committee_members,id'],
            'units' => ['sometimes', 'array:1,3,4'],
            'units.*' => ['array'],
            'units.*.*' => ['integer', 'exists:military_units,id'],
            'present_members' => ['sometimes', 'array'],
            'present_members.*' => ['integer', 'in:1,2,3,4'],
            'present_units' => ['sometimes', 'array'],
            'present_units.*' => ['integer', 'in:1,3,4'],
        ];
    }

    public function attributes(): array
    {
        return ['manual_types_id' => 'tipo de manual', 'doctrinal_map_id' => 'mapa doctrinario', 'research_advances' => 'avances de Investigación', 'experimentation_advances' => 'avances de Experimentación', 'process_started_on' => 'fecha de inicio'];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach (['members', 'units'] as $group) {
                $groups = $this->input($group, []);
                if (! is_array($groups)) {
                    continue;
                }
                foreach ($groups as $key => $ids) {
                    if (is_array($ids) && count(array_filter($ids, 'is_scalar')) === count($ids) && count($ids) !== count(array_unique($ids))) {
                        $validator->errors()->add($group.'.'.$key, 'No repitas la misma persona o unidad dentro de un comité.');
                    }
                }
            }
        });
    }
}
