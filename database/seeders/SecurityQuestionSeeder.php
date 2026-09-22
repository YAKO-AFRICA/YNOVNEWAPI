<?php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\SecurityQuestion;
use App\Services\Api\Ynov\SecurityQuestionService;
use Illuminate\Database\Seeder;

class SecurityQuestionSeeder extends Seeder
{
    public function __construct(
        private SecurityQuestionService $securityQuestionService
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = $this->securityQuestionService->suggestedQuestions();

        foreach ($questions as $categoryData) {
            $category = $categoryData['category'];

            foreach ($categoryData['questions'] as $questionText) {
                // Vérifier si la question existe déjà
                $existing = SecurityQuestion::where('question_text', $questionText)->first();

                if (!$existing) {
                    SecurityQuestion::create([
                        'question_text' => $questionText,
                        'category' => $category,
                        'is_active' => true,
                        'is_system' => true,
                    ]);
                }
            }
        }

        $this->command->info('Questions de sécurité créées avec succès.');
    }
}
