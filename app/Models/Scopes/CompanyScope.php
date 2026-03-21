<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $company = app()->bound('current_company') ? app('current_company') : null;

        if ($company instanceof Company) {
            $builder->where($model->getTable() . '.company_id', $company->id);
        }
    }
}
