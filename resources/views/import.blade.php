<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV ke Database Laravel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            color: #2c3e50;
            text-align: center;
        }

        h2 {
            color: #34495e;
            margin-top: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        button {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

        button:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }

        .history-container {
            margin-bottom: 40px;
        }

        .history-item {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .history-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .history-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-queued {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .history-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .detail-item {
            margin-bottom: 8px;
        }

        .detail-label {
            font-weight: bold;
            display: inline-block;
            width: 180px;
            color: #6c757d;
        }

        .new-import-section {
            background-color: #ffffff;
            border: 2px dashed #3498db;
            border-radius: 8px;
            padding: 30px;
        }

        #progress-container {
            width: 100%;
            background-color: #ecf0f1;
            border-radius: 4px;
            margin: 20px 0;
            overflow: hidden;
            display: none;
        }

        #progress-bar {
            height: 30px;
            background-color: #2ecc71;
            width: 0%;
            text-align: center;
            line-height: 30px;
            color: white;
            transition: width 0.5s ease;
        }

        #status {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-family: monospace;
        }

        .status-item {
            margin-bottom: 8px;
        }

        .status-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }

        #error {
            color: #e74c3c;
            background-color: #fadbd8;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            display: none;
        }

        .no-history {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>

<body>
    <h1>Import CSV ke Database Laravel</h1>

    <!-- History Section -->
    <div class="history-container">
        <h2>📊 Riwayat Import</h2>
        @if ($importHistory->count() > 0)
            @foreach ($importHistory as $history)
                <div class="history-item">
                    <div class="history-header">
                        <div class="history-title">
                            {{ $history->file_name }}
                        </div>
                        <div
                            class="history-status
                            @if ($history->status === 'completed' || $history->status === 'done') status-completed
                            @elseif($history->status === 'processing') status-processing
                            @elseif($history->status === 'queued') status-queued
                            @elseif($history->status === 'failed') status-failed @endif">
                            {{ strtoupper($history->status) }}
                        </div>
                    </div>
                    <div class="history-details">
                        <div class="detail-item">
                            <span class="detail-label">Waktu Import:</span>
                            {{ $history->created_at->format('d M Y, H:i:s') }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Baris:</span>
                            {{ number_format($history->total_rows) }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Baris Diproses:</span>
                            {{ number_format($history->inserted_rows) }}
                        </div>
                        @if ($history->execution_stats)
                            @php
                                $stats = json_decode($history->execution_stats, true);
                            @endphp
                            <div class="detail-item">
                                <span class="detail-label">Rata-rata per 1000 baris:</span>
                                @php
                                    if (!empty($stats['execution_times']) && is_array($stats['execution_times'])) {
                                        $avgTime =
                                            array_sum(array_column($stats['execution_times'], 'time')) /
                                            count($stats['execution_times']);
                                        echo round($avgTime, 4) . ' s';
                                    } else {
                                        echo '-';
                                    }
                                @endphp
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Waktu Eksekusi:</span>
                                {{ $stats['total_execution_time'] ?? '-' }}
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Puncak Memori:</span>
                                @php
                                    $memoryPeaks = [];
                                    if (!empty($stats['memory_usage']) && is_array($stats['memory_usage'])) {
                                        foreach ($stats['memory_usage'] as $m) {
                                            if (isset($m['memory_peak'])) {
                                                $value = floatval(str_replace(' MB', '', $m['memory_peak']));
                                                $memoryPeaks[] = $value;
                                            }
                                        }
                                    }
                                    echo !empty($memoryPeaks) ? round(max($memoryPeaks), 2) . ' MB' : '-';
                                @endphp
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="no-history">
                Belum ada riwayat import
            </div>
        @endif
    </div>

    <!-- New Import Section -->
    <div class="new-import-section">
        <h2>➕ Import Data Baru</h2>

        <div class="button-container">
            <button data-file="customers-500000.csv">Import 500,000 rows</button>
            <button data-file="customers-1000000.csv">Import 1,000,000 rows</button>
            <button data-file="customers-1500000.csv">Import 1,500,000 rows</button>
        </div>

        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
            <button id="truncate-btn" style="background-color: #e74c3c;">Clear Data</button>
        </div>

        <div id="progress-container">
            <div id="progress-bar">0%</div>
        </div>

        <div id="status">Pilih file untuk memulai proses import...</div>
        <div id="error"></div>
    </div>


    <script>
        document.querySelectorAll('button[data-file]').forEach(button => {
            button.addEventListener('click', async () => {
                const file = button.getAttribute('data-file');

                const response = await fetch('{{ route('import.start') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        file
                    })
                });

                const data = await response.json();

                if (data.import_id) {
                    document.getElementById('progress-container').style.display = 'block';
                    document.getElementById('error').style.display = 'none';
                    pollStatus(data.import_id);
                } else if (data.error) {
                    document.getElementById('error').innerText = data.error;
                    document.getElementById('error').style.display = 'block';
                }
            });
        });

        function pollStatus(importId) {
            const statusEl = document.getElementById('status');
            const bar = document.getElementById('progress-bar');

            const interval = setInterval(async () => {
                const res = await fetch(`/import/status/${importId}`);
                const data = await res.json();

                if (data.error) {
                    clearInterval(interval);
                    document.getElementById('error').innerText = data.error;
                    document.getElementById('error').style.display = 'block';
                    return;
                }

                const percent = Math.floor((data.processed / data.total) * 100);
                bar.style.width = percent + '%';
                bar.innerText = percent + '%';

                statusEl.innerHTML = `
    <div class="status-item"><span class="status-label">Status:</span> ${data.status || '-'}</div>
    <div class="status-item"><span class="status-label">Diproses:</span> ${data.processed || '-'} / ${data.total || '-'}</div>
    <div class="status-item"><span class="status-label">Waktu Eksekusi:</span> ${data.stats?.total_time || '-'}</div>
    <div class="status-item"><span class="status-label">Rata-rata per 1000 baris:</span> ${data.stats?.average_time_per_100_rows || '-'}</div>
    <div class="status-item"><span class="status-label">Puncak Memori:</span> ${data.stats?.peak_memory || '-'}</div>
`;

                if (data.status === 'done' || data.status === 'failed' || data.status === 'completed') {
                    clearInterval(interval);
                    // Reload halaman setelah 2 detik untuk menampilkan history terbaru
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            }, 1000);
        }

        document.getElementById('truncate-btn').addEventListener('click', async () => {
            if (!confirm('Apakah kamu yakin ingin menghapus semua data? Ini tidak bisa dibatalkan!')) return;

            const response = await fetch('{{ route('import.truncate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                }
            });

            const data = await response.json();

            if (data.success) {
                alert('Semua data berhasil dihapus!');
                window.location.reload();
            } else if (data.error) {
                document.getElementById('error').innerText = data.error;
                document.getElementById('error').style.display = 'block';
            }
        });
    </script>
</body>

</html>
