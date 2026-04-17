<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\View;
use Models\PortalRepository;
use Throwable;

class FinanceiroController {
    public function index() {
        Auth::requireAnyProfile(['Coordenador Geral']);

        $summary = [
            'receitas_mes' => 0,
            'despesas_mes' => 0,
            'saldo_previsto_mes' => 0,
            'recebido_mes' => 0,
            'a_pagar' => 0,
            'a_receber' => 0,
            'atrasados' => 0,
        ];
        $lancamentos = [];
        $agendaFinanceira = [];
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $summary = $repository->getFinancialSummary();
            $lancamentos = $repository->getFinancialEntries();
            $agendaFinanceira = $repository->getUpcomingFinancialEntries();
        } catch (Throwable $e) {
            $dbWarning = 'Nao foi possivel carregar os dados financeiros direto do banco.';
        }

        View::render('financeiro/index', [
            'pageTitle' => 'Financeiro',
            'summary' => $summary,
            'lancamentos' => $lancamentos,
            'agendaFinanceira' => $agendaFinanceira,
            'dbWarning' => $dbWarning,
        ]);
    }
}
