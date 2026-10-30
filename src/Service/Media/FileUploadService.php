<?php

namespace App\Service\Media;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Stores validated image uploads under public/uploads and resolves public URLs.
 */
class FileUploadService
{
    private const MAX_SIZE = 4 * 1024 * 1024;

    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(
        private string $projectDir,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return array{path: string}
     */
    public function store(UploadedFile $file, string $folder): array
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('The uploaded file is not valid');
        }
        if (($file->getSize() ?? 0) > self::MAX_SIZE) {
            throw new \InvalidArgumentException('The image must not exceed 4 MB');
        }

        $mime = $file->getMimeType() ?? '';
        if (!isset(self::ALLOWED_TYPES[$mime])) {
            throw new \InvalidArgumentException('Only JPEG, PNG, WEBP and GIF images are supported');
        }

        $relativeDir = '/uploads/' . trim($folder, '/');
        $absoluteDir = $this->projectDir . '/public' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new \RuntimeException('Unable to create the upload directory');
        }

        $storedName = bin2hex(random_bytes(10)) . '.' . self::ALLOWED_TYPES[$mime];
        $file->move($absoluteDir, $storedName);

        return [
            'path' => $relativeDir . '/' . $storedName,
        ];
    }

    public function delete(?string $path): void
    {
        if (!$path || !str_starts_with($path, '/uploads/')) {
            return;
        }

        $absolute = $this->projectDir . '/public' . $path;
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /** Absolute URL usable directly by the React client. */
    public function publicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $path;
        }

        return $request->getSchemeAndHttpHost() . $path;
    }
}
