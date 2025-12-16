<?php

namespace Modules\FileManager\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\FileManager\App\Http\Repositories\FileRepository;
use Modules\FileManager\App\Http\Requests\StoreFileRequest;
use Modules\FileManager\App\Models\File;
use Modules\FileManager\App\Models\Fileable;

class FileManagerController extends Controller
{
    public function __construct(
        public FileRepository $fileRepository = new FileRepository(),
    )
    {
    }

    public function index(Request $request)
    {
        return $this->fileRepository->index($request);
    }
    public function upload(StoreFileRequest $request)
    {
        return $this->fileRepository->upload($request);
    }
    public function checkUpload(StoreFileRequest $request)
    {
        return $this->fileRepository->upload($request);
    }
    public function adminUpload(StoreFileRequest $request)
    {
        return $this->fileRepository->upload($request);
    }

    public function show(File $file)
    {
        return okResponse($file);
    }

    public function delete(File $file)
    {
        return $this->fileRepository->delete($file);
    }
    public function showByKey(string $key)
    {
        $files = Fileable::showByKey($key);

        return response()->json([
            'key' => $key,
            'files' => $files,
        ]);
    }

}
