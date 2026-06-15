<?php

namespace App\Features\Chat\Services;

use Illuminate\Http\UploadedFile;

class FileUploadService
{
    public function handle(UploadedFile $file): array
    {
        return [
            'file_path' => $file->store('chat-attachments', 'public'),
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ];
    }
}
