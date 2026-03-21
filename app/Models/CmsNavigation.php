<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CmsNavigation extends Model
{
    use HasCompanyScope, HasUlids;

    protected $table = 'cms_navigation';

    protected $fillable = [
        'company_id',
        'location',
        'items',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
