<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\SecurityQuestion;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\ThrottleService;
use App\Services\Api\Ynov\SecurityQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SecurityQuestionController extends Controller
{
    public function __construct(
        private SecurityQuestionService $securityQuestionService,
        private ThrottleService $throttleService,
    ) {}


    /**
     * Suggérer des questions de sécurité prédéfinies
     */
    public function suggestedQuestions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Questions de sécurité suggérées.',
            'code' => 'QUESTIONS_SUGGESTED',
            'data' => [
                [
                    'category' => 'Personnelle',
                    'questions' => [
                        'Quel est le nom de votre premier animal de compagnie ?',
                        'Quel est le nom de jeune fille de votre mère ?',
                        'Quelle est votre couleur préférée ?',
                        'Quel est votre plat préféré ?',
                        'Quel est votre film préféré ?',
                        'Quelle est votre chanson préférée ?',
                        'Quel est votre livre préféré ?',
                        'Quelle est votre ville préférée ?',
                        'Quel est votre sport préféré ?',
                        'Quelle est votre saison préférée ?',
                    ]
                ],
                [
                    'category' => 'Famille',
                    'questions' => [
                        'Quel est le prénom de votre père ?',
                        'Quel est le prénom de votre mère ?',
                        'Quel est le prénom de votre frère/soeur ?',
                        'Quel est le nom de votre grand-mère maternelle ?',
                        'Quel est le nom de votre grand-père paternel ?',
                        'Dans quelle ville vos parents se sont-ils rencontrés ?',
                        'Quelle est la date de naissance de votre mère ?',
                        'Quel est le métier de votre père ?',
                    ]
                ],
                [
                    'category' => 'Éducation',
                    'questions' => [
                        'Quel est le nom de votre école primaire ?',
                        'Quel est le nom de votre lycée ?',
                        'Quel est le nom de votre université ?',
                        'Quelle était votre matière préférée à l\'école ?',
                        'Quel est le nom de votre meilleur professeur ?',
                        'Quelle est votre année de diplôme ?',
                        'Quel est votre diplôme le plus élevé ?',
                    ]
                ],
                [
                    'category' => 'Professionnelle',
                    'questions' => [
                        'Quel est le nom de votre premier employeur ?',
                        'Quel était votre premier poste ?',
                        'Quel est le nom de votre manager actuel ?',
                        'Quelle est votre entreprise actuelle ?',
                        'Quel a été votre premier salaire ?',
                        'Quel est votre projet professionnel préféré ?',
                    ]
                ],
                [
                    'category' => 'Loisirs',
                    'questions' => [
                        'Quel est votre hobby préféré ?',
                        'Quel est votre sport préféré ?',
                        'Quelle est votre destination de vacances préférée ?',
                        'Quel est votre jeu vidéo préféré ?',
                        'Quelle est votre série préférée ?',
                        'Quel est votre acteur/actrice préféré ?',
                        'Quel est votre groupe/musicien préféré ?',
                    ]
                ],
                [
                    'category' => 'Mémorable',
                    'questions' => [
                        'Quel est votre plus beau souvenir d\'enfance ?',
                        'Quel est votre voyage le plus mémorable ?',
                        'Quel est votre événement marquant préféré ?',
                        'Quelle est votre plus grande fierté ?',
                        'Quel est votre meilleur ami d\'enfance ?',
                        'Quel est votre restaurant préféré ?',
                    ]
                ],
            ],
        ]);
    }


    /**
     * Récupérer toutes les questions disponibles
     */
    public function getAvailableQuestions(): JsonResponse
    {
        $questions = $this->securityQuestionService->getAvailableQuestions();

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }


    /**
     * Récupérer les questions d'un utilisateur avec ses réponses
     */
    public function getUserQuestions(Request $request): JsonResponse
    {
        $user = $request->user();
        $questions = $this->securityQuestionService->getQuestionsForUser($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user_uuid' => $user->uuid_user,
                'has_configured' => $this->securityQuestionService->hasConfiguredQuestions($user),
                'questions' => $questions,
            ],
        ]);
    }

    /**
     * Configurer les questions de sécurité d'un utilisateur
     */
    public function setUserQuestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'answers' => ['required', 'array', 'min:3', 'max:5'],
            'answers.*.question_uuid' => ['required', 'string', 'exists:security_questions,uuid'],
            'answers.*.answer' => ['required', 'string', 'min:2', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.',
                'code' => 'INVALID_PASSWORD',
            ], 403);
        }

        try {
            $this->securityQuestionService->setUserAnswers(
                $user,
                $request->input('answers'),
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Questions de sécurité configurées avec succès.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }


    /* 
     * Vérifier si un utilisateur a des questions de sécurité configurées
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string', 'exists:users,login'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email non trouvé.',
            ], 404);
        }

        $user = User::where('login', $request->login)->first();
        $questions = $this->securityQuestionService->getQuestionsForUser($user);
        $hasQuestions = count($questions) > 0;

        // Rate limiting pour éviter l'énumération
        $throttleKey = $this->throttleService->keyForIp($request->ip(), 'security_email_verify');
        
        try {
            $this->throttleService->checkAndIncrement($throttleKey, 5, 300);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives. Veuillez patienter.',
                'code' => 'TOO_MANY_ATTEMPTS',
            ], 429);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_uuid' => $user->uuid_user,
                'has_questions' => $hasQuestions,
                'questions' => $hasQuestions ? $questions : [],
            ],
        ]);
    }


    public function verifyAnswer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string'],
            
            'questions' => ['required', 'array', 'min:1'],
            
            'questions.*.question_uuid' => [
                'required',
                'string',
                'exists:security_questions,uuid',
            ],
            
            'questions.*.answer' => [
                'required',
                'string',
                'min:1',
                'max:255',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Recherche par login
        $user = User::where('login', $request->login)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Rate limiting global
        $throttleKey = $this->throttleService->key(
            $user,
            'security_question_verify'
        );

        try {
            $this->throttleService->checkAndIncrement(
                $throttleKey,
                5,
                300
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'TOO_MANY_ATTEMPTS',
                'available_in' => $this->throttleService
                    ->availableIn($throttleKey),
            ], 429);
        }

        try {
            // Vérifier toutes les réponses
            $result = $this->securityQuestionService->verifyAnswers(
                $user,
                $request->questions
            );

            // Au moins une réponse incorrecte
            if (!$result['success']) {

                $remaining = $this->throttleService
                    ->remainingAttempts($throttleKey, 5);

                return response()->json([
                    'success' => false,
                    'message' => 'Une ou plusieurs réponses sont incorrectes.',
                    'remaining_attempts' => max(0, $remaining),
                    'data' => [
                        'verified' => false,
                        'results' => $result['results'],
                    ],
                ], 422);
            }

            // Succès : reset du rate limiter
            $this->throttleService->clear($throttleKey);

            // Génération du token
            $resetToken = Str::random(64);

            // Hash du token avant stockage
            $hashedToken = Hash::make($resetToken);

            DB::table('password_reset_tokens')->updateOrInsert(
                [
                    'login' => $user->login,
                ],
                [
                    'token' => $hashedToken,
                    'created_at' => now(),
                    'expires_at' => now()->addMinutes(60),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Toutes les réponses sont correctes.',
                'data' => [
                    'verified' => true,
                    'user_uuid' => $user->uuid_user,
                    'reset_token' => $resetToken,
                    'results' => $result['results'],
                ],
            ]);

        } catch (\RuntimeException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'TOO_MANY_ATTEMPTS',
            ], 429);

        } catch (\Throwable $e) {

            Log::error('Erreur lors de la vérification des questions de sécurité', [
                'user_uuid' => $user->uuid_user,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la vérification.',
            ], 500);
        }
    }

    /**
     * Admin : Créer une nouvelle question de sécurité
     */
    public function createQuestion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question_text' => ['required', 'string', 'max:255', 'unique:security_questions,question_text'],
            'category' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'is_system' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $question = $this->securityQuestionService->createQuestion(
                $request->all(),
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Question de sécurité créée avec succès.',
                'data' => $question,
            ], 201);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Admin : Mettre à jour une question de sécurité
     */
    public function updateQuestion(Request $request, string $uuid): JsonResponse
    {
        $question = SecurityQuestion::where('uuid', $uuid)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'question_text' => ['sometimes', 'string', 'max:255', 'unique:security_questions,question_text,' . $question->id],
            'category' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'is_system' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updated = $this->securityQuestionService->updateQuestion(
                $question,
                $request->all(),
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Question de sécurité mise à jour avec succès.',
                'data' => $updated,
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Admin : Supprimer une question de sécurité
     */
    public function deleteQuestion(Request $request, string $uuid): JsonResponse
    {
        $question = SecurityQuestion::where('uuid', $uuid)->firstOrFail();

        try {
            $this->securityQuestionService->deleteQuestion(
                $question,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Question de sécurité supprimée avec succès.',
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}