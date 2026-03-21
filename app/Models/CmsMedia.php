<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CmsMedia extends Model
{
    /** @use HasFactory<\Database\Factories\CmsMediaFactory> */
    use HasCompanyScope, HasFactory, HasUlids, SoftDeletes;

    protected $table = 'cms_media';

    protected $fillable = [
        'company_id',
        'uploaded_by',
        'filename',
        'path',
        'disk',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'alt_text',
        'caption',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(CmsUser::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
