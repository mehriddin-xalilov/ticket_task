<?php

namespace Modules\FileManager\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fileable extends Model
{
    protected $table = 'fileables';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'file_id',
        'fileable_id',
        'fileable_type',
        'fileable_key',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public static function showByKey(string $key)
    {
        return self::where('fileable_key', $key)
            ->with('file')
            ->get()
            ->pluck('file');
    }

}
