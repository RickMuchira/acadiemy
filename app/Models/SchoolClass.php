<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $table = 'school_classes';

    protected $fillable = ['level', 'stream', 'class_teacher_name'];

    /** Levels that require a stream (GREEN or RED). */
    public const STREAMED_LEVELS = ['PP1', 'PP2', 'Grade1', 'Grade2', 'Grade3'];

    /** Levels without streams. */
    public const NON_STREAMED_LEVELS = ['PG', 'Grade4', 'Grade5', 'Grade6'];

    /** Human-readable display labels for each level. */
    public const LEVEL_LABELS = [
        'PG' => 'Playgroup',
        'PP1' => 'PP1',
        'PP2' => 'PP2',
        'Grade1' => 'Grade 1',
        'Grade2' => 'Grade 2',
        'Grade3' => 'Grade 3',
        'Grade4' => 'Grade 4',
        'Grade5' => 'Grade 5',
        'Grade6' => 'Grade 6',
    ];

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'class_id')->orderBy('display_order');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Returns the composed display name, e.g. "PP1 GREEN" or "Grade 4".
     */
    public function getDisplayNameAttribute(): string
    {
        $label = self::LEVEL_LABELS[$this->level] ?? $this->level;

        return $this->stream ? "{$label} {$this->stream}" : $label;
    }
}
