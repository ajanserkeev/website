<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class OperatorGuide extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['languages' => 'array'];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }
}
