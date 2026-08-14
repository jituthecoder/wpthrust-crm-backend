<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BusinessImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BusinessImportController extends Controller
{
    protected BusinessImportService $importService;

    public function __construct(BusinessImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Import Businesses CSV
     */
    public function import(Request $request)
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        try {

            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimes:csv,txt',
                    'max:51200', // 50MB
                ],
            ]);

            $result = $this->importService->import($request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Businesses imported successfully.',
                'data' => $result,
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);

        }
    }
}