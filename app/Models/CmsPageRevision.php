<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CmsPageRevision extends Model
{
    use HasCompanyScope, HasUlids;

    protected $table = 'cms_page_revisions';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'content_type',
        'content_id',
        'blocks',
        'summary',
        'created_by_type',
        'created_by_id',
        'ai_session_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
