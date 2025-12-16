<?php

namespace App\Helpers\Traits;

use Modules\FileManager\App\Models\File;
use Modules\FileManager\App\Models\Fileable;
use Str;

trait FileableTrait
{
    public function __call($method, $parameters)
    {
        if (property_exists($this, 'fileableAttributes') && $this->checkCallingMethod($method)) {
            if (Str::endsWith($method, 's')) {
                return $this->morphToMany(File::class, 'fileable', 'fileables', 'fileable_id', 'file_id')
                    ->where('fileable_key', $method)
                    ->where('fileable_type', $this->getMorphClass())
                    ->withPivot('fileable_key');
            } else {
                return $this->hasOneThrough(File::class, Fileable::class, 'fileable_id', 'id', 'id', 'file_id')
                    ->where('fileable_key', $method)
                    ->where('fileable_type', $this->getMorphClass());
            }
        }

        return parent::__call($method, $parameters);
    }

    protected static function bootFileableTrait(): void
    {
        self::saved(function ($model) {
            // Request'dagi barcha ma'lumotlarni tekshiramiz
            $requestData = request()->all();

            if (is_array($model->fileableAttributes)) {
                foreach ($model->fileableAttributes as $attribute) {
                    // Request'da attribute borligini tekshiramiz
                    if (array_key_exists($attribute, $requestData)) {
                        $model->deleteRelation($model->getMorphClass(), $attribute, $model->id);

                        if (is_null($requestData[$attribute])) {
                            continue;
                        }

                        $ids = is_array($requestData[$attribute])
                            ? $requestData[$attribute]
                            : [$requestData[$attribute]];

                        foreach ($ids as $fileId) {
                            Fileable::create([
                                'fileable_id' => $model->id,
                                'fileable_type' => $model->getMorphClass(),
                                'fileable_key' => $attribute,
                                'file_id' => $fileId,
                            ]);
                        }
                    }
                }
            } else {
                $attribute = $model->fileableAttributes;
                if (array_key_exists($attribute, $requestData)) {
                    $model->deleteRelation($model->getMorphClass(), $attribute, $model->id);

                    if (is_null($requestData[$attribute])) {
                        return;
                    }

                    $ids = is_array($requestData[$attribute])
                        ? $requestData[$attribute]
                        : [$requestData[$attribute]];

                    foreach ($ids as $fileId) {
                        Fileable::create([
                            'fileable_id' => $model->id,
                            'fileable_type' => $model->getMorphClass(),
                            'fileable_key' => $attribute,
                            'file_id' => $fileId,
                        ]);
                    }
                }
            }
        });

        static::deleted(function ($model) {
            if (!property_exists($model, 'fileableAttributes')) {
                return;
            }

            $fileables = Fileable::where([
                'fileable_type' => $model->getMorphClass(),
                'fileable_id' => $model->id
            ])->get();

            foreach ($fileables as $fileable) {
                $file = $fileable->file;
                if ($file) {
                    $otherUsages = Fileable::where('file_id', $file->id)
                        ->where('id', '!=', $fileable->id)
                        ->exists();

                    if (!$otherUsages) {
                        $model->deletePhysicalFile($file);
                        $file->delete();
                    }
                }
            }

            Fileable::where([
                'fileable_type' => $model->getMorphClass(),
                'fileable_id' => $model->id
            ])->delete();
        });
    }

    protected function checkCallingMethod($method): bool
    {
        if (is_array($this->fileableAttributes) && in_array($method, $this->fileableAttributes)) {
            return true;
        } elseif ($method === $this->fileableAttributes) {
            return true;
        }
        return false;
    }

    public function deleteRelation($class, $attribute, $relationId): void
    {
        Fileable::where([
            'fileable_key' => $attribute,
            'fileable_type' => $class,
            'fileable_id' => $relationId
        ])->delete();
    }

    public function deleteFilesByAttribute($attribute): void
    {
        $this->deleteRelation($this->getMorphClass(), $attribute, $this->id);
    }

    public function deleteAllFiles(): void
    {
        if (is_array($this->fileableAttributes)) {
            foreach ($this->fileableAttributes as $attribute) {
                $this->deleteRelation($this->getMorphClass(), $attribute, $this->id);
            }
        } else {
            $this->deleteRelation($this->getMorphClass(), $this->fileableAttributes, $this->id);
        }
    }

    /**
     * Faylni va uning barcha thumbnaillarini fizik diskdan o'chirish
     */
    protected function deletePhysicalFile($file): void
    {
        if (file_exists($file->path)) {
            unlink($file->path);
        }
        if (in_array($file->ext, $file->getImageExtensionsAttribute())) {
            $thumbsImages = config('filemanager.thumbs', []);

            foreach ($thumbsImages as $thumbsImage) {
                $slug = $thumbsImage['slug'];
                $thumbnailPath = base_path('static/') . $file->folder . $file->slug . '_' . $slug . '.' . $file->ext;

                if (file_exists($thumbnailPath)) {
                    unlink($thumbnailPath);
                }
            }
        }
    }
}
