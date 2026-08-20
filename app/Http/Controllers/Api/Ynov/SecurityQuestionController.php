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

// class SecurityQuestionController extends Controller
// {
//     public function __construct(
//         private SecurityQuestionService $securityQuestionService,
//         private ThrottleService $throttleService,
//     ) {}

//     /**
//      * ================================================================
//      * NOUVEAU : Suggérer des questions de sécurité prédéfinies
//      * ================================================================
//      */
//     public function suggestedQuestions(): JsonResponse
//     {
//         return response()->json([
//             'success' => true,
//             'message' => 'Questions de sécurité suggérées.',
//             'code' => 'QUESTIONS_SUGGESTED',
//             'data' => [
//                 // Questions personnelles
//                 [
//                     'category' => 'Personnelle',
//                     'questions' => [
//                         'Quel est le nom de votre premier animal de compagnie ?',
//                         'Quel est le nom de jeune fille de votre mère ?',
//                         'Quelle est votre couleur préférée ?',
//                         'Quel est votre plat préféré ?',
//                         'Quel est votre film préféré ?',
//                         'Quelle est votre chanson préférée ?',
//                         'Quel est votre livre préféré ?',
//                         'Quelle est votre ville préférée ?',
//                         'Quel est votre sport préféré ?',
//                         'Quelle est votre saison préférée ?',
//                     ]
//                 ],
//                 // Questions familiales
//                 [
//                     'category' => 'Famille',
//                     'questions' => [
//                         'Quel est le prénom de votre père ?',
//                         'Quel est le prénom de votre mère ?',
//                         'Quel est le prénom de votre frère/soeur ?',
//                         'Quel est le nom de votre grand-mère maternelle ?',
//                         'Quel est le nom de votre grand-père paternel ?',
//                         'Dans quelle ville vos parents se sont-ils rencontrés ?',
//                         'Quelle est la date de naissance de votre mère ?',
//                         'Quel est le métier de votre père ?',
//                     ]
//                 ],
//                 // Questions éducatives
//                 [
//                     'category' => 'Éducation',
//                     'questions' => [
//                         'Quel est le nom de votre école primaire ?',
//                         'Quel est le nom de votre lycée ?',
//                         'Quel est le nom de votre université ?',
//                         'Quelle était votre matière préférée à l\'école ?',
//                         'Quel est le nom de votre meilleur professeur ?',
//                         'Quelle est votre année de diplôme ?',
//                         'Quel est votre diplôme le plus élevé ?',
//                     ]
//                 ],
//                 // Questions professionnelles
//                 [
//                     'category' => 'Professionnelle',
//                     'questions' => [
//                         'Quel est le nom de votre premier employeur ?',
//                         'Quel était votre premier poste ?',
//                         'Quel est le nom de votre manager actuel ?',
//                         'Quelle est votre entreprise actuelle ?',
//                         'Quel a été votre premier salaire ?',
//                         'Quel est votre projet professionnel préféré ?',
//                     ]
//                 ],
//                 // Questions sur les loisirs
//                 [
//                     'category' => 'Loisirs',
//                     'questions' => [
//                         'Quel est votre hobby préféré ?',
//                         'Quel est votre sport préféré ?',
//                         'Quelle est votre destination de vacances préférée ?',
//                         'Quel est votre jeu vidéo préféré ?',
//                         'Quelle est votre série préférée ?',
//                         'Quel est votre acteur/actrice préféré ?',
//                         'Quel est votre groupe/musicien préféré ?',
//                     ]
//                 ],
//                 // Questions mémorables
//                 [
//                     'category' => 'Mémorable',
//                     'questions' => [
//                         'Quel est votre plus beau souvenir d\'enfance ?',
//                         'Quel est votre voyage le plus mémorable ?',
//                         'Quel est votre événement marquant préféré ?',
//                         'Quelle est votre plus grande fierté ?',
//                         'Quel est votre meilleur ami d\'enfance ?',
//                         'Quel est votre restaurant préféré ?',
//                     ]
//                 ],
//             ],
//         ]);
//     }

//     /**
//      * Récupérer toutes les questions disponibles
//      */
//     public function getAvailableQuestions(): JsonResponse
//     {
//         $questions = $this->securityQuestionService->getAvailableQuestions();

//         return response()->json([
//             'success' => true,
//             'data' => $questions,
//         ]);
//     }

//     /**
//      * Récupérer les questions d'un utilisateur avec ses réponses
//      */
//     public function getUserQuestions(Request $request): JsonResponse
//     {
//         $user = $request->user();
//         $questions = $this->securityQuestionService->getQuestionsForUser($user);

//         return response()->json([
//             'success' => true,
//             'data' => [
//                 'user_uuid' => $user->uuid_user,
//                 'has_configured' => $this->securityQuestionService->hasConfiguredQuestions($user),
//                 'questions' => $questions,
//             ],
//         ]);
//     }

//     /**
//      * Configurer les questions de sécurité d'un utilisateur
//      */
//     public function setUserQuestions(Request $request): JsonResponse
//     {
//         $validator = Validator::make($request->all(), [
//             'answers' => ['required', 'array', 'min:3', 'max:5'],
//             'answers.*.question_uuid' => ['required', 'string', 'exists:security_questions,uuid'],
//             'answers.*.answer' => ['required', 'string', 'min:2', 'max:255'],
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Données invalides.',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $user = $request->user();

//         // Vérifier que l'utilisateur a le droit de modifier ses questions
//         // (nécessite une ré-authentification ou un mot de passe)
//         if (!$this->verifyUserAuthorization($request)) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Veuillez confirmer votre mot de passe pour modifier vos questions de sécurité.',
//                 'code' => 'AUTHENTICATION_REQUIRED',
//             ], 403);
//         }

//         try {
//             $this->securityQuestionService->setUserAnswers(
//                 $user,
//                 $request->input('answers'),
//                 $request->user()->uuid_user
//             );

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Questions de sécurité configurées avec succès.',
//             ]);
//         } catch (\InvalidArgumentException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage(),
//             ], 422);
//         }
//     }

//     /**
//      * Vérifier une réponse de sécurité (pour récupération de compte)
//      */
//     public function verifyAnswer(Request $request): JsonResponse
//     {
//         $validator = Validator::make($request->all(), [
//             'email' => ['required', 'email', 'exists:users,email'],
//             'question_uuid' => ['required', 'string', 'exists:security_questions,uuid'],
//             'answer' => ['required', 'string', 'min:1', 'max:255'],
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Données invalides.',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         $user = User::where('email', $request->email)->first();

//         if (!$user) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Utilisateur non trouvé.',
//             ], 404);
//         }

//         // Rate limiting
//         $throttleKey = $this->throttleService->key($user, 'security_question_verify');

//         try {
//             $this->throttleService->checkAndIncrement(
//                 $throttleKey,
//                 5, // Max 5 tentatives
//                 300 // 5 minutes
//             );
//         } catch (\RuntimeException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage(),
//                 'code' => 'TOO_MANY_ATTEMPTS',
//                 'available_in' => $this->throttleService->availableIn($throttleKey),
//             ], 429);
//         }

//         try {
//             $isValid = $this->securityQuestionService->verifyAnswer(
//                 $user,
//                 $request->question_uuid,
//                 $request->answer
//             );

//             if ($isValid) {
//                 // Réinitialiser le compteur en cas de succès
//                 $this->throttleService->clear($throttleKey);

//                 return response()->json([
//                     'success' => true,
//                     'message' => 'Réponse correcte.',
//                     'data' => [
//                         'verified' => true,
//                         'user_uuid' => $user->uuid_user,
//                     ],
//                 ]);
//             }

//             return response()->json([
//                 'success' => false,
//                 'message' => 'Réponse incorrecte.',
//                 'remaining_attempts' => $this->throttleService->remainingAttempts($throttleKey, 5),
//             ], 422);

//         } catch (\RuntimeException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage(),
//                 'code' => 'TOO_MANY_ATTEMPTS',
//             ], 429);
//         }
//     }

//     /**
//      * Vérifier l'autorisation de l'utilisateur (mot de passe requis)
//      */
//     private function verifyUserAuthorization(Request $request): bool
//     {
//         $validator = Validator::make($request->all(), [
//             'password' => ['required', 'string'],
//         ]);

//         if ($validator->fails()) {
//             return false;
//         }

//         $user = $request->user();
//         return Hash::check($request->password, $user->password);
//     }

//     /**
//      * Admin : Créer une nouvelle question de sécurité
//      */
//     public function createQuestion(Request $request): JsonResponse
//     {
//         $validator = Validator::make($request->all(), [
//             'question_text' => ['required', 'string', 'max:255', 'unique:security_questions,question_text'],
//             'category' => ['nullable', 'string', 'max:50'],
//             'is_active' => ['nullable', 'boolean'],
//             'min_answers' => ['nullable', 'integer', 'min:1', 'max:10'],
//             'max_answers' => ['nullable', 'integer', 'min:1', 'max:10'],
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Données invalides.',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         try {
//             $question = $this->securityQuestionService->createQuestion(
//                 $request->all(),
//                 $request->user()->uuid_user
//             );

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Question de sécurité créée avec succès.',
//                 'data' => $question,
//             ], 201);

//         } catch (\RuntimeException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage(),
//             ], 422);
//         }
//     }

//     /**
//      * Admin : Mettre à jour une question de sécurité
//      */
//     public function updateQuestion(Request $request, string $uuid): JsonResponse
//     {
//         $question = SecurityQuestion::where('uuid', $uuid)->firstOrFail();

//         $validator = Validator::make($request->all(), [
//             'question_text' => ['sometimes', 'string', 'max:255', 'unique:security_questions,question_text,' . $question->id],
//             'category' => ['nullable', 'string', 'max:50'],
//             'is_active' => ['nullable', 'boolean'],
//             'is_system' => ['nullable', 'boolean'],
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Données invalides.',
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         try {
//             $updated = $this->securityQuestionService->updateQuestion(
//                 $question,
//                 $request->all(),
//                 $request->user()->uuid_user
//             );

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Question de sécurité mise à jour avec succès.',
//                 'data' => $updated,
//             ]);

//         } catch (\RuntimeException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage(),
//             ], 403);
//         }
//     }

//     /**
//      * Admin : Supprimer une question de sécurité
//      */
//     public function deleteQuestion(Request $request, string $uuid): JsonResponse
//     {
//         $question = SecurityQuestion::where('uuid', $uuid)->firstOrFail();

//         try {
//             $this->securityQuestionService->deleteQuestion(
//                 $question,
//                 $request->user()->uuid_user
//             );

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Question de sécurité supprimée avec succès.',
//             ]);

//         } catch (\RuntimeException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage(),
//             ], 422);
//         }
//     }
// }

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
     * @OA\Post(
     *     path="/security/user-questions",
     *     operationId="securitySetUserQuestions",
     *     tags={"Security Questions"},
     *     summary="Configurer (remplacer) les questions de sécurité de l'utilisateur connecté",
     *     description="Authentifié. **Exige le mot de passe courant** pour confirmer l'identité avant toute modification (défense en profondeur — même avec un token valide, une session détournée ne peut pas reconfigurer les questions sans connaître le mot de passe). Entre 3 et 5 réponses requises (bornes `SecurityQuestionService::MIN_REQUIRED_ANSWERS`/`MAX_ALLOWED_ANSWERS`). **Remplace intégralement** les réponses existantes (suppression puis recréation — pas de fusion incrémentale). Chaque réponse est normalisée (minuscules, espaces réduits, trim) puis hashée avec `Hash::make()` avant stockage — **aucune réponse en clair n'est jamais persistée**. Journalise l'action dans `activity_logs` (sans les réponses elles-mêmes, uniquement le nombre de questions configurées).",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers","password"},
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 minItems=3,
     *                 maxItems=5,
     *                 @OA\Items(
     *                     type="object",
     *                     required={"question_uuid","answer"},
     *                     @OA\Property(property="question_uuid", type="string", format="uuid", description="Doit correspondre à une question existante dans security_questions.uuid."),
     *                     @OA\Property(property="answer", type="string", minLength=2, maxLength=255, example="Rex", description="Réponse en clair envoyée une seule fois lors de la configuration — jamais renvoyée ni stockée en clair ensuite.")
     *                 ),
     *                 description="Chaque question_uuid doit être unique dans la liste (pas de doublon de question) et correspondre à une question active."
     *             ),
     *             @OA\Property(property="password", type="string", format="password", description="Mot de passe actuel de l'utilisateur, requis pour confirmer l'action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Questions de sécurité configurées avec succès.",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Questions de sécurité configurées avec succès."))
     *     ),
     *     @OA\Response(response=401, description="Non authentifié.", @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponse")),
     *     @OA\Response(
     *         response=403,
     *         description="Mot de passe incorrect.",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="Mot de passe incorrect."), @OA\Property(property="code", type="string", example="INVALID_PASSWORD"))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Échec de validation du FormRequest (nombre de réponses hors bornes, question_uuid inexistant) OU règle métier violée côté service (moins de 3 / plus de 5 réponses, question dupliquée, question inactive) — ces deux sources d'erreur 422 ont des structures différentes.",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(ref="#/components/schemas/ValidationErrorResponse"),
     *                 @OA\Schema(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="Vous ne pouvez pas sélectionner deux fois la même question."))
     *             }
     *         )
     *     )
     * )
     */
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


    /**
     * @OA\Post(
     *     path="/security/verify-email",
     *     operationId="securityVerifyEmail",
     *     tags={"Security Questions"},
     *     summary="Vérifier un email et récupérer les questions de sécurité associées (récupération de compte)",
     *     description="Endpoint **public**, première étape du parcours de récupération de compte par questions de sécurité (alternative à /auth/forgot-password). **Point de vigilance sécurité important** : contrairement à `/auth/forgot-password` qui retourne toujours le même message générique (`Si cet email existe, un lien a été envoyé.`) pour éviter l'énumération de comptes, cet endpoint retourne une **erreur 404 explicite** si l'email n'existe pas (`'Email non trouvé.'`), et révèle `has_questions` (booléen) ainsi que la liste des questions configurées si l'email existe. **Ceci constitue une fuite d'information exploitable pour énumérer les comptes existants et connaître leur niveau de configuration sécurité** — à signaler comme incohérence de sécurité par rapport au reste de l'API, qui applique systématiquement le principe anti-énumération ailleurs (login, forgot-password). Rate-limité à 5 requêtes / 5 minutes par IP (`ThrottleService`, appliqué **après** la recherche utilisateur, donc n'empêche pas un premier essai de révéler l'information avant que la limite ne s'active) — de plus, le rate limit du contrôleur (`ThrottleService`, clé par IP) s'ajoute au `throttle:5,15` déjà posé sur la route, ce qui applique deux limites différentes en parallèle.",
     *     security={},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@yako-africa.ci", description="Doit exister dans la table users (règle 'exists:users,email' — voir remarque de sécurité dans la description de l'endpoint).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email trouvé — informations de configuration des questions de sécurité révélées.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_uuid", type="string", format="uuid"),
     *                 @OA\Property(property="has_questions", type="boolean"),
     *                 @OA\Property(property="questions", type="array", @OA\Items(type="object"), description="Liste vide si has_questions=false, sinon structure identique à GET /security/user-questions.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email non trouvé dans le système — révèle explicitement l'absence du compte (voir avertissement de sécurité dans la description).",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="Email non trouvé."))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Format d'email invalide (le message '404 Email non trouvé' du contrôleur est en réalité retourné aussi pour un échec de la règle 'exists', voir description).",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit dépassé (5 tentatives / 5 minutes par IP, ThrottleService + throttle:5,15 de la route).",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="Trop de tentatives. Veuillez patienter."), @OA\Property(property="code", type="string", example="TOO_MANY_ATTEMPTS"))
     *     )
     * )
     */
    /**
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

        $user = User::where('email', $request->email)->orWhere('login', $request->login)->first();
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

        // Recherche par email OU login
        $user = User::where('email', $request->login)
            ->orWhere('login', $request->login)
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
                    'login' => $user->email ?? $user->login,
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