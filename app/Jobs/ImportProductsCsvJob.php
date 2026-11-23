<?php

namespace App\Jobs;

use App\Services\ProductCsvImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ImportProductsCsvJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;
    public int $tries = 1;

    private const REQUIRED_HEADERS = [
        'Barcode',
        'Item Name',
        'price',
        'Stock',
        'Status',
    ];

    public function __construct(
        public int $batchId
    ) {}

    public function handle(
        ProductCsvImportService $importer
    ): void {
        $batch = DB::table('product_import_batches')
            ->where('id', $this->batchId)
            ->first();

        if (!$batch) {
            throw new RuntimeException('Import batch not found.');
        }

        $path = Storage::disk('local')->path(
            $batch->file_path
        );

        $handle = fopen($path, 'rb');

        if (!$handle) {
            throw new RuntimeException(
                'Unable to open uploaded CSV.'
            );
        }

        try {
            $headers = fgetcsv(
                $handle,
                0,
                ',',
                '"',
                ''
            );

            if (!$headers) {
                throw new RuntimeException('CSV file is empty.');
            }

            $headers = array_map(
                fn ($header) => trim(
                    preg_replace('/^\xEF\xBB\xBF/', '', $header)
                ),
                $headers
            );

            $missing = array_diff(
                self::REQUIRED_HEADERS,
                $headers
            );

            if (!empty($missing)) {
                throw new RuntimeException(
                    'Missing CSV columns: ' .
                    implode(', ', $missing)
                );
            }

            $total = 0;

            while (
                fgetcsv($handle, 0, ',', '"', '') !== false
            ) {
                $total++;
            }

            rewind($handle);
            fgetcsv($handle, 0, ',', '"', '');

            DB::table('product_import_batches')
                ->where('id', $this->batchId)
                ->update([
                    'status' => 'processing',
                    'total_rows' => $total,
                    'updated_at' => now(),
                ]);

            $processed = 0;
            $created = 0;
            $skipped = 0;
            $failed = 0;
            $line = 1;

            while (
                ($values = fgetcsv(
                    $handle,
                    0,
                    ',',
                    '"',
                    ''
                )) !== false
            ) {
                $line++;

                if (
                    count($values) === 1 &&
                    trim($values[0] ?? '') === ''
                ) {
                    continue;
                }

                $processed++;

                try {
                    if (count($headers) !== count($values)) {
                        throw new RuntimeException(
                            'CSV column count mismatch.'
                        );
                    }

                    $row = array_combine(
                        $headers,
                        $values
                    );

                    $result = $importer->importRow($row);

                    if ($result === 'created') {
                        $created++;
                    } else {
                        $skipped++;
                    }
                } catch (Throwable $exception) {
                    $failed++;

                    Log::warning('Product CSV import row failed', [
                        'batch_id' => $this->batchId,
                        'line' => $line,
                        'error' => $exception->getMessage(),
                    ]);
                }

                if ($processed % 50 === 0) {
                    $this->updateProgress(
                        $processed,
                        $created,
                        $skipped,
                        $failed
                    );
                }
            }

            $this->updateProgress(
                $processed,
                $created,
                $skipped,
                $failed
            );

            DB::table('product_import_batches')
                ->where('id', $this->batchId)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);
        } finally {
            fclose($handle);
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::table('product_import_batches')
            ->where('id', $this->batchId)
            ->update([
                'status' => 'failed',
                'error_message' => substr(
                    $exception?->getMessage() ??
                    'Import failed.',
                    0,
                    2000
                ),
                'updated_at' => now(),
            ]);
    }

    private function updateProgress(
        int $processed,
        int $created,
        int $skipped,
        int $failed
    ): void {
        DB::table('product_import_batches')
            ->where('id', $this->batchId)
            ->update([
                'processed_rows' => $processed,
                'created_rows' => $created,
                'skipped_rows' => $skipped,
                'failed_rows' => $failed,
                'updated_at' => now(),
            ]);
    }
}
