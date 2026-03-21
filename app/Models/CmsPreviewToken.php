<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CmsPreviewToken extends Model
{
    use HasCompanyScope, HasUlids;

    protected $table = 'cms_preview_tokens';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'content_type',
        'content_id',
        'revision_id',
        'token',
        'expires_at',
        'viewed_at',
        'created_by_type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'viewed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(CmsPageRevision::class, 'revision_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
