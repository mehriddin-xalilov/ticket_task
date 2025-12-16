<?php

namespace Modules\FileManager\App\Http\Repositories;

use App\Helpers\Roles;
use App\Helpers\Traits\QueryBuilderTrait;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Mockery\Exception;
use Modules\FileManager\App\DTO\UploadFileDto;
use Modules\FileManager\App\Models\File;
use Modules\FileManager\App\Models\Fileable;
use Throwable;
use function Symfony\Component\String\s;

class FileRepository
{
    use QueryBuilderTrait;

    /**
     * @throws Throwable
     */
    public function upload(Request $request, $from_terminal = false): JsonResponse|array
    {
        $files = $request->file('files');
        $response = [];
        $errors = [];

        if (is_array($files)) {
            foreach ($files as $file) {
                $_file = $this->storeFile($file);
                if ($_file instanceof File) {
                    $response[] = $_file;
                } else {
                    $errors[] = $_file;
                }
            }
        } else {
            $_file = $this->storeFile($files);
            if ($_file instanceof File) {
                $response[] = $_file;
            } else {
                $errors[] = $_file;
            }
        }

        if ($from_terminal) {
            return $response;
        }
        return empty($errors)
            ? okResponse($response)
            : errorResponse(status: 422, data: $errors);
    }

    /**
     * @throws Throwable
     */
    public function storeFile(UploadedFile $uploadedFile)
    {
        \DB::beginTransaction();
        try {
            $dto = $this->generatePath($uploadedFile);
            $uploadedFile->move($dto->file_folder, $dto->file);

            $file = File::create($dto->toArray());

            $this->createThumbnails($file);

            \DB::commit();
            return $file;
        } catch (Exception $exception) {
            \DB::rollBack();
            return [
                'message' => $exception->getMessage()
            ];
        }
    }

    public function generatePath(UploadedFile $file): UploadFileDto
    {
        $dto = new UploadFileDto();
        $dto->title = $file->getClientOriginalName();
        $dto->size = $file->getSize();
        $dto->ext = $file->getClientOriginalExtension();
        $dto->user_id = \Auth::user()?->id;
        $dto->domain = config('filemanager.static_url');
        $dto->description = $file->getClientOriginalName();

        $base_path = base_path('static');
        $created_at = time();
        $y = date('Y', $created_at);
        $m = date('m', $created_at);
        $d = date('d', $created_at);
        $h = date('H', $created_at);
        $i = date('i', $created_at);

        $folders = [$y, $m, $d, $h, $i];
        $folder_path = '';

        foreach ($folders as $folder) {
            $folder_path .= $folder . '/';
            $base_path .= '/' . $folder;
            if (!is_dir($base_path)) {
                mkdir($base_path, 0777, true);
                chmod($base_path, 0777);
            }
        }

        if (!is_writable($base_path)) {
            throw new DomainException('Path is not writeable');
        }

        $dto->file_folder = $base_path;
        $file_hash = \Str::random(18);
        $dto->file = $file_hash . '.' . $file->getClientOriginalExtension();
        $dto->path = $base_path . '/' . $file_hash . '.' . $file->getClientOriginalExtension();
        $dto->folder = $folder_path;
        $dto->slug = $file_hash;

        return $dto;
    }

    public function createThumbnails(File $file): ?bool
    {
        if (!in_array(strtolower($file->ext), $file->getImageExtensionsAttribute())) {
            return null;
        }

        $thumbsImages = config('filemanager.thumbs');

        $originalFilePath = $file->path;

        if (empty($originalFilePath)) {
            $originalFilePath = base_path('static/' . $file->getDist());
        }

        if (!file_exists($originalFilePath)) {
            return false;
        }

        try {
            foreach ($thumbsImages as $thumbsImage) {
                $width = $thumbsImage['w'];
                $quality = $thumbsImage['q'];
                $slug = $thumbsImage['slug'];
                if ($slug == 'original') {
                    continue;
                }
                $folderPath = rtrim($file->folder, '/') . '/';
                $thumbnailPath = base_path('static/' . $folderPath . $file->slug . '_' . $slug . '.' . $file->ext);

                $thumbnailDir = dirname($thumbnailPath);
                if (!is_dir($thumbnailDir)) {
                    mkdir($thumbnailDir, 0777, true);
                    chmod($thumbnailDir, 0777);
                }

                if (strtolower($file->ext) == 'svg') {
                    copy($originalFilePath, $thumbnailPath);
                } else {
                    $img = Image::read($originalFilePath);
                    $originalWidth = $img->width();
                    $originalHeight = $img->height();

                    if ($originalWidth > 0 && $originalHeight > 0) {
                        $height = intval($width * ($originalHeight / $originalWidth));
                        $img->resize($width, $height)->save($thumbnailPath, quality: $quality);
                    }
                }
            }
        } catch (Throwable $e) {
            \Log::error('Thumbnail creation failed: ' . $e->getMessage(), [
                'file_id' => $file->id,
                'file_path' => $originalFilePath,
                'error' => $e->getMessage()
            ]);
            report($e);
            return false;
        }

        return true;
    }

    public function index(Request $request): JsonResponse
    {
        $query = File::query();
        $user = \Auth::guard('api')->user();
        if ($user && $user->role !== Roles::ROLE_ADMIN) {
            $query->where('user_id', $user->id);
        }
        return $this->withPagination($query, $request);
    }

    public function delete(File $file): JsonResponse
    {
        $this->deleteFile($file);
        $file->delete();
        return okResponse([
            'message' => 'File deleted'
        ]);
    }

    public function deleteFile(File $file): void
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

    public function downloadAndSaveFiles(string $link, $filaAbleType, $fileableId, $fileableKey)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get($link);
        $tempPath = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tempPath, $response->body());

        $file = new UploadedFile(
            $tempPath,
            basename($link),
            $response->header('Content-Type'),
            null,
            true // Test mode
        );

        $request = new Request();
        $request->files->set('files', [$file]);

        $response = $this->upload($request, true);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
        if (isset($response[0])) {
            $fileModel = $response[0];
            Fileable::create([
                'fileable_id' => $fileableId,
                'file_id' => $fileModel->id,
                'fileable_key' => $fileableKey,
                'fileable_type' => $filaAbleType
            ]);
            return $fileModel;
        }

        return null;
    }
}

