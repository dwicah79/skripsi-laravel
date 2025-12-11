<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $table = 'import_logs';

    protected $fillable = [
        'file_name',
        'total_rows',
        'inserted_rows',
        'chunk_size',
        'status',
        'execution_stats',
        'duration'
    ];
}
