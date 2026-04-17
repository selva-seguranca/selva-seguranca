<?php

namespace Controllers;

use DateTimeImmutable;
use Helpers\Auth;
use Helpers\View;
use Models\PortalRepository;
use Throwable;

class EscalaController {
    public function calendario() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        $monthStart = new DateTimeImmutable('first day of this month');
        $monthEnd = new DateTimeImmutable('last day of this month');
        $plantoes = [];
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $plantoes = $repository->getScaleEntries($monthStart, $monthEnd);
        } catch (Throwable $e) {
            $dbWarning = 'Nao foi possivel carregar as escalas a partir do banco.';
        }

        View::render('escalas/calendario', [
            'pageTitle' => 'Escalas e Plantoes',
            'plantoes' => $plantoes,
            'calendarTitle' => $monthStart->format('m/Y'),
            'calendarDays' => $this->buildCalendarDays($monthStart, $plantoes),
            'dbWarning' => $dbWarning,
        ]);
    }

    private function buildCalendarDays(DateTimeImmutable $monthStart, $plantoes) {
        $monthKey = $monthStart->format('Y-m');
        $daysInMonth = (int) $monthStart->format('t');
        $firstDayOfWeek = (int) $monthStart->format('w');
        $eventsByDay = [];

        foreach ($plantoes as $plantao) {
            $eventsByDay[$plantao['data']][] = $plantao;
        }

        $cells = [];

        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $cells[] = [
                'label' => '',
                'date' => null,
                'isCurrentMonth' => false,
                'isToday' => false,
                'events' => [],
            ];
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $monthKey . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
            $cells[] = [
                'label' => $day,
                'date' => $date,
                'isCurrentMonth' => true,
                'isToday' => $date === date('Y-m-d'),
                'events' => $eventsByDay[$date] ?? [],
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = [
                'label' => '',
                'date' => null,
                'isCurrentMonth' => false,
                'isToday' => false,
                'events' => [],
            ];
        }

        return $cells;
    }
}
