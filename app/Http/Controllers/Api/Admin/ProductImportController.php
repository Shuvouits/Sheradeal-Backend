<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProductsCsvJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductImportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:20480',
            ],
        ]);

        $file = $request->file('file');

        $filename = Str::uuid() . '.csv';

        $path = $file->storeAs(
            'imports/products',
            $filename,
            'local'
        );

        if (!$path) {
            throw ValidationException::withMessages([
                'file' => ['Unable to save CSV file.'],
            ]);
        }

        $batchId = DB::table('product_import_batches')
            ->insertGetId([
                'filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => 'pending',
                'total_rows' => 0,
                'processed_rows' => 0,
                'created_rows' => 0,
                'skipped_rows' => 0,
                'failed_rows' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        ImportProductsCsvJob::dispatch($batchId);

        return response()->json([
            'success' => true,
            'message' => 'Product CSV import queued successfully.',
            'data' => [
                'batch_id' => $batchId,
                'status' => 'pending',
            ],
        ], 202);
    }

    public function show(int $batch): JsonResponse
    {
        $import = DB::table('product_import_batches')
            ->where('id', $batch)
            ->first();

        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Import batch not found.',
            ], 404);
        }

        $progress = $import->total_rows > 0
            ? round(
                ($import->processed_rows /
                    $import->total_rows) * 100,
                2
            )
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $import->id,
                'filename' => $import->filename,
                'status' => $import->status,
                'total_rows' => $import->total_rows,
                'processed_rows' => $import->processed_rows,
                'created_rows' => $import->created_rows,
                'skipped_rows' => $import->skipped_rows,
                'failed_rows' => $import->failed_rows,
                'progress' => $progress,
                'error_message' => $import->error_message,
            ],
        ]);
    }
}
