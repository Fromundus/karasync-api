<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class FileService
{
    /**
     * Store one or many files for a given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model The related model (e.g. MonthlyRate, Announcement)
     * @param array|UploadedFile $files Single file or array of files
     * @param string $directory Storage directory
     * @param string $disk Storage disk (default: public)
     * @return \Illuminate\Database\Eloquent\Collection|File
     */
    public function store(Model $model, array|UploadedFile $files, string $directory, string $disk = 'public', string $type = null)
    {
        if ($files instanceof UploadedFile) {
            return $this->saveFile($model, $files, $directory, $disk, $type);
        }

        $storedFiles = [];
        foreach ($files as $file) {
            $storedFiles[] = $this->saveFile($model, $file, $directory, $disk, $type);
        }

        return collect($storedFiles);
    }

    /**
     * Save a single file.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param string $disk
     * @return File
     */
    protected function saveFile(Model $model, UploadedFile $file, string $directory, string $disk, string $type = null): File
    {
        $path = $file->store($directory, $disk);

        return $model->files()->create([
            'filename'      => $file->getClientOriginalName(),
            'path'          => $path,
            'mime_type'     => $file->getClientMimeType(),
            'size'          => $file->getSize(),
            'type'          => $type ?? null,
        ]);
    }
}
