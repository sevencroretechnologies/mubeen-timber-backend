<?php

namespace App\Http\Controllers\Api\crm;

use App\Enums\Gender;
use App\Enums\QualificationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class EnumController extends Controller
{
    public function qualificationStatuses(): JsonResponse
    {
        return response()->json(QualificationStatus::options());
    }

    public function genders(): JsonResponse
    {
        return response()->json(Gender::options());
    }
}
