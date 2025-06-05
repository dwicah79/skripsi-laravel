<?php

namespace App\Jobs;

use App\Models\ImportLog;
use App\Models\ImportData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportCsvChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $log;

    public function __construct(ImportLog $log)
    {
        $this->log = $log;
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $memoryStats = [];
        $executionTimes = [];

        try {
            $log = ImportLog::findOrFail($this->log->id);

            if (empty($log->file_name)) {
                throw new \Exception("Nama file kosong");
            }

            $filename = basename($log->file_name);
            $path = storage_path('app/imports/' . $filename);

            Log::info("Membuka file: " . $path);
            if (!file_exists($path)) {
                throw new \Exception("File tidak ditemukan di: " . $path);
            }
            if (is_dir($path)) {
                throw new \Exception("Path mengarah ke direktori bukan file");
            }

            $handle = fopen($path, 'r');
            if (!$handle) {
                throw new \Exception("Gagal membuka file");
            }

            fgetcsv($handle); // skip header
            $log->status = 'processing';
            $log->save();

            $batch = [];
            $batchSize = 1000;
            $processed = 0;

            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) <= 11)
                    continue;

                $batch[] = [
                    'index' => $data[0],
                    'customer_id' => $data[1],
                    'first_name' => $data[2],
                    'last_name' => $data[3],
                    'company' => $data[4],
                    'city' => $data[5],
                    'country' => $data[6],
                    'phone1' => $data[7],
                    'phone2' => $data[8],
                    'email' => $data[9],
                    'subscription_date' => $data[10],
                    'website' => $data[11],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $processed++;

                if ($processed % $batchSize === 0) {
                    $chunkStart = microtime(true);
                    ImportData::insert($batch);
                    $chunkTime = microtime(true) - $chunkStart;

                    $executionTimes[] = ['time' => $chunkTime];
                    $memoryStats[] = [
                        'memory_peak' => memory_get_peak_usage(),
                        'memory_now' => memory_get_usage(),
                    ];

                    $batch = [];
                    $log->inserted_rows = $processed;
                    $log->save();
                }
            }

            if (!empty($batch)) {
                ImportData::insert($batch);
            }

            fclose($handle);

            $log->inserted_rows = $processed;
            $log->status = 'completed';

            $totalTime = microtime(true) - $startTime;
            $avgPer100 = $processed > 0 ? round($totalTime / ($processed / 100), 4) : 0;

            $log->execution_stats = json_encode([
                'total_execution_time' => round($totalTime, 2) . ' seconds',
                'average_per_100' => $avgPer100 . ' seconds',
                'execution_times' => array_map(function ($et) {
                    return ['time' => round($et['time'], 4)];
                }, $executionTimes),
                'memory_usage' => array_map(function ($m) {
                    return [
                        'memory_peak' => round($m['memory_peak'] / 1048576, 2) . ' MB',
                        'memory_now' => round($m['memory_now'] / 1048576, 2) . ' MB'
                    ];
                }, $memoryStats),
            ]);

            $log->save();

        } catch (\Exception $e) {
            Log::error("ImportCsvChunk Error: " . $e->getMessage());

            $log = ImportLog::find($this->log->id);
            if ($log) {
                $log->status = 'failed';
                $log->save();
            }
        }
    }
}
