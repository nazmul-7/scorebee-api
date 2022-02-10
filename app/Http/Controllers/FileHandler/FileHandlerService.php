<?php

namespace App\Http\Controllers\FileHandler;

use Intervention\Image\Facades\Image;

class FileHandlerService
{
    private $filehandlerQuery;

    public function __construct(FileHandlerQuery $filehandlerQuery)
    {
        $this->filehandlerQuery = $filehandlerQuery;
    }

    public function imageUploader($file): string
    {
        $baseURL = str_replace('api/', '', env('APP_URL'));
        $imgName = hexdec(uniqid()) . '.webp';
        Image::make($file)->save('uploads/' . $imgName);
        return $baseURL . 'uploads/' . $imgName;
    }

    public function imageRemover($fileName, $defaultFileName): void
    {
        $baseURL = str_replace('api/', '', env('APP_URL'));
        $filePath = str_replace($baseURL, '', $fileName);
        $isNotDefaultFile = ! str_contains($filePath, $defaultFileName);
        if ($filePath and $isNotDefaultFile) {
            unlink(public_path($filePath));
        }
    }

}
