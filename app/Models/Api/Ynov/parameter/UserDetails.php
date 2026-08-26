<?php
// app/Models/Api/Ynov/parameter/UserDetails.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

// class UserDetails extends Model
// {
//     use SoftDeletes;
    
    
//     protected $table = 'user_details';
    
//     protected $fillable = [
//         'uuid_user_details',
//         'code_agent',
//         'matricule',
//         'numero_client',
//         'user_uuid',
//         'nom',
//         'prenoms',
//         'fonction',
//         'service',
//         'departement',
//         'mobile_1',
//         'mobile_2',
//         'telephone_fixe',
//         'email_pro',
//         'photo',
//         'photo_path',
//         'date_naissance',
//         'lieu_naissance',
//         'lieu_residence',
//         'nationalite',
//         'genre',
//         'civilite',
//         'adresse_complete',
//         'ville',
//         'code_postal',
//         'pays',
//         'date_embauche',
//         'statut_employe',
//         'type_contrat',
//         'preferences',
//         'created_by',
//         'updated_by',
//         'deleted_by',
//     ];
    
//     protected $casts = [
//         'date_naissance' => 'date',
//         'date_embauche' => 'date',
//         'preferences' => 'array',
//     ];

//     protected static function booted(): void
//     {
//         static::creating(fn (self $model) => $model->uuid_user_details ??= (string) Str::uuid());
//     }

//     public function user(): BelongsTo
//     {
//         return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
//     }

//     /**
//      * Obtenir le photo_path nettoyé
//      */
//     public function getCleanPhotoPathAttribute(): ?string
//     {
//         if (!$this->photo_path) {
//             return null;
//         }
//         return str_replace('\\', '/', $this->photo_path);
//     }

//     /**
//      * Obtenir l'URL complète de la photo
//      */
//     public function getPhotoUrlAttribute(): ?string
//     {
//         // Si photo est une URL externe
//         if ($this->photo && filter_var($this->photo, FILTER_VALIDATE_URL)) {
//             return $this->photo;
//         }
        
//         $path = $this->clean_photo_path ?? $this->photo;
        
//         if ($path && !filter_var($path, FILTER_VALIDATE_URL)) {
//             // Nettoyer le chemin
//             $cleanPath = str_replace('\\', '/', $path);
//             $cleanPath = ltrim($cleanPath, '/');
            
//             try {
//                 return route('storage.documents', ['file' => $cleanPath]);
//             } catch (\Exception $e) {
//                 return url('/storage/documents/' . $cleanPath);
//             }
//         }
        
//         return null;
//     }

//     /**
//      * Setter pour photo_path
//      */
//     public function setPhotoPathAttribute($value): void
//     {
//         if ($value) {
//             // Nettoyer le chemin
//             $cleanPath = str_replace('\\', '/', $value);
//             $cleanPath = ltrim($cleanPath, '/');
//             $this->attributes['photo_path'] = $cleanPath;
//         } else {
//             $this->attributes['photo_path'] = null;
//         }
//     }
    
//     /**
//      * Obtenir le nom complet
//      */
//     public function getFullNameAttribute(): string
//     {
//         return $this->prenoms . ' ' . $this->nom;
//     }
    
//     /**
//      * Obtenir le nom complet avec titre
//      */
//     public function getFormalNameAttribute(): string
//     {
//         $civilite = $this->civilite ? $this->civilite . ' ' : '';
//         return $civilite . $this->getFullNameAttribute();
//     }
    
//     /**
//      * Scope pour un genre spécifique
//      */
//     public function scopeGender($query, string $gender)
//     {
//         return $query->where('genre', $gender);
//     }
    
//     /**
//      * Scope pour une ville spécifique
//      */
//     public function scopeInCity($query, string $city)
//     {
//         return $query->where('ville', $city);
//     }
// }

class UserDetails extends Model
{
    use SoftDeletes;
    
    protected $table = 'user_details';
    
    protected $fillable = [
        'uuid_user_details',
        'code_agent',
        'matricule',
        'numero_client',
        'user_uuid',
        'nom',
        'prenoms',
        'fonction',
        'service',
        'departement',
        'mobile_1',
        'mobile_2',
        'telephone_fixe',
        'email_pro',
        'photo',
        'photo_path',
        'date_naissance',
        'lieu_naissance',
        'lieu_residence',
        'nationalite',
        'genre',
        'civilite',
        'adresse_complete',
        'ville',
        'code_postal',
        'pays',
        'date_embauche',
        'statut_employe',
        'type_contrat',
        'preferences',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    protected $casts = [
        'date_naissance' => 'date',
        'date_embauche' => 'date',
        'preferences' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_user_details ??= (string) Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }

    /**
     * Setter pour photo_path - nettoie automatiquement
     */
    public function setPhotoPathAttribute($value): void
    {
        if ($value) {
            // Si c'est déjà une URL complète, extraire le chemin
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $parsed = parse_url($value);
                $path = $parsed['path'] ?? '';
                // Enlever le préfixe /storage/documents/ si présent
                $value = str_replace('/storage/documents/', '', $path);
                $value = str_replace('/storage/documents', '', $value);
            }
            
            // Nettoyer le chemin
            $cleanPath = str_replace('\\', '/', $value);
            $cleanPath = ltrim($cleanPath, '/');
            $this->attributes['photo_path'] = $cleanPath;
        } else {
            $this->attributes['photo_path'] = null;
        }
    }
    
    /**
     * Obtenir le chemin complet de la photo
     */
    public function getPhotoPathAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }
        return str_replace('\\', '/', $value);
    }
    
    /**
     * Obtenir le nom complet
     */
    public function getFullNameAttribute(): string
    {
        return $this->prenoms . ' ' . $this->nom;
    }
    
    /**
     * Obtenir le nom complet avec titre
     */
    public function getFormalNameAttribute(): string
    {
        $civilite = $this->civilite ? $this->civilite . ' ' : '';
        return $civilite . $this->getFullNameAttribute();
    }
    
    /**
     * Scope pour un genre spécifique
     */
    public function scopeGender($query, string $gender)
    {
        return $query->where('genre', $gender);
    }
    
    /**
     * Scope pour une ville spécifique
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('ville', $city);
    }
}