<?php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\SecurityQuestion;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSecurityAnswer extends Model
{
    protected $table = 'user_security_answers';

    protected $fillable = [
        'uuid',
        'user_uuid',
        'security_question_uuid',
        'answer_hash',
        'verified_at',
        'verification_attempts',
        'last_attempt_at',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'answer_hash',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'verification_attempts' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(fn($model) => $model->uuid ??= (string) Str::uuid());
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }

    /**
     * Relation avec la question de sécurité
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(SecurityQuestion::class, 'security_question_uuid', 'uuid');
    }

    /**
     * Vérifier si la réponse est correcte
     */
    public function verifyAnswer(string $answer): bool
    {
        // Normaliser la réponse
        $normalized = $this->normalizeAnswer($answer);
        
        // Vérifier avec Hash::check
        $isValid = Hash::check($normalized, $this->answer_hash);
        
        // Mettre à jour les statistiques
        $this->increment('verification_attempts');
        $this->update(['last_attempt_at' => now()]);
        
        if ($isValid) {
            $this->update(['verified_at' => now()]);
        }
        
        return $isValid;
    }

    /**
     * Normaliser une réponse pour la comparaison
     */
    private function normalizeAnswer(string $answer): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $answer)));
    }

    /**
     * Vérifier si l'utilisateur a dépassé le nombre maximum de tentatives
     */
    public function hasExceededMaxAttempts(int $maxAttempts = 5): bool
    {
        return $this->verification_attempts >= $maxAttempts;
    }

    /**
     * Réinitialiser les tentatives
     */
    public function resetAttempts(): void
    {
        $this->update([
            'verification_attempts' => 0,
            'last_attempt_at' => null,
        ]);
    }
}