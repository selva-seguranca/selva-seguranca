<?php

namespace Helpers;

use Config\Database;
use Config\Env;
use RuntimeException;

class ChecklistPhotoStorage {
    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
    ];

    public static function store($file) {
        if (!is_array($file) || !isset($file['error'])) {
            throw new RuntimeException('Envie a foto do painel para iniciar a ronda.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('Envie a foto do painel para iniciar a ronda.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('A foto do painel excede o limite de ' . self::getMaxFileSizeLabel() . '.');
            default:
                throw new RuntimeException('Nao foi possivel processar a foto do painel enviada.');
        }

        if (($file['size'] ?? 0) <= 0 || $file['size'] > self::getMaxFileSizeBytes()) {
            throw new RuntimeException('A foto do painel deve ter ate ' . self::getMaxFileSizeLabel() . '.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Upload de foto invalido.');
        }

        $extension = self::detectExtension($file);
        $driver = strtolower((string) Env::get(
            'CHECKLIST_STORAGE_DRIVER',
            Env::isTruthy('VERCEL') ? 'supabase' : 'local'
        ));

        if ($driver === 'local') {
            return self::storeLocally($file, $extension);
        }

        if ($driver === 'supabase') {
            return self::storeInSupabase($file, $extension);
        }

        throw new RuntimeException('Driver de armazenamento de checklist invalido.');
    }

    public static function delete($storedFile) {
        if (is_array($storedFile)) {
            $driver = strtolower((string) ($storedFile['driver'] ?? 'local'));

            if ($driver === 'supabase' && !empty($storedFile['object_path'])) {
                self::deleteFromSupabase($storedFile['object_path']);
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

        throw new RuntimeException('Formato de foto nao suportado. Use JPG, PNG, WEBP ou HEIC.');
    }

    private static function detectMimeType($file) {
        $mime = '';

        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']) ?: '';
        }

        if ($mime === '' && isset($file['type'])) {
            $mime = strtolower((string) $file['type']);
        }

        return $mime;
    }

    private static function storeLocally($file, $extension) {
        if (Env::isTruthy('VERCEL')) {
            throw new RuntimeException('Na Vercel, configure CHECKLIST_STORAGE_DRIVER=supabase para salvar a foto do checklist.');
        }

        $relativeDirectory = '/uploads/checklists/' . date('Y/m');
        $absoluteDirectory = dirname(__DIR__) . '/public' . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new RuntimeException('Nao foi possivel preparar o diretorio da foto do checklist.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new RuntimeException('Nao foi possivel salvar a foto do checklist.');
        }

        return [
            'driver' => 'local',
            'url' => $relativeDirectory . '/' . $filename,
            'path' => $absolutePath,
        ];
    }

    private static function storeInSupabase($file, $extension) {
        $supabaseUrl = self::resolveSupabaseUrl();
        $bucket = self::resolveSupabaseBucket();
        $storageKey = self::resolveSupabaseStorageKey();

        if ($supabaseUrl === '') {
            throw new RuntimeException('Defina SUPABASE_URL na Vercel para salvar a foto do checklist.');
        }

        if ($bucket === '') {
            throw new RuntimeException('Defina SUPABASE_STORAGE_BUCKET para salvar a foto do checklist.');
        }

        if ($storageKey === '') {
            throw new RuntimeException('Defina SUPABASE_STORAGE_KEY ou SUPABASE_SERVICE_ROLE_KEY na Vercel.');
        }

        $baseDirectory = trim((string) Env::get('SUPABASE_STORAGE_DIRECTORY', 'checklists'), '/');
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $objectPath = trim($baseDirectory . '/' . date('Y/m') . '/' . $filename, '/');
        $uploadUrl = $supabaseUrl . '/storage/v1/object/' . rawurlencode($bucket) . '/' . self::encodePath($objectPath);
        $fileContents = file_get_contents($file['tmp_name']);

        if ($fileContents === false) {
            throw new RuntimeException('Nao foi possivel ler a foto do checklist para envio.');
        }

        try {
            self::sendSupabaseRequest(
                'POST',
                $uploadUrl,
                $storageKey,
                $fileContents,
                self::detectMimeType($file) ?: 'application/octet-stream'
            );
        } catch (RuntimeException $e) {
            if (!self::isMissingBucketError($e)) {
                throw $e;
            }

            self::ensureSupabaseBucketExists($supabaseUrl, $bucket, $storageKey);
            usleep(150000);

            self::sendSupabaseRequest(
                'POST',
                $uploadUrl,
                $storageKey,
                $fileContents,
                self::detectMimeType($file) ?: 'application/octet-stream'
            );
        }

        return [
            'driver' => 'supabase',
            'url' => self::buildSupabasePublicUrl($supabaseUrl, $bucket, $objectPath),
            'path' => $objectPath,
            'object_path' => $objectPath,
        ];
    }

    private static function deleteFromSupabase($objectPath) {
        $supabaseUrl = self::resolveSupabaseUrl();
        $bucket = self::resolveSupabaseBucket();
        $storageKey = self::resolveSupabaseStorageKey();

        if ($supabaseUrl === '' || $bucket === '' || $storageKey === '') {
            return;
        }

        $deleteUrl = $supabaseUrl . '/storage/v1/object/' . rawurlencode($bucket) . '/' . self::encodePath($objectPath);

        try {
            self::sendSupabaseRequest('DELETE', $deleteUrl, $storageKey);
        } catch (RuntimeException $e) {
            return;
        }
    }

    private static function sendSupabaseRequest($method, $url, $apiKey, $body = null, $contentType = null) {
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'apikey: ' . $apiKey,
            'x-upsert: false',
        ];

        if ($contentType !== null && $contentType !== '') {
            $headers[] = 'Content-Type: ' . $contentType;
        }

        $options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 30,
            ],
        ];

        if ($body !== null) {
            $options['http']['content'] = $body;
        }

        $context = stream_context_create($options);
        $responseBody = false;
        $responseHeaders = [];
        $stream = @fopen($url, 'rb', false, $context);

        if (is_resource($stream)) {
            $responseBody = stream_get_contents($stream);
            $metadata = stream_get_meta_data($stream);
            $responseHeaders = is_array($metadata['wrapper_data'] ?? null) ? $metadata['wrapper_data'] : [];
            fclose($stream);
        }

        $statusCode = self::extractHttpStatusCode($responseHeaders);

        if ($statusCode >= 200 && $statusCode < 300) {
            return $responseBody;
        }

        $errorMessage = self::extractSupabaseErrorMessage($responseBody);
        if ($errorMessage === '') {
            $errorMessage = 'Falha ao enviar a foto do checklist para o Supabase Storage.';
        }

        throw new RuntimeException($errorMessage);
    }

    private static function getLastHttpResponseHeaders() {
        if (function_exists('http_get_last_response_headers')) {
            $headers = http_get_last_response_headers();
            return is_array($headers) ? $headers : [];
        }

        return [];
    }

    private static function ensureSupabaseBucketExists($supabaseUrl, $bucket, $apiKey) {
        $bucketUrl = rtrim($supabaseUrl, '/') . '/storage/v1/bucket';
        $payload = [
            'id' => $bucket,
            'name' => $bucket,
            'public' => true,
            'allowed_mime_types' => self::resolveBucketAllowedMimeTypes(),
            'file_size_limit' => self::getMaxFileSizeBytes(),
        ];

        try {
            self::sendSupabaseJsonRequest('POST', $bucketUrl, $apiKey, $payload);
        } catch (RuntimeException $e) {
            if (self::ensureSupabaseBucketExistsViaDatabase($bucket)) {
                return;
            }

            if (self::isBucketAlreadyExistsError($e)) {
                return;
            }

            throw new RuntimeException(
                'Falha ao criar automaticamente o bucket do Supabase: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private static function sendSupabaseJsonRequest($method, $url, $apiKey, array $payload) {
        return self::sendSupabaseRequest(
            $method,
            $url,
            $apiKey,
            json_encode($payload),
            'application/json'
        );
    }

    private static function ensureSupabaseBucketExistsViaDatabase($bucket) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO storage.buckets (id, name, public)
                 VALUES (:id, :name, true)
                 ON CONFLICT (id) DO NOTHING'
            );
            $stmt->execute([
                ':id' => $bucket,
                ':name' => $bucket,
            ]);

            return true;
        } catch (\Throwable $e) {
            error_log('[ChecklistPhotoStorage] bucket SQL fallback failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function extractHttpStatusCode($headers) {
        if (empty($headers) || !preg_match('/\s(\d{3})\s?/', (string) $headers[0], $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }

    private static function extractSupabaseErrorMessage($responseBody) {
        if (!is_string($responseBody) || trim($responseBody) === '') {
            return '';
        }

        $payload = json_decode($responseBody, true);
        if (!is_array($payload)) {
            return '';
        }

        foreach (['message', 'error', 'msg'] as $key) {
            if (!empty($payload[$key]) && is_string($payload[$key])) {
                return $payload[$key];
            }
        }

        return '';
    }

    private static function isMissingBucketError(RuntimeException $e) {
        $message = strtolower(trim($e->getMessage()));

        return strpos($message, 'bucket not found') !== false
            || strpos($message, 'the resource was not found') !== false;
    }

    private static function isBucketAlreadyExistsError(RuntimeException $e) {
        $message = strtolower(trim($e->getMessage()));

        return strpos($message, 'already exists') !== false
            || strpos($message, 'duplicate') !== false;
    }

    private static function resolveSupabaseUrl() {
        $configuredUrl = self::getFirstNonEmptyEnv([
            'SUPABASE_URL',
            'SUPABASE_PROJECT_URL',
            'NEXT_PUBLIC_SUPABASE_URL',
        ]);

        if ($configuredUrl !== null) {
            return rtrim($configuredUrl, '/');
        }

        $projectRef = self::resolveSupabaseProjectRef();
        if ($projectRef !== null) {
            return 'https://' . $projectRef . '.supabase.co';
        }

        return '';
    }

    private static function resolveSupabaseBucket() {
        $bucket = self::getFirstNonEmptyEnv([
            'SUPABASE_STORAGE_BUCKET',
            'SUPABASE_BUCKET',
        ]);

        return $bucket !== null ? $bucket : 'checklists';
    }

    private static function resolveSupabaseStorageKey() {
        $key = self::getFirstNonEmptyEnv([
            'SUPABASE_STORAGE_KEY',
            'SUPABASE_SECRET_KEY',
            'SUPABASE_SERVICE_ROLE_KEY',
            'SUPABASE_PUBLISHABLE_KEY',
            'NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY',
            'SUPABASE_ANON_KEY',
            'NEXT_PUBLIC_SUPABASE_ANON_KEY',
            'SERVICE_ROLE_KEY',
            'ANON_KEY',
        ]);

        return $key !== null ? $key : '';
    }

    private static function resolveSupabaseProjectRef() {
        $host = self::getFirstNonEmptyEnv([
            'DB_HOST',
            'POSTGRES_HOST',
        ]);
        $projectRef = self::extractSupabaseProjectRefFromHost($host);
        if ($projectRef !== null) {
            return $projectRef;
        }

        $databaseUrl = self::getFirstNonEmptyEnv([
            'DATABASE_URL',
            'POSTGRES_URL',
            'SUPABASE_DB_URL',
        ]);

        if ($databaseUrl === null) {
            return null;
        }

        $parts = parse_url($databaseUrl);
        if ($parts === false) {
            return null;
        }

        $projectRef = self::extractSupabaseProjectRefFromHost($parts['host'] ?? null);
        if ($projectRef !== null) {
            return $projectRef;
        }

        $databaseUser = urldecode((string) ($parts['user'] ?? ''));
        if (preg_match('/^[^.]+\.([a-z0-9-]+)$/i', $databaseUser, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    private static function extractSupabaseProjectRefFromHost($host) {
        $host = strtolower(trim((string) $host));
        if ($host === '') {
            return null;
        }

        if (preg_match('/^db\.([a-z0-9-]+)\.supabase\.co$/i', $host, $matches)) {
            return strtolower($matches[1]);
        }

        if (preg_match('/^([a-z0-9-]+)\.supabase\.co$/i', $host, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    private static function getFirstNonEmptyEnv(array $names) {
        foreach ($names as $name) {
            $value = trim((string) Env::get($name, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function resolveBucketAllowedMimeTypes() {
        return array_values(array_unique(array_merge(
            ['image/*'],
            array_keys(self::MIME_TO_EXTENSION)
        )));
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
        $defaultMegabytes = Env::isTruthy('VERCEL') ? 4 : 10;
        $configuredMegabytes = (float) Env::get('CHECKLIST_MAX_FILE_SIZE_MB', (string) $defaultMegabytes);

        if ($configuredMegabytes <= 0) {
            $configuredMegabytes = $defaultMegabytes;
        }

        return (int) round($configuredMegabytes * 1024 * 1024);
    }

    private static function getMaxFileSizeLabel() {
        $megabytes = self::getMaxFileSizeBytes() / 1048576;

        if (abs($megabytes - (int) $megabytes) < 0.001) {
            return (int) $megabytes . ' MB';
        }

        return number_format($megabytes, 1, ',', '.') . ' MB';
    }
}
