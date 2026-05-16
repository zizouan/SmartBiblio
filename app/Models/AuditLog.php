<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuid;

    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'ip_address'];
}
