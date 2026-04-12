<?php

namespace Helpers;

use RuntimeException;

class ChecklistPhotoStorage {
    private const MAX_FILE_SIZE = 10485760;

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
                throw new RuntimeException('A foto do painel excede o tamanho maximo permitido.');
            default:
                throw new RuntimeException('Nao foi possivel processar a foto do painel enviada.');
        }

        if (($file['size'] ?? 0) <= 0 || $file['size'] > self::MAX_FILE_SIZE) {
            throw new RuntimeException('A foto do painel deve ter ate 10 MB.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Upload de foto invalido.');
        }

        $extension = self::detectExtension($file);
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
            'url' => $relativeDirectory . '/' . $filename,
            'path' => $absolutePath,
        ];
    }

    public static function delete($absolutePath) {
        if (is_string($absolutePath) && $absolutePath !== '' && is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private static function detectExtension($file) {
        $mime = '';

        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']) ?: '';
        }

        if ($mime === '' && isset($file['type'])) {
            $mime = strtolower((string) $file['type']);
        }

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
}
