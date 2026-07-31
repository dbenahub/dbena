<?php

declare(strict_types=1);

namespace App\Enums;

enum OwnerStatus: string
{
    case Active = 'active';
    case PendingApproval = 'pending_approval';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('admin.owner_status.'.$this->value);
    }
}
