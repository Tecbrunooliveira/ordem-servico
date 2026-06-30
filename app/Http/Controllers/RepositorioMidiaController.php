<?php

namespace App\Http\Controllers;

use App\Support\Subdirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepositorioMidiaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:jpeg,jpg,png,gif,webp,mp4,webm,mov'],
        ]);

        $file = $validated['file'];
        $path = $file->store('repositorio', 'public');
        $mime = (string) $file->getMimeType();
        $isVideo = str_starts_with($mime, 'video/');

        return response()->json([
            'url' => Subdirectory::applicationUrl('/storage/'.$path),
            'type' => $isVideo ? 'video' : 'image',
        ]);
    }
}
