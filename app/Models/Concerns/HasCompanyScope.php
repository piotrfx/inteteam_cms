<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

trait HasCompanyScope
{
    public static function bootHasCompanyScope(): void
    {
        static::addGlobalScope(new CompanyScope());

        static::creating(function (Model $model): void {
            if ($model->getAttribute('company_id') === null) {
                $company = app('current_company');

                if ($company instanceof Company) {
                    $model->setAttribute('company_id', $company->id);
                }
            }
        });
    }
}
