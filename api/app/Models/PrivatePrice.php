<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Per-person price of a private tour for a group-size bracket. */
#[Guarded(['id'])]
class PrivatePrice extends Model
{
    use HasFactory;

    public function covers(int $groupSize): bool
    {
        return $groupSize >= $this->group_size_from && $groupSize <= $this->group_size_to;
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
