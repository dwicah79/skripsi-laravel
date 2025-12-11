<?php
namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\ImportData;
use App\Jobs\ImportCsvChunk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function index()
    {
        $importHistory = ImportLog::orderBy('created_at', 'desc')->get();
        return view('import', compact('importHistory'));
    }

    public function startImport(Request $request)
    {
        $request->validate([
            'file' => 'required|string',
            'chunk_size' => 'nullable|integer|min:100|max:10000'
        ]);
        ini_set('memory_limit', '2048M');

        $filename = basename($request->input('file')); // pakai basename supaya aman
        $filePath = storage_path('app/imports/' . $filename);
        $chunkSize = $request->input('chunk_size', 1000); // default 1000 jika tidak diisi

        // Validasi file ada di storage
        if (!Storage::exists('imports/' . $filename)) {
            return response()->json([
                'error' => 'File not found in imports directory.',
                'available_files' => Storage::files('imports')
            ], 404);
        }

        // Validasi ekstensi file CSV
        if (!preg_match('/\.csv$/i', $filename)) {
            return response()->json(['error' => 'File must be a CSV.'], 400);
        }

        try {
            // Hitung baris minus header
            $rows = count(file($filePath)) - 1;
            // Simpan log import
            $log = ImportLog::create([
                'file_name' => $filename,
                'total_rows' => $rows,
                'inserted_rows' => 0,
                'chunk_size' => $chunkSize,
                'status' => 'queued'
            ]);

            // Dispatch job untuk proses import
            ImportCsvChunk::dispatch($log->fresh());

            return response()->json([
                'import_id' => $log->id,
                'message' => 'Import process started successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to start import: ' . $e->getMessage()
            ], 500);
        }
    }


    public function status($id)
    {
        $log = ImportLog::find($id);
        if (!$log) {
            return response()->json(['error' => 'Import not found.'], 404);
        }

        $response = [
            'processed' => $log->inserted_rows,
            'total' => $log->total_rows,
            'status' => $log->status,
        ];

        if ($log->execution_stats) {
            $stats = json_decode($log->execution_stats, true);

            // Ambil nilai numerik dari string '123.45 MB' -> 123.45
            $memoryPeaks = [];
            if (!empty($stats['memory_usage']) && is_array($stats['memory_usage'])) {
                foreach ($stats['memory_usage'] as $m) {
                    if (isset($m['memory_peak'])) {
                        $value = floatval(str_replace(' MB', '', $m['memory_peak']));
                        $memoryPeaks[] = $value;
                    }
                }
            }

            $response['stats'] = [
                'total_time' => $stats['total_execution_time'] ?? '-',
                'peak_memory' => !empty($memoryPeaks)
                    ? round(max($memoryPeaks), 2) . ' MB'
                    : '-',
                'average_time_per_100_rows' => (!empty($stats['execution_times']) && is_array($stats['execution_times']))
                    ? round(array_sum(array_column($stats['execution_times'], 'time')) / count($stats['execution_times']), 4) . ' s'
                    : '-',
                'memory_usage' => '-',
            ];
        }


        return response()->json($response);
    }

    public function truncate()
    {
        try {
            DB::table('import_data')->truncate();
            DB::table('import_logs')->truncate();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }
}
