<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskBoardNote extends Model
{
    protected $fillable = ['year', 'month', 'prepared_by', 'prepared_on', 'priorities', 'notes'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'prepared_on' => 'date',
            'priorities' => 'array',
            'notes' => 'array',
        ];
    }
}
