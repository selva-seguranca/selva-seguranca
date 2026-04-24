<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\MediaStorage;
use Helpers\View;
use Helpers\WarningPdf;
use Models\PortalRepository;
use Throwable;

class AdvertenciaController {
    public function index() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        $advertenciasControl = [
            'vigilantes' => [],
            'ocorrencias' => [],
            'advertencias' => [],
            'ocorrencias_registradas' => [],
            'resumo' => [
                'advertencias_total' => 0,
                'ocorrencias_total' => 0,
                'mes_atual' => 0,
                'graves_total' => 0,
                'evolucao_total' => 0,
            ],
        ];
        $flash = $this->consumeFlash();
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $advertenciasControl = $repository->getRhWarningControlData();
        } catch (Throwable $e) {
            $dbWarning = 'Não foi possível carregar o módulo de ocorrências e advertências direto do banco.';
        }

        View::render('advertencias/index', [
            'pageTitle' => 'Ocorrências e Advertências',
            'advertenciasControl' => $advertenciasControl,
            'advertenciaSuccess' => $flash['success'],
            'advertenciaError' => $flash['error'],
            'ocorrenciaSuccess' => $flash['occurrence_success'],
            'ocorrenciaError' => $flash['occurrence_error'],
            'dbWarning' => $dbWarning,
        ]);
    }

    public function store() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: /advertencias');
            exit;
        }

        $repository = null;
        $warningId = '';

        try {
            $repository = new PortalRepository();
            $createdWarning = $repository->createCollaboratorWarning(
                $_POST,
                $_SESSION['user_id'] ?? null,
                $_SESSION['user_nome'] ?? ''
            );
            $warningId = (string) ($createdWarning['id'] ?? '');

            if ($warningId === '') {
                throw new \RuntimeException('Advertência registrada, mas não foi possível identificar o registro para gerar o PDF.');
            }

            $warning = $repository->getCollaboratorWarningById($warningId);

            if ($warning === null) {
                throw new \RuntimeException('Advertência registrada, mas não foi possível carregar os dados para gerar o PDF.');
            }

            $pdfContent = WarningPdf::generate($warning);
            $pdfFileName = $this->buildPdfFileName($warning, $warningId);
            $storedPdf = null;

            try {
                $storedPdf = MediaStorage::storeContent(
                    $pdfContent,
                    $pdfFileName,
                    'colaboradores/advertencias',
                    null,
                    'application/pdf'
                );

                $repository->attachCollaboratorWarningPdf(
                    $warningId,
                    $storedPdf,
                    $pdfFileName,
                    strlen($pdfContent)
                );
            } catch (Throwable $e) {
                if ($storedPdf !== null) {
                    MediaStorage::delete($storedPdf);
                }

                throw $e;
            }

            $_SESSION['advertencia_success'] = 'ADVERTÊNCIA REGISTRADA COM SUCESSO!';
        } catch (Throwable $e) {
            if ($repository instanceof PortalRepository && $warningId !== '') {
                try {
                    $repository->deleteCollaboratorWarning($warningId);
                } catch (Throwable $cleanupError) {
                }
            }

            $_SESSION['advertencia_error'] = $e->getMessage();
        }

        header('Location: /advertencias');
        exit;
    }

    public function storeOccurrence() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: /advertencias');
            exit;
        }

        try {
            $repository = new PortalRepository();
            $repository->createCollaboratorOccurrenceRecord(
                $_POST,
                $_SESSION['user_id'] ?? null,
                $_SESSION['user_nome'] ?? ''
            );

            $_SESSION['occurrence_success'] = 'OCORRÊNCIA SALVA COM SUCESSO!';
        } catch (Throwable $e) {
            $_SESSION['occurrence_error'] = $e->getMessage();
        }

        header('Location: /advertencias');
        exit;
    }

    public function pdf() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        $warningId = trim((string) ($_GET['id'] ?? ''));

        if ($warningId === '') {
            http_response_code(404);
            echo 'PDF da advertência não encontrado.';
            return;
        }

        try {
            $repository = new PortalRepository();
            $pdf = $repository->getCollaboratorWarningPdf($warningId);

            if ($pdf === null) {
                http_response_code(404);
                echo 'PDF da advertência não encontrado.';
                return;
            }

            $download = trim((string) ($_GET['download'] ?? '')) === '1';
            $fileName = $this->sanitizePdfDownloadName((string) ($pdf['arquivo_pdf_nome'] ?? 'advertencia.pdf'));
            $driver = strtolower((string) ($pdf['arquivo_pdf_storage_driver'] ?? ''));
            $path = (string) ($pdf['arquivo_pdf_path'] ?? '');

            if ($driver === 'local') {
                $localPath = $path;

                if ($localPath !== '' && strpos($localPath, '/uploads/') === 0) {
                    $localPath = dirname(__DIR__) . '/public' . str_replace('/', DIRECTORY_SEPARATOR, $localPath);
                }

                if ($localPath !== '' && is_file($localPath)) {
                    $this->streamPdfFile($localPath, $fileName, $download);
                    return;
                }
            }

            $url = trim((string) ($pdf['arquivo_pdf_url'] ?? ''));

            if ($url === '') {
                http_response_code(404);
                echo 'PDF da advertência não encontrado.';
                return;
            }

            if ($download) {
                $contents = @file_get_contents($url);

                if ($contents !== false) {
                    $this->streamPdfContent($contents, $fileName, true);
                    return;
                }
            }

            header('Location: ' . $url);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Não foi possível abrir o PDF da advertência.';
        }
    }

    private function consumeFlash() {
        $success = $_SESSION['advertencia_success'] ?? null;
        $error = $_SESSION['advertencia_error'] ?? null;
        $occurrenceSuccess = $_SESSION['occurrence_success'] ?? null;
        $occurrenceError = $_SESSION['occurrence_error'] ?? null;

        unset(
            $_SESSION['advertencia_success'],
            $_SESSION['advertencia_error'],
            $_SESSION['occurrence_success'],
            $_SESSION['occurrence_error']
        );

        return [
            'success' => $success,
            'error' => $error,
            'occurrence_success' => $occurrenceSuccess,
            'occurrence_error' => $occurrenceError,
        ];
    }

    private function buildPdfFileName(array $warning, string $warningId): string {
        $name = trim((string) ($warning['colaborador_nome'] ?? 'advertencia'));
        $date = trim((string) ($warning['data_advertencia'] ?? date('Y-m-d')));
        $date = preg_replace('/[^0-9]/', '', substr($date, 0, 10)) ?: date('Ymd');
        $suffix = substr(preg_replace('/[^a-z0-9]/i', '', $warningId), 0, 8) ?: bin2hex(random_bytes(4));

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $name);
            if ($converted !== false) {
                $name = $converted;
            }
        }

        $name = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)) ?: 'advertencia';
        $name = trim($name, '-');

        return 'advertencia-' . $date . '-' . $name . '-' . $suffix . '.pdf';
    }

    private function sanitizePdfDownloadName(string $fileName): string {
        $fileName = trim(str_replace(["\0", '/', '\\'], '', $fileName));

        if ($fileName === '') {
            return 'advertencia.pdf';
        }

        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'pdf' ? $fileName : $fileName . '.pdf';
    }

    private function streamPdfFile(string $path, string $fileName, bool $download): void {
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($path));
        header(
            'Content-Disposition: '
            . ($download ? 'attachment' : 'inline')
            . '; filename="' . addcslashes($fileName, '"\\') . '"'
        );
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    private function streamPdfContent(string $contents, string $fileName, bool $download): void {
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($contents));
        header(
            'Content-Disposition: '
            . ($download ? 'attachment' : 'inline')
            . '; filename="' . addcslashes($fileName, '"\\') . '"'
        );
        header('X-Content-Type-Options: nosniff');
        echo $contents;
        exit;
    }
}
