<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NoCrmConnectionException;
use App\Models\Company;
use Illuminate\Support\Facades\Crypt;

final class CrmApiClientFactory
{
    public function forCompany(Company $company): CrmApiClient
    {
        if (!$company->hasCrmConnection()) {
            throw new NoCrmConnectionException;
        }

        return new CrmApiClient(
            baseUrl:   (string) $company->crm_base_url,
            apiKey:    Crypt::decryptString((string) $company->crm_api_key),
            companyId: (string) $company->crm_company_id,
        );
    }
}
