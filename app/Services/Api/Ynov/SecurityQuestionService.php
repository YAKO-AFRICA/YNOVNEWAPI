<?php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\SecurityQuestion;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\UserSecurityAnswer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecurityQuestionService
{
    private const MAX_ATTEMPTS = 5;
    private const MIN_REQUIRED_ANSWERS = 3;
    private const MAX_ALLOWED_ANSWERS = 5;

    /**
     * Récupérer toutes les questions actives
     */
    public function getAvailableQuestions(): array
    {
        return SecurityQuestion::active()
            ->get(['uuid', 'question_text', 'category'])
            ->toArray();
    }

    /**
     * Récupérer les questions actives par catégorie
     */
    public function getQuestionsByCategory(string $category): array
    {
        return SecurityQuestion::active()
            ->category($category)
            ->get(['uuid', 'question_text'])
            ->toArray();
    }

    /**
     * Récupérer les questions d'un utilisateur avec ses réponses
     */
    public function getQuestionsForUser(User $user): array
    {
        $answers =  UserSecurityAnswer::where('user_uuid', $user->uuid_user)
            ->with('question')
            ->get()
            ->map(function ($answer) {
                return [
                    'question_uuid' => $answer->question->uuid,
                    'question_text' => $answer->question->question_text,
                    'category' => $answer->question->category,
                    'verified_at' => $answer->verified_at,
                    'verification_attempts' => $answer->verification_attempts,
                ];
            })
            ->toArray();
        // Log::info($answers);
        return $answers;
    }

    /**
     * Définir les réponses de sécurité pour un utilisateur
     */
    public function setUserAnswers(User $user, array $answers, string $creatorUuid): void
    {
        // Valider le nombre de réponses
        $this->validateAnswerCount($answers);

        // Supprimer les anciennes réponses
        UserSecurityAnswer::where('user_uuid', $user->uuid_user)->delete();

        // Créer les nouvelles réponses
        foreach ($answers as $answer) {
            $this->createUserAnswer($user, $answer, $creatorUuid);
        }

        // Journaliser l'action
        ActivityLog::log([
            'user_uuid' => $user->uuid_user,
            'action' => 'security_questions_set',
            'action_type' => 'security',
            'module' => 'security',
            'description' => "Configuration des questions de sécurité pour l'utilisateur : {$user->email}",
            'level' => 'info',
            'metadata' => [
                'question_count' => count($answers),
            ],
        ]);
    }

    /**
     * Créer une réponse pour un utilisateur
     */
    private function createUserAnswer(User $user, array $answer, string $creatorUuid): void
    {
        // Normaliser et hasher la réponse
        $normalizedAnswer = $this->normalizeAnswer($answer['answer']);
        $hashedAnswer = Hash::make($normalizedAnswer);

        UserSecurityAnswer::create([
            'uuid' => (string) Str::uuid(),
            'user_uuid' => $user->uuid_user,
            'security_question_uuid' => $answer['question_uuid'],
            'answer_hash' => $hashedAnswer,
            'created_by' => $creatorUuid,
        ]);
    }

    /**
     * Valider le nombre de réponses
     */
    private function validateAnswerCount(array $answers): void
    {
        $count = count($answers);

        if ($count < self::MIN_REQUIRED_ANSWERS) {
            throw new \InvalidArgumentException(
                "Vous devez fournir au moins " . self::MIN_REQUIRED_ANSWERS . " réponses de sécurité."
            );
        }

        if ($count > self::MAX_ALLOWED_ANSWERS) {
            throw new \InvalidArgumentException(
                "Vous ne pouvez pas fournir plus de " . self::MAX_ALLOWED_ANSWERS . " réponses de sécurité."
            );
        }

        // Vérifier les doublons de questions
        $questionUuids = array_column($answers, 'question_uuid');
        if (count($questionUuids) !== count(array_unique($questionUuids))) {
            throw new \InvalidArgumentException("Vous ne pouvez pas sélectionner deux fois la même question.");
        }

        // Vérifier que toutes les questions existent
        $existingQuestions = SecurityQuestion::whereIn('uuid', $questionUuids)
            ->where('is_active', true)
            ->pluck('uuid')
            ->toArray();

        $invalidQuestions = array_diff($questionUuids, $existingQuestions);
        if (!empty($invalidQuestions)) {
            throw new \InvalidArgumentException("Certaines questions de sécurité n'existent pas ou sont désactivées.");
        }
    }

    /**
     * Normaliser une réponse
     */
    private function normalizeAnswer(string $answer): string
    {
        // Supprimer les espaces en trop, mettre en minuscule
        return strtolower(trim(preg_replace('/\s+/', ' ', $answer)));
    }

    /**
     * Vérifier une réponse de sécurité
     */

    public function verifyAnswers(User $user, array $questions): array
    {
        $results = [];

        $allValid = true;

        foreach ($questions as $question) {

            $questionUuid = $question['question_uuid'];
            $answer = $question['answer'];

            $isValid = $this->verifyAnswer(
                $user,
                $questionUuid,
                $answer
            );

            $results[] = [
                'question_uuid' => $questionUuid,
                'verified' => $isValid,
            ];

            if (!$isValid) {
                $allValid = false;
            }
        }

        return [
            'success' => $allValid,
            'results' => $results,
        ];
    }

    public function verifyAnswer(
        User $user,
        string $questionUuid,
        string $answer
    ): bool {

        $userAnswer = UserSecurityAnswer::where(
            'user_uuid',
            $user->uuid_user
        )
            ->where(
                'security_question_uuid',
                $questionUuid
            )
            ->first();

        if (!$userAnswer) {

            $this->logFailedAttempt(
                $user,
                $questionUuid,
                'question_not_found'
            );

            return false;
        }

        // Vérifier le nombre maximum de tentatives
        if (
            $userAnswer->hasExceededMaxAttempts(
                self::MAX_ATTEMPTS
            )
        ) {

            $this->logFailedAttempt(
                $user,
                $questionUuid,
                'max_attempts_exceeded'
            );

            throw new \RuntimeException(
                'Trop de tentatives de vérification. Veuillez contacter l\'administrateur.',
                429
            );
        }

        // Normaliser la réponse
        $normalizedAnswer = $this->normalizeAnswer($answer);

        // Vérifier le hash
        $isValid = Hash::check(
            $normalizedAnswer,
            $userAnswer->answer_hash
        );

        // Mettre à jour les statistiques
        $userAnswer->increment('verification_attempts');

        $userAnswer->update([
            'last_attempt_at' => now(),
        ]);

        if ($isValid) {

            $userAnswer->update([
                'verified_at' => now(),
            ]);

            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'security_question_verified',
                'action_type' => 'security',
                'module' => 'security',
                'description' => 'Vérification réussie d\'une question de sécurité.',
                'level' => 'info',
                'metadata' => [
                    'question_uuid' => $questionUuid,
                ],
            ]);
        } else {

            $this->logFailedAttempt(
                $user,
                $questionUuid,
                'invalid_answer'
            );
        }

        return $isValid;
    }

    /**
     * Journaliser une tentative échouée
     */
    private function logFailedAttempt(User $user, string $questionUuid, string $reason): void
    {
        ActivityLog::log([
            'user_uuid' => $user->uuid_user,
            'action' => 'security_question_failed',
            'action_type' => 'security',
            'module' => 'security',
            'description' => "Tentative échouée de vérification d'une question de sécurité.",
            'level' => 'warning',
            'metadata' => [
                'question_uuid' => $questionUuid,
                'reason' => $reason,
            ],
        ]);
    }

    /**
     * Vérifier si un utilisateur a configuré ses questions de sécurité
     */
    public function hasConfiguredQuestions(User $user): bool
    {
        $count = UserSecurityAnswer::where('user_uuid', $user->uuid_user)->count();
        return $count >= self::MIN_REQUIRED_ANSWERS;
    }

    /**
     * Réinitialiser les questions de sécurité d'un utilisateur
     */
    public function resetUserQuestions(User $user, string $resetterUuid): void
    {
        UserSecurityAnswer::where('user_uuid', $user->uuid_user)->delete();

        ActivityLog::log([
            'user_uuid' => $user->uuid_user,
            'action' => 'security_questions_reset',
            'action_type' => 'security',
            'module' => 'security',
            'description' => "Réinitialisation des questions de sécurité de l'utilisateur : {$user->email}",
            'level' => 'warning',
            'metadata' => [
                'resetter_uuid' => $resetterUuid,
            ],
        ]);
    }

    /**
     * Créer une nouvelle question de sécurité (système)
     */
    public function createQuestion(array $data, string $creatorUuid): SecurityQuestion
    {
        return SecurityQuestion::create([
            'uuid' => (string) Str::uuid(),
            'question_text' => $data['question_text'],
            'category' => $data['category'] ?? 'Générale',
            'is_active' => $data['is_active'] ?? true,
            'is_system' => $data['is_system'] ?? false,
            'created_by' => $creatorUuid,
        ]);
    }

    /**
     * Mettre à jour une question de sécurité
     */
    public function updateQuestion(SecurityQuestion $question, array $data, string $updaterUuid): SecurityQuestion
    {
        $user = User::where('uuid_user', $updaterUuid)->first();
        // Ne pas permettre la modification des questions système (sauf par Super Admin)
        if ($question->is_system && !$user->isSuperAdmin()) {
            throw new \RuntimeException("Les questions système ne peuvent pas être modifiées.");
        }

        $question->update([
            'question_text' => $data['question_text'] ?? $question->question_text,
            'category' => $data['category'] ?? $question->category,
            'is_active' => $data['is_active'] ?? $question->is_active,
            'is_system' => $data['is_system'] ?? $question->is_system,
            'updated_by' => $updaterUuid,
        ]);

        return $question->fresh();
    }

    /**
     * Supprimer une question de sécurité
     */
    public function deleteQuestion(SecurityQuestion $question, string $deleterUuid): void
    {
        $user = User::where('uuid_user', $deleterUuid)->first();
        // Ne pas permettre la suppression des questions système (sauf par Super Admin)
        if ($question->is_system && !$user->isSuperAdmin()) {
            throw new \RuntimeException("Les questions système ne peuvent pas être supprimées.");
        }

        // Vérifier si la question est utilisée
        if ($question->userAnswers()->count() > 0) {
            throw new \RuntimeException("Cette question est utilisée par des utilisateurs et ne peut pas être supprimée.");
        }

        $question->update([
            'is_active' => false,
            'deleted_by' => $deleterUuid,
        ]);

        $question->delete();
    }
}
