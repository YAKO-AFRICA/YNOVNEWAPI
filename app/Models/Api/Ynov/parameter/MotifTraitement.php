<?php

namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MotifTraitement extends Model
{
    protected $table = 'motif_traitements';

    protected $fillable = [
        'uuid_motif_traitements',
        'libelle',
        'type',
        'status',
        'module',
    ];

    protected $casts = [
        'type' => 'array',
        'module' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_motif_traitements ??= (string) Str::uuid();
        });
    }

    public function scopeActif($query)
    {
        return $query->where('status', 'actif');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('libelle', 'like', "%{$search}%")
              ->orWhere('status', 'like', "%{$search}%");
        });
    }
}
