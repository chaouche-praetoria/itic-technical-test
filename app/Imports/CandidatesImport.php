<?php

namespace App\Imports;

use App\Models\Candidate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CandidatesImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function model(array $row)
    {
        return Candidate::updateOrCreate(
            ['email' => $row['email']],
            [
                'first_name' => $row['prenom'] ?? $row['first_name'] ?? '',
                'last_name' => $row['nom'] ?? $row['last_name'] ?? '',
                'phone' => $row['telephone'] ?? $row['phone'] ?? null,
                'formation_souhaitee' => $row['formation'] ?? $row['formation_souhaitee'] ?? null,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'prenom' => 'nullable|string|max:100',
            'nom' => 'nullable|string|max:100',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'Le format de l\'email est invalide.',
        ];
    }
}
