<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CsvImportService;
use App\Services\BetImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BetImportWizardController extends Controller
{
    public function __construct(
        private CsvImportService $csvImportService,
        private BetImportService $betImportService
    ) {}

    /**
     * Show import wizard
     */
    public function index()
    {
        return Inertia::render('admin/BetImport/Index', [
            'columnRequirements' => $this->csvImportService->getColumnRequirements(),
            'validationRules' => $this->csvImportService->getValidationRules(),
        ]);
    }

    /**
     * Upload and analyze CSV file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        try {
            // Store file temporarily
            $path = $request->file('file')->store('temp/imports');
            $fullPath = Storage::disk('local')->path($path);
            
            // Analyze CSV
            $analysis = $this->csvImportService->analyzeCsv($fullPath);
            
            if (!$analysis['success']) {
                Storage::delete($path);
                return response()->json($analysis, 422);
            }
            
            // Store file info in session
            session([
                'import_file' => $path,
                'import_analysis' => $analysis,
            ]);
            
            return response()->json([
                'success' => true,
                'file_id' => encrypt($path),
                'analysis' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate import with column mappings
     */
    public function validate(Request $request)
    {
        $request->validate([
            'file_id' => 'required|string',
            'mappings' => 'required|array',
            'static_values' => 'nullable|array',
        ]);

        try {
            $path = decrypt($request->input('file_id'));
            
            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import file not found. Please upload again.',
                ], 404);
            }
            
            $fullPath = Storage::disk('local')->path($path);
            $validation = $this->csvImportService->validateImport(
                $fullPath,
                $request->input('mappings'),
                $request->input('static_values', [])
            );
            
            // Store validation results in session
            session([
                'import_validation' => $validation,
                'import_mappings' => $request->input('mappings'),
                'import_static_values' => $request->input('static_values', []),
            ]);
            
            return response()->json($validation);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute import
     */
    public function import(Request $request)
    {
        $request->validate([
            'file_id' => 'required|string',
            'mappings' => 'required|array',
            'static_values' => 'nullable|array',
            'skip_errors' => 'boolean',
        ]);

        try {
            $path = decrypt($request->input('file_id'));
            
            if (!Storage::exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import file not found. Please upload again.',
                ], 404);
            }
            
            $fullPath = Storage::disk('local')->path($path);
            
            // Generate unique import ID
            $importId = uniqid('import_', true);
            
            // Get row count from analysis
            $analysis = $this->csvImportService->analyzeCsv($fullPath);
            $rowCount = $analysis['total_rows'];
            
            // Determine if we should use queue (more than 100 rows)
            if ($rowCount > 100) {
                // Move file to permanent location for queue processing
                $newPath = 'imports/' . basename($path);
                Storage::move($path, $newPath);
                
                // Dispatch job to queue
                \App\Jobs\ProcessBetImport::dispatch(
                    $newPath,
                    $request->input('mappings'),
                    $importId,
                    auth()->id()
                );
                
                // Initialize progress tracking
                \Illuminate\Support\Facades\Cache::put("import_progress_{$importId}", [
                    'status' => 'queued',
                    'total' => $rowCount,
                    'processed' => 0,
                    'success' => 0,
                    'errors' => 0,
                    'percentage' => 0,
                    'queued_at' => now()->toIso8601String()
                ], now()->addHours(24));
                
                // Clear session
                session()->forget(['import_file', 'import_analysis', 'import_validation', 'import_mappings']);
                
                return response()->json([
                    'success' => true,
                    'import_id' => $importId,
                    'queued' => true,
                    'message' => "Import queued for processing. {$rowCount} rows will be imported in the background."
                ]);
            } else {
                // Process immediately for small files
                $this->betImportService->setColumnMappings($request->input('mappings'));
                $this->betImportService->setStaticValues($request->input('static_values', []));
                $result = $this->betImportService->importFromCsv($fullPath);
                
                // Store results in cache for consistency
                \Illuminate\Support\Facades\Cache::put("import_progress_{$importId}", [
                    'status' => 'completed',
                    'total' => $result['processed'] ?? $rowCount,
                    'processed' => $result['processed'] ?? $rowCount,
                    'success' => $result['successCount']['bets'] ?? 0,
                    'errors' => count($result['errors'] ?? []),
                    'percentage' => 100,
                    'error_log' => array_slice($result['errors'] ?? [], 0, 100),
                    'completed_at' => now()->toIso8601String()
                ], now()->addHours(24));
                
                // Clean up temp file
                Storage::delete($path);
                session()->forget(['import_file', 'import_analysis', 'import_validation', 'import_mappings']);
                
                return response()->json([
                    'success' => true,
                    'import_id' => $importId,
                    'queued' => false,
                    'result' => $result,
                    'message' => "Import completed immediately."
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download sample CSV template
     */
    public function downloadTemplate()
    {
        try {
            $filePath = $this->csvImportService->generateSampleCsv();
            
            return response()->download($filePath)->deleteFileAfterSend();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get import progress
     */
    public function progress(Request $request)
    {
        $request->validate([
            'import_id' => 'required|string'
        ]);
        
        $importId = $request->input('import_id');
        $progress = \Illuminate\Support\Facades\Cache::get("import_progress_{$importId}");
        
        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Import not found or expired'
            ], 404);
        }
        
        // Check if error report is available
        $errorReportPath = \Illuminate\Support\Facades\Cache::get("import_error_report_{$importId}");
        
        return response()->json([
            'success' => true,
            'progress' => $progress,
            'error_report_available' => !empty($errorReportPath)
        ]);
    }
    
    /**
     * Download import error report
     */
    public function downloadErrorReport(Request $request)
    {
        $request->validate([
            'import_id' => 'required|string'
        ]);
        
        $importId = $request->input('import_id');
        $reportPath = \Illuminate\Support\Facades\Cache::get("import_error_report_{$importId}");
        
        if (!$reportPath || !Storage::disk('local')->exists($reportPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Error report not found'
            ], 404);
        }
        
        return Storage::disk('local')->download($reportPath, "import_errors_{$importId}.csv");
    }
}