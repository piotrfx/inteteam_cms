<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Exceptions\CrmConnectionException;
use App\Exceptions\NoCrmConnectionException;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CrmApiClientFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CrmFormController extends Controller
{
    public function __construct(private readonly CrmApiClientFactory $crmFactory) {}

    public function submit(Request $request, string $companySlug, string $slug): RedirectResponse
    {
        $company = Company::where('slug', $companySlug)->where('is_active', true)->firstOrFail();

        try {
            $client = $this->crmFactory->forCompany($company);
            $client->submitForm($slug, $request->except(['_token', '_method']));
        } catch (NoCrmConnectionException|CrmConnectionException) {
            return back()->with(['alert' => 'Form submission failed. Please try again later.', 'type' => 'error']);
        }

        return back()->with(['alert' => 'Thank you, your message has been sent.', 'type' => 'success']);
    }
}
