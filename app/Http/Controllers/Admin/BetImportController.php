<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LegacyBetImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BetImportController extends Controller
{
    public function __construct(
        private LegacyBetImportService $importService
    ) {}

    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $result = $this->importService->importFromCsv(
            $request->file('csv')->getRealPath()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function getSampleFormat(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'format' => $this->importService->getSampleCsvFormat(),
            'message' => 'Use this format for CSV imports',
        ]);
    }
}
