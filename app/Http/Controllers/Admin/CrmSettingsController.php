<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\CrmConnectionException;
use App\Exceptions\NoCrmConnectionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCrmSettingsRequest;
use App\Models\Company;
use App\Services\CrmApiClientFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

final class CrmSettingsController extends Controller
{
    public function __construct(private readonly CrmApiClientFactory $crmFactory) {}

    public function show(): Response
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);

        /** @var Company $company */
        $company = app('current_company');

        return Inertia::render('Admin/Settings/CrmIntegration', [
            'crm' => [
                'crm_base_url' => $company->crm_base_url,
                'crm_company_id' => $company->crm_company_id,
                'has_api_key' => filled($company->crm_api_key),
                'is_connected' => $company->hasCrmConnection(),
            ],
        ]);
    }

    public function update(UpdateCrmSettingsRequest $request): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);

        /** @var Company $company */
        $company = app('current_company');

        $data = $request->validated();

        // Encrypt the API key if provided; preserve existing if blank
        if (filled($data['crm_api_key'] ?? null) && is_string($data['crm_api_key'])) {
            $data['crm_api_key'] = Crypt::encryptString($data['crm_api_key']);
        } else {
            unset($data['crm_api_key']);
        }

        $company->update($data);

        return back()->with(['alert' => 'CRM settings saved.', 'type' => 'success']);
    }

    public function testConnection(): JsonResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);

        /** @var Company $company */
        $company = app('current_company');

        try {
            $client = $this->crmFactory->forCompany($company);
            $connected = $client->testConnection();

            if (!$connected) {
                return response()->json(['success' => false, 'message' => 'Connection failed. Check your credentials.'], 422);
            }

            return response()->json(['success' => true, 'message' => 'Connected successfully.']);
        } catch (NoCrmConnectionException) {
            return response()->json(['success' => false, 'message' => 'CRM credentials are not configured.'], 422);
        } catch (CrmConnectionException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
