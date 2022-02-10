<?php

namespace App\Http\Controllers\FileHandler;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FileHandlerController extends Controller
{

    private $filehandlerService;

    public function __construct(FileHandlerService $filehandlerService)
    {
        $this->filehandlerService = $filehandlerService;
    }

    public function imageUploader(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image',
        ],
            [
                'file.required' => 'Image file is required.',
                'file.image' => 'Team banner must be an image.'
            ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $imgURL= $this->filehandlerService->imageUploader($request->file('file'));

        if ($imgURL) {
            return [
                'image_url' => $imgURL
            ];
        }

        return response()->json([
            'messages' => 'Image uploading unsuccessful.'
        ], 200);

    }


}
