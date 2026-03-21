<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CmsPost extends Model
{
    /** @use HasFactory<\Database\Factories\CmsPostFactory> */
    use HasCompanyScope, HasFactory, HasUlids, SoftDeletes;

    protected $table = 'cms_posts';

    protected $fillable = [
        'company_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'blocks',
        'status',
        'published_at',
        'featured_image_path',
        'live_revision_id',
        'staged_revision_id',
        'seo_title',
        'seo_description',
        'seo_og_image_path',
        'seo_canonical_url',
        'seo_robots',
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(CmsUser::class, 'author_id');
    }

    public function liveRevision(): BelongsTo
    {
        return $this->belongsTo(CmsPageRevision::class, 'live_revision_id');
    }

    public function stagedRevision(): BelongsTo
    {
        return $this->belongsTo(CmsPageRevision::class, 'staged_revision_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
