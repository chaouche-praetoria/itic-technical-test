<?php

namespace App\Exports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QuestionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $questions;

    public function __construct($questions = null)
    {
        $this->questions = $questions;
    }

    public function collection()
    {
        return $this->questions ?: Question::with(['domains', 'themes', 'academicLevel', 'choices'])->get();
    }

    public function headings(): array
    {
        return [
            'domaine', 'niveau', 'themes', 'difficulte', 'enonce', 'points', 'explication', 'reponse_multiple',
            'choix_1', 'correct_1', 'choix_2', 'correct_2', 'choix_3', 'correct_3', 'choix_4', 'correct_4', 'choix_5', 'correct_5', 'choix_6', 'correct_6'
        ];
    }

    public function map($question): array
    {
        $choices = $question->choices->take(6);
        
        $data = [
            $question->domains->pluck('name')->implode(', '),
            $question->academicLevel?->name,
            $question->themes->pluck('name')->implode(', '),
            $question->difficulty,
            $question->statement,
            $question->points,
            $question->explanation,
            $question->multiple_answers ? '1' : '0',
        ];

        for ($i = 0; $i < 6; $i++) {
            $choice = $choices->get($i);
            $data[] = $choice ? $choice->text : '';
            $data[] = $choice ? ($choice->is_correct ? '1' : '0') : '0';
        }

        return $data;
    }
}
