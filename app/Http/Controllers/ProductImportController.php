<?php

namespace App\Http\Controllers;

use App\Services\MohasagorImportService;
use Illuminate\Http\Request;

class ProductImportController extends Controller
{
    protected $importService;

    public function __construct(MohasagorImportService $importService)
    {
        $this->importService = $importService;
    }

    public function importProducts(Request $request)
    {
        $result = $this->importService->fetchAndProcessProducts();
        
        $status = $result['status'] ?? 200;
        unset($result['status']); // Remove internal status code from response body
        
        return response()->json($result, $status);
    }
}
