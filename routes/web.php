<?php

use App\Http\Controllers\AuthController;
use App\Models\ImportLog;
use App\Jobs\ImportCsvChunk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ImportController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/import', [ImportController::class, 'index']);
Route::post('/import/start', [ImportController::class, 'startImport'])->name('import.start');
Route::get('/import/status/{id}', [ImportController::class, 'status']);
Route::post('/import/truncate', [ImportController::class, 'truncate'])->name('import.truncate');


Route::get('/test-csv', function () {
    $filePath = "imports/customers-500000.csv";
    if (Storage::exists($filePath)) {
        $content = Storage::get($filePath);
        $lines = explode(PHP_EOL, $content);
        $header = str_getcsv($lines[0]);
        $firstRow = str_getcsv($lines[1]);

        return response()->json([
            'header' => $header,
            'first_row' => $firstRow,
            'combined' => array_combine($header, $firstRow)
        ]);
    }
    return response()->json(['error' => 'File not found']);
});


Route::get('/verify-table', function () {
    try {
        $columns = DB::select("DESCRIBE import_data");
        return response()->json([
            'table_structure' => $columns,
            'import_logs_count' => DB::table('import_logs')->count(),
            'import_data_count' => DB::table('import_data')->count()
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

Route::get('/test-import-small', function () {
    try {
        // Ambil hanya 10 baris pertama untuk testing
        $filePath = "imports/customers-500000.csv";
        $content = Storage::get($filePath);
        $lines = explode(PHP_EOL, $content);

        // Ambil header + 10 baris data
        $testLines = array_slice($lines, 0, 11);
        $testContent = implode(PHP_EOL, $testLines);

        // Parse data
        $testRows = [];
        $header = str_getcsv(array_shift($testLines));

        foreach ($testLines as $line) {
            if (trim($line) === '')
                continue;
            $data = str_getcsv($line);
            if (count($data) == count($header)) {
                $testRows[] = array_combine($header, $data);
            }
        }

        // Buat import log
        $importLog = ImportLog::create([
            'total_rows' => count($testRows),
            'inserted_rows' => 0,
            'status' => 'processing',
            'duration' => 0,
            'memory' => null,
        ]);

        // Dispatch job dengan data kecil
        ImportCsvChunk::dispatch($testRows, $importLog->id);

        return response()->json([
            'message' => 'Test import started',
            'import_log_id' => $importLog->id,
            'test_rows' => count($testRows),
            'sample_data' => $testRows[0] ?? null
        ]);

    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

Route::get('/import-large-csv', function () {
    try {
        $filePath = storage_path('app/imports/customers-500000.csv');

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File tidak ditemukan.'], 404);
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return response()->json(['error' => 'Gagal membuka file.'], 500);
        }

        $header = fgetcsv($handle); // Ambil header baris pertama
        $chunk = [];
        $chunkSize = 1000;
        $rowCount = 0;

        $importLog = ImportLog::create([
            'total_rows' => 0,
            'inserted_rows' => 0,
            'status' => 'processing',
            'duration' => 0,
            'memory' => null,
        ]);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($header)) {
                continue; // Skip baris tidak valid
            }

            $chunk[] = array_combine($header, $data);
            $rowCount++;

            if (count($chunk) >= $chunkSize) {
                ImportCsvChunk::dispatch($chunk, $importLog->id);
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            ImportCsvChunk::dispatch($chunk, $importLog->id);
        }

        fclose($handle);

        // Update jumlah total baris di log
        $importLog->update(['total_rows' => $rowCount]);

        return response()->json([
            'message' => 'CSV import dispatched in chunks',
            'import_log_id' => $importLog->id,
            'total_rows' => $rowCount
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});



Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/', function () {
        return view('welcome');
    });
});
