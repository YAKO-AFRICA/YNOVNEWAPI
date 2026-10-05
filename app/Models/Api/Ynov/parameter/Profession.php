<?php

namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Profession extends Model
{
    use HasFactory;

    protected $connexion = 'mysql';

    protected $table = 'professions';

    protected $primaryKey = 'uuid_profession';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid_profession',
        'code_profession',
        'MonLibelle'
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $profession) => $profession->uuid_profession ??= (string) Str::uuid());
    }
}
