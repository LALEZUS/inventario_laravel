<?php

namespace App\Http\Controllers;

use App\Models\Cellphone;
use App\Models\AccountCredential;
use App\Models\EnterpriseNetwork;
use App\Models\HardwareAsset;
use App\Models\MicrosoftEmail;
use App\Models\OfficeEmail;
use App\Models\OutlookAccount;
use App\Models\SoftwareLicense;
use App\Models\WatchguardUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensitiveValueController extends Controller
{
    public function computer(Request $request, HardwareAsset $computer): JsonResponse
    {
        $this->authorize('viewSensitive', $computer);

        return $this->noStore(['admin_password' => $computer->admin_password]);
    }

    public function cellphone(Request $request, Cellphone $cellphone): JsonResponse
    {
        $this->authorize('viewSensitive', $cellphone);

        return $this->noStore([
            'password' => $cellphone->getRawOriginal('password'),
            'updated_password' => $cellphone->getRawOriginal('updated_password'),
            'app_lock_password' => $cellphone->getRawOriginal('app_lock_password'),
            'app_lock_answer' => $cellphone->getRawOriginal('app_lock_answer'),
        ]);
    }

    public function account(Request $request, AccountCredential $accountCredential): JsonResponse
    {
        $this->authorize('viewSensitive', $accountCredential);
        return $this->noStore(['password' => $accountCredential->getRawOriginal('password')]);
    }

    public function outlook(Request $request, OutlookAccount $outlookAccount): JsonResponse
    {
        $this->authorize('viewSensitive', $outlookAccount);
        return $this->noStore(['password' => $outlookAccount->getRawOriginal('contraseña')]);
    }

    public function license(Request $request, SoftwareLicense $softwareLicense): JsonResponse
    {
        $this->authorize('viewSensitive', $softwareLicense);
        return $this->noStore(['key_value' => $softwareLicense->getRawOriginal('key_value'), 'password' => $softwareLicense->getRawOriginal('password')]);
    }

    public function microsoftEmail(Request $request, MicrosoftEmail $microsoftEmail): JsonResponse
    {
        $this->authorize('viewSensitive', $microsoftEmail);
        return $this->noStore(['password' => $microsoftEmail->getRawOriginal('password')]);
    }

    public function officeEmail(Request $request, OfficeEmail $officeEmail): JsonResponse
    {
        $this->authorize('viewSensitive', $officeEmail);
        return $this->noStore(['password' => $officeEmail->getRawOriginal('password')]);
    }

    public function enterpriseNetwork(Request $request, EnterpriseNetwork $enterpriseNetwork): JsonResponse
    {
        $this->authorize('viewSensitive', $enterpriseNetwork);
        return $this->noStore(['password' => $enterpriseNetwork->getRawOriginal('password')]);
    }

    public function watchguard(Request $request, WatchguardUser $watchguardUser): JsonResponse
    {
        $this->authorize('viewSensitive', $watchguardUser);
        return $this->noStore(['password' => $watchguardUser->getRawOriginal('password')]);
    }

    private function noStore(array $values): JsonResponse
    {
        return response()->json($values)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
