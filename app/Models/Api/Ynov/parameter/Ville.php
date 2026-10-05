<?php

namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ville extends Model
{
    use HasFactory;

    protected $table = 'villes';

    protected $primaryKey = 'uuid_ville';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid_ville', 'codeVille', 'MonLibelle', 'MonPays', 'created_at', 'updated_at'
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $ville) => $ville->uuid_ville ??= (string) Str::uuid());
    }
}
