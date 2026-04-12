<?php

namespace Controllers;

use Helpers\ChecklistPhotoStorage;
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
                ChecklistPhotoStorage::delete($storedPhoto['path'] ?? null);
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
