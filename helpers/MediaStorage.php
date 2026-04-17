<?php

namespace Helpers;

use Config\Env;
use RuntimeException;

class MediaStorage {
    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'video/mpeg' => 'mpeg',
        'video/x-msvideo' => 'avi',
        'video/webm' => 'webm'
    ];

    /**
     * Store a file in the configured storage driver.
     * 
     * @param array $file The $_FILES entry
     * @param string $folder The subfolder/bucket to store the file in (e.g., 'ocorrencias')
     * @return array Stored file information (url, path, driver)
     */
    public static function store($file, string $folder = 'uploads') {
        if (!is_array($file) || !isset($file['error'])) {
            throw new RuntimeException('Arquivo nao recebido corretamente.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return null; // Optional media
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('O arquivo excede o limite de ' . self::getMaxFileSizeLabel() . '.');
            default:
                throw new RuntimeException('Nao foi possivel processar o arquivo enviado.');
        }

        if (($file['size'] ?? 0) <= 0 || $file['size'] > self::getMaxFileSizeBytes()) {
            throw new RuntimeException('O arquivo deve ter ate ' . self::getMaxFileSizeLabel() . '.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Upload de arquivo invalido.');
        }

        $extension = self::detectExtension($file);
        $driver = strtolower((string) Env::get(
            'MEDIA_STORAGE_DRIVER',
            Env::get('CHECKLIST_STORAGE_DRIVER', Env::isTruthy('VERCEL') ? 'supabase' : 'local')
        ));

        if ($driver === 'local') {
            return self::storeLocally($file, $extension, $folder);
        }

        if ($driver === 'supabase') {
            return self::storeInSupabase($file, $extension, $folder);
        }

        throw new RuntimeException('Driver de armazenamento de media invalido.');
    }

    public static function delete($storedFile) {
        if (is_array($storedFile)) {
            $driver = strtolower((string) ($storedFile['driver'] ?? 'local'));

            if ($driver === 'supabase' && !empty($storedFile['object_path'])) {
                self::deleteFromSupabase($storedFile['object_path'], $storedFile['bucket'] ?? 'checklists');
                return;
            }

            $localPath = $storedFile['path'] ?? null;
            if (is_string($localPath) && $localPath !== '' && is_file($localPath)) {
                @unlink($localPath);
            }
            return;
        }

        if (is_string($storedFile) && $storedFile !== '' && is_file($storedFile)) {
            @unlink($storedFile);
        }
    }

    private static function detectExtension($file) {
        $mime = self::detectMimeType($file);

        if (isset(self::MIME_TO_EXTENSION[$mime])) {
            return self::MIME_TO_EXTENSION[$mime];
        }

        $fallback = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedExtensions = array_unique(array_values(self::MIME_TO_EXTENSION));

        if (in_array($fallback, $allowedExtensions, true) || $fallback === 'jpeg') {
            return $fallback === 'jpeg' ? 'jpg' : $fallback;
        }

        throw new RuntimeException('Formato de arquivo nao suportado.');
    }

    private static function detectMimeType($file) {
        $mime = '';

        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = @$finfo->file($file['tmp_name']) ?: '';
        }

        if ($mime === '' && isset($file['type'])) {
            $mime = strtolower((string) $file['type']);
        }

        return $mime;
    }

    private static function storeLocally($file, $extension, $folder) {
        if (Env::isTruthy('VERCEL')) {
            throw new RuntimeException('Na Vercel, use driver supabase.');
        }

        $mime = self::detectMimeType($file) ?: 'application/octet-stream';
        $relativeDirectory = '/uploads/' . $folder . '/' . date('Y/m');
        $absoluteDirectory = dirname(__DIR__) . '/public' . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new RuntimeException('Nao foi possivel preparar o diretorio de armazenamento.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new RuntimeException('Nao foi possivel salvar o arquivo localmente.');
        }

        return [
            'driver' => 'local',
            'url' => $relativeDirectory . '/' . $filename,
            'path' => $absolutePath,
            'mime' => $mime,
        ];
    }

    private static function storeInSupabase($file, $extension, $folder) {
        $supabaseUrl = self::resolveSupabaseUrl();
        $bucket = trim((string) Env::get('SUPABASE_STORAGE_BUCKET', 'checklists'));
        $storageKey = trim((string) Env::get('SUPABASE_SERVICE_ROLE_KEY', Env::get('SUPABASE_ANON_KEY', '')));

        if ($supabaseUrl === '' || $storageKey === '') {
            throw new RuntimeException('Configuracao do Supabase ausente.');
        }

        $mime = self::detectMimeType($file) ?: 'application/octet-stream';
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $objectPath = $folder . '/' . date('Y/m') . '/' . $filename;
        
        $uploadUrl = $supabaseUrl . '/storage/v1/object/' . rawurlencode($bucket) . '/' . self::encodePath($objectPath);
        $fileContents = file_get_contents($file['tmp_name']);

        if ($fileContents === false) {
            throw new RuntimeException('Nao foi possivel ler o arquivo para envio.');
        }

        self::sendSupabaseRequest(
            'POST',
            $uploadUrl,
            $storageKey,
            $fileContents,
            $mime
        );

        return [
            'driver' => 'supabase',
            'url' => self::buildSupabasePublicUrl($supabaseUrl, $bucket, $objectPath),
            'path' => $objectPath,
            'object_path' => $objectPath,
            'bucket' => $bucket,
            'mime' => $mime,
        ];
    }

    private static function deleteFromSupabase($objectPath, $bucket) {
        $supabaseUrl = self::resolveSupabaseUrl();
        $storageKey = trim((string) Env::get('SUPABASE_SERVICE_ROLE_KEY', ''));

        if ($supabaseUrl === '' || $storageKey === '') {
            return;
        }

        $deleteUrl = $supabaseUrl . '/storage/v1/object/' . rawurlencode($bucket) . '/' . self::encodePath($objectPath);

        try {
            self::sendSupabaseRequest('DELETE', $deleteUrl, $storageKey);
        } catch (RuntimeException $e) {
            // Silently fail on delete errors
        }
    }

    private static function sendSupabaseRequest($method, $url, $apiKey, $body = null, $contentType = null) {
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'apikey: ' . $apiKey,
        ];

        if ($contentType !== null) {
            $headers[] = 'Content-Type: ' . $contentType;
        }

        $options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 60,
            ],
        ];

        if ($body !== null) {
            $options['http']['content'] = $body;
        }

        $context = stream_context_create($options);
        $responseBody = @file_get_contents($url, false, $context);
        $responseHeaders = isset($http_response_header) ? $http_response_header : [];
        $statusCode = self::extractHttpStatusCode($responseHeaders);

        if ($statusCode >= 200 && $statusCode < 300) {
            return $responseBody;
        }

        throw new RuntimeException('Erro no Supabase: ' . $statusCode);
    }

    private static function extractHttpStatusCode($headers) {
        if (empty($headers) || !preg_match('/\s(\d{3})\s?/', (string) $headers[0], $matches)) {
            return 0;
        }
        return (int) $matches[1];
    }

    private static function resolveSupabaseUrl() {
        $configuredUrl = trim((string) Env::get('SUPABASE_URL', Env::get('NEXT_PUBLIC_SUPABASE_URL', '')));
        if ($configuredUrl !== '') {
            return rtrim($configuredUrl, '/');
        }

        $host = trim((string) Env::get('DB_HOST', ''));
        if (preg_match('/^db\.([a-z0-9-]+)\.supabase\.co$/i', $host, $matches)) {
            return 'https://' . $matches[1] . '.supabase.co';
        }

        return '';
    }

    private static function buildSupabasePublicUrl($supabaseUrl, $bucket, $objectPath) {
        $customPublicUrl = trim((string) Env::get('SUPABASE_STORAGE_PUBLIC_URL', ''));
        if ($customPublicUrl !== '') {
            return rtrim($customPublicUrl, '/') . '/' . self::encodePath($objectPath);
        }

        return rtrim($supabaseUrl, '/') . '/storage/v1/object/public/'
            . rawurlencode($bucket) . '/'
            . self::encodePath($objectPath);
    }

    private static function encodePath($path) {
        $segments = array_map('rawurlencode', explode('/', trim((string) $path, '/')));
        return implode('/', $segments);
    }

    private static function getMaxFileSizeBytes() {
        $defaultMb = Env::isTruthy('VERCEL') ? 4 : 20;
        $mb = (float) Env::get('MEDIA_MAX_FILE_SIZE_MB', (string)$defaultMb);
        return (int) round($mb * 1024 * 1024);
    }

    private static function getMaxFileSizeLabel() {
        $bytes = self::getMaxFileSizeBytes();
        $mb = $bytes / (1024 * 1024);
        return $mb . ' MB';
    }
}
