<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class CmsUser extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\CmsUserFactory> */
    use HasCompanyScope, HasFactory, HasUlids, Notifiable, SoftDeletes;

    protected $table = 'cms_users';

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role',
        'sso_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
