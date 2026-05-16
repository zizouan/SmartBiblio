<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasUuid;

    protected $fillable = ['name', 'slug', 'description', 'color_hex'];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_genres');
    }
}
