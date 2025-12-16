<?php

namespace Modules\FileManager\App\DTO;

class UploadFileDto
{
    public string $title;
    public string $description;
    public string $slug;
    public string $ext;
    public string $file;
    public float $size;
    public string $folder;
    public string $domain;
    public ?int $user_id;
    public string $path;
    public string $file_folder;

    public function toArray(): array
    {
        return array_map(function ($value) {
            return $value;
        }, get_object_vars($this));
    }
}
