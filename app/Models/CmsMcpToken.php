<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CmsMcpToken extends Model
{
    use HasCompanyScope, HasUlids;

    protected $table = 'cms_mcp_tokens';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'name',
        'token_hash',
        'permissions',
        'last_used_at',
        'expires_at',
        'created_by',
        'created_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CmsUser::class, 'created_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isRevoked() && !$this->isExpired();
    }

    public function hasPermission(string $permission): bool
    {
        $perms = $this->permissions ?? [];

        return match ($permission) {
            'read' => in_array('read', $perms, true),
            'write' => in_array('write', $perms, true),
            'publish' => in_array('publish', $perms, true),
            default => false,
        };
    }
}
