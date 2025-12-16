<?php

namespace Modules\FileManager\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\FileManager\App\Http\Repositories\FileRepository;

class File extends Model
{
    use SoftDeletes;

    protected $casts = [
        'updated_at' => 'timestamp',
        'created_at' => 'timestamp'
    ];
    protected $table = 'files';
    protected $appends = [
        'thumbnails',
    ];
    protected $fillable = [
        'title',
        'description',
        'slug',
        'ext',
        'file',
        'folder',
        'domain',
        'path',
        'user_id',
        'size',
        'deleted_at',
        'user_id',
    ];

    protected $hidden = [
        'domain',
        'created_at',
        'deleted_at',
        'updated_at',
        'path',
        'description'
    ];

    public function getUrlAttribute(): string
    {
        return trim($this->domain, '/') . '/' . $this->folder . $this->file;
    }

    public function getThumbnailsAttribute()
    {
        $thumbsImages = config('filemanager.thumbs');
        foreach ($thumbsImages as &$thumbsImage) {
            $slug = $thumbsImage['slug'];
            if (!in_array($this->ext, $this->getImageExtensionsAttribute())) {
                $newFileDist = $this->getUrlAttribute();
            } else {
                $newFileDist = $this->domain . "/" . $this->folder . $this->slug;
                if ($slug != 'original') {
                    $newFileDist .= "_" . $slug;
                }
                $newFileDist .= "." . $this->ext;
            }
            $thumbsImage['src'] = $newFileDist;
            unset($thumbsImage["w"]);
            unset($thumbsImage["h"]);
            unset($thumbsImage["q"]);
        }
        return $thumbsImages;
    }

    public function getImageExtensionsAttribute()
    {
        return config('filemanager.images_ext');
    }

    public function getDist()
    {
        return $this->folder . '/' . $this->file;
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

