<?php

namespace App\Models;

use App\Enums\TourItemKind;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class TourItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['kind' => TourItemKind::class];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
