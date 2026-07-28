<?php

namespace App\Facades\File;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File as HttpFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

class File
{
    public static function save(HttpFile|UploadedFile $file, string $path, string $name = null, string $disk = 'public'): string
    {
        return Storage::disk($disk)->putFileAs(
            $path,
            $file,
            $name ?? Str::uuid() . '.' . $file->extension()
        );
    }

    /**
     * @param string $filePath
     * @param bool $url_is_absolute
     * @param string $disk
     * @return void
     * @throws InvalidArgumentException when filePath is empty.
     */
    public static function delete(string $filePath, bool $url_is_absolute = false, string $disk = 'public'): void
    {
        if (!$filePath){
            throw new InvalidArgumentException('File path is invalid.');
        }

        if ($url_is_absolute) {
            $needle = 'storage/';
            $pos = strpos($filePath, $needle);
            $filePath = substr($filePath, $pos + strlen($needle));
        }

        if (Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->delete($filePath);
        }
    }

    public function deleteContent($file_path): bool
    {
        if (file_exists($file_path)) {
            file_put_contents($file_path, '');

            return true;
        }
        return false;
    }

    public static function tail($file_path, $lines = 100, $buffer = 2048): bool|string
    {
        // Open file
        $file = @fopen($file_path, "rb");
        if ($file === false) {
            return false;
        }

        // Jump to last character
        fseek($file, -1, SEEK_END);

        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($file, 1) != "\n") {
            $lines -= 1;
        }

        // Start reading
        $output = '';
        $chunk = '';

        // While we would like more
        while (ftell($file) > 0 && $lines >= 0) {

            // Figure out how far back we should jump
            $seek = min(ftell($file), $buffer);

            // Do the jump (backwards, relative to where we are)
            fseek($file, -$seek, SEEK_CUR);

            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($file, $seek)) . $output;

            // Jump back to where we started reading
            fseek($file, -mb_strlen($chunk, '8bit'), SEEK_CUR);

            // Decrease our line counter
            $lines -= substr_count($chunk, "\n");
        }

        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {

            // Find first newline and remove all text before that
            $output = substr($output, strpos($output, "\n") + 1);
        }

        // Close file and return
        fclose($file);
        return trim($output);
    }

    public static function deleteDirectory(string $path, string $disk = 'public'): void
    {
        Storage::disk($disk)->deleteDirectory($path);
    }
}
