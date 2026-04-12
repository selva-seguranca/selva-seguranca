<?php

namespace Controllers;

use Helpers\ChecklistPhotoStorage;
use Helpers\MediaStorage;
use Helpers\View;
use Models\PortalRepository;
use Throwable;

class VigilanteController {
    public function preRonda() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        $veiculos = [];
        $dbWarning = null;
        $formError = $_SESSION['ronda_error'] ?? null;
        $successMessage = $_SESSION['ronda_success'] ?? null;
        unset($_SESSION['ronda_error']);
        unset($_SESSION['ronda_success']);

        try {
            $repository = new PortalRepository();
            $rondaAtiva = $repository->getActiveRoundByUserId($_SESSION['user_id']);

            if ($rondaAtiva !== null) {
                $_SESSION['ronda_ativa'] = true;
                $_SESSION['ronda_id'] = $rondaAtiva['id'];
                header("Location: /vigilante/painel");
                exit;
            }

            $veiculos = $repository->getChecklistVehicles();
        } catch (Throwable $e) {
            $dbWarning = 'Nao foi possivel carregar os veiculos disponiveis.';
        }

        View::render('vigilante/ronda', [
            'veiculos' => $veiculos,
            'dbWarning' => $dbWarning,
            'formError' => $formError,
            'successMessage' => $successMessage,
        ], null);
    }

    public function submitChecklist() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /vigilante/ronda");
            exit;
        }

        $storedPhoto = null;

        try {
            $storedPhoto = ChecklistPhotoStorage::store($_FILES['foto_painel'] ?? null);
            $repository = new PortalRepository();
            $ronda = $repository->startRoundFromChecklist(
                $_SESSION['user_id'],
                $_POST,
                $storedPhoto['url']
            );

            $_SESSION['ronda_ativa'] = true;
            $_SESSION['ronda_id'] = $ronda['id'];

            header("Location: /vigilante/painel");
            exit;
        } catch (Throwable $e) {
            if ($storedPhoto !== null) {
                ChecklistPhotoStorage::delete($storedPhoto);
            }

            $_SESSION['ronda_error'] = $e->getMessage();
            header("Location: /vigilante/ronda");
            exit;
        }
    }

    public function painelAtivo() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        try {
            $repository = new PortalRepository();
            $ronda = null;

            if (isset($_SESSION['ronda_id'])) {
                $ronda = $repository->getRoundByIdForUser($_SESSION['ronda_id'], $_SESSION['user_id']);
            }

            if ($ronda === null) {
                $ronda = $repository->getActiveRoundByUserId($_SESSION['user_id']);
            }

            if ($ronda === null) {
                unset($_SESSION['ronda_ativa'], $_SESSION['ronda_id']);
                header("Location: /vigilante/ronda");
                exit;
            }

            $_SESSION['ronda_ativa'] = true;
            $_SESSION['ronda_id'] = $ronda['id'];

            View::render('vigilante/painel', [
                'ronda' => $ronda,
            ], null);
        } catch (Throwable $e) {
            $_SESSION['ronda_error'] = 'Nao foi possivel carregar a ronda ativa.';
            header("Location: /vigilante/ronda");
            exit;
        }
    }

    public function registrarOcorrencia() {
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Sessao expirada.']);
            exit;
        }

        try {
            $rondaId = $_POST['ronda_id'] ?? ($_SESSION['ronda_id'] ?? null);
            if (!$rondaId) {
                throw new \RuntimeException('Ronda nao identificada.');
            }

            $repository = new PortalRepository();
            
            $fotoUrl = null;
            $videoUrl = null;

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $res = MediaStorage::store($_FILES['foto'], 'ocorrencias');
                if ($res) $fotoUrl = $res['url'];
            }
            if (isset($_FILES['video']) && $_FILES['video']['error'] !== UPLOAD_ERR_NO_FILE) {
                $res = MediaStorage::store($_FILES['video'], 'ocorrencias');
                if ($res) $videoUrl = $res['url'];
            }
            if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] !== UPLOAD_ERR_NO_FILE) {
                $res = MediaStorage::store($_FILES['evidencia'], 'ocorrencias');
                if ($res) {
                    $mime = $res['mime'] ?? '';
                    if (strpos($mime, 'video') !== false) {
                        $videoUrl = $res['url'];
                    } else {
                        $fotoUrl = $res['url'];
                    }
                }
            }

            $dados = [
                'tipo' => $_POST['tipo'] ?? 'outros',
                'descricao' => $_POST['descricao'] ?? '',
                'latitude' => $_POST['latitude'] ?? null,
                'longitude' => $_POST['longitude'] ?? null,
                'foto_url' => $fotoUrl,
                'video_url' => $videoUrl,
            ];

            $ocorrencia = $repository->registrarOcorrencia($rondaId, $dados);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'ocorrencia' => $ocorrencia,
                'message' => 'Ocorrencia registrada com sucesso.'
            ]);
        } catch (Throwable $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function finalizarRonda() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /vigilante/painel");
            exit;
        }

        try {
            $repository = new PortalRepository();
            $repository->finishRound($_POST['ronda_id'] ?? ($_SESSION['ronda_id'] ?? null), $_SESSION['user_id']);

            unset($_SESSION['ronda_ativa'], $_SESSION['ronda_id']);
            $_SESSION['ronda_success'] = 'Ronda finalizada com sucesso.';

            header("Location: /vigilante/ronda");
            exit;
        } catch (Throwable $e) {
            $_SESSION['ronda_error'] = $e->getMessage();
            header("Location: /vigilante/painel");
            exit;
        }
    }
}
