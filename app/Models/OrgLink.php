<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrgLinkStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgLink extends Model
{
    protected $fillable = ['from_node_id', 'to_node_id', 'style'];

    protected function casts(): array
    {
        return ['style' => OrgLinkStyle::class];
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(OrgNode::class, 'from_node_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(OrgNode::class, 'to_node_id');
    }
}
