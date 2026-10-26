<?php

namespace App\Services;

use App\Models\CommitteeMember;
use App\Models\Grade;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MemberCsvImport
{
    public const MAX_ROWS = 5000;

    public function preview(UploadedFile $file): array
    {
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw ValidationException::withMessages(['csv' => 'El archivo no debe superar 2 MB.']);
        }
        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            throw ValidationException::withMessages(['csv' => 'No se pudo leer el archivo.']);
        }
        try {
            $firstLine = fgets($stream);
            if ($firstLine === false) {
                throw ValidationException::withMessages(['csv' => 'El archivo está vacío.']);
            }
            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
            if (! mb_check_encoding($firstLine, 'UTF-8')) {
                throw ValidationException::withMessages(['csv' => 'Guarde el archivo con codificación UTF-8.']);
            }
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            $header = str_getcsv(trim($firstLine), $delimiter, '"', '');
            if ($header !== ['cedula', 'nombre', 'grado_codigo', 'celular']) {
                throw ValidationException::withMessages(['csv' => 'La cabecera debe ser cedula,nombre,grado_codigo,celular (se admite ; como separador).']);
            }
            $grades = Grade::pluck('id', 'code')->all();
            $rows = [];
            $seen = [];
            $line = 1;
            while (($fields = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if ($fields === [null]) {
                    continue;
                }
                if (count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages(['csv' => 'El archivo admite hasta '.self::MAX_ROWS.' integrantes por importación.']);
                }
                $fields = array_map(fn ($value) => trim((string) $value), $fields);
                if (! mb_check_encoding($fields, 'UTF-8')) {
                    throw ValidationException::withMessages(['csv' => 'La fila '.$line.' no está en UTF-8. Guarde el archivo con esa codificación.']);
                }
                $data = [
                    'identification' => $fields[0] ?? '', 'full_name' => $fields[1] ?? '',
                    'grade_code' => $fields[2] ?? '', 'cellphone' => ($fields[3] ?? '') !== '' ? $fields[3] : null,
                ];
                $validator = Validator::make($data, [
                    'identification' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
                    'full_name' => ['required', 'string', 'max:250'],
                    'grade_code' => ['required', 'integer'],
                    'cellphone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 ()-]*$/'],
                ], [
                    'identification.regex' => 'La cédula debe contener 10 dígitos.',
                    'full_name.required' => 'El nombre es obligatorio.',
                    'cellphone.regex' => 'El celular tiene un formato inválido.',
                ]);
                $errors = $validator->errors()->all();
                if (count($fields) !== 4) {
                    $errors[] = 'La fila debe tener exactamente cuatro columnas.';
                }
                if (! array_key_exists($data['grade_code'], $grades)) {
                    $errors[] = 'El código de grado no está registrado.';
                }
                $status = $errors ? 'error' : (isset($seen[$data['identification']]) ? 'duplicate' : 'new');
                if (! $errors) {
                    $seen[$data['identification']] = true;
                }
                $rows[] = ['line' => $line, 'status' => $status, 'errors' => $errors, 'data' => [
                    'identification' => $data['identification'], 'full_name' => $data['full_name'],
                    'grades_id' => $grades[$data['grade_code']] ?? null, 'cellphone' => $data['cellphone'],
                ]];
            }
            if (! $rows) {
                throw ValidationException::withMessages(['csv' => 'El archivo no contiene integrantes.']);
            }
            $existing = [];
            foreach (array_chunk(array_keys($seen), 500) as $identifications) {
                foreach (CommitteeMember::whereIn('identification', $identifications)->pluck('identification') as $identification) {
                    $existing[(string) $identification] = true;
                }
            }
            foreach ($rows as &$row) {
                if ($row['status'] === 'new' && isset($existing[$row['data']['identification']])) {
                    $row['status'] = 'existing';
                }
            }
            unset($row);
            $summary = ['new' => 0, 'existing' => 0, 'duplicate' => 0, 'error' => 0];
            foreach ($rows as $row) {
                $summary[$row['status']]++;
            }

            return ['rows' => $rows, 'summary' => $summary];
        } finally {
            fclose($stream);
        }
    }

    public function commit(array $preview): array
    {
        // An unexpected database error rolls back the whole batch. firstOrCreate
        // handles concurrent insertion through the existing unique cédula index.
        return DB::transaction(function () use ($preview) {
            $summary = ['inserted' => 0, 'omitted' => 0, 'errors' => 0];
            $gradeIds = Grade::pluck('id')->all();
            foreach ($preview['rows'] as $row) {
                if ($row['status'] === 'error') {
                    $summary['errors']++;
                    continue;
                }
                if ($row['status'] !== 'new') {
                    $summary['omitted']++;
                    continue;
                }
                $data = $row['data'];
                if (! in_array($data['grades_id'], $gradeIds, true)) {
                    throw ValidationException::withMessages(['csv' => 'Cambió el catálogo de grados. Revise nuevamente el archivo.']);
                }
                $identification = $data['identification'];
                unset($data['identification']);
                // Current locking read also sees a competing committed import under
                // MySQL's repeatable-read isolation. Deadlocks retry the whole batch.
                $member = CommitteeMember::where('identification', $identification)->lockForUpdate()->first();
                if ($member) {
                    $summary['omitted']++;
                    continue;
                }
                $member = CommitteeMember::createOrFirst(['identification' => $identification], $data);
                $summary[$member->wasRecentlyCreated ? 'inserted' : 'omitted']++;
            }

            return $summary;
        }, 3);
    }
}
