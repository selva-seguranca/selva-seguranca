<?php

namespace Helpers;

use DateTimeImmutable;
use Throwable;

class WarningPdf {
    public static function generate(array $warning): string {
        $builder = new self();
        return $builder->build($warning);
    }

    private function build(array $warning): string {
        $pages = [[]];
        $pageIndex = 0;
        $y = 790;

        $addLine = function ($text, $fontSize = 10, $gap = 15) use (&$pages, &$pageIndex, &$y) {
            $lines = $this->wrapText((string) $text, $fontSize >= 13 ? 62 : 92);

            foreach ($lines as $line) {
                if ($y < 60) {
                    $pages[] = [];
                    $pageIndex++;
                    $y = 790;
                }

                $pages[$pageIndex][] = [
                    'text' => $line,
                    'fontSize' => $fontSize,
                    'y' => $y,
                ];
                $y -= $gap;
            }
        };

        $addSpace = function ($space = 10) use (&$y) {
            $y -= $space;
        };

        $addLine('CONTROLE DE ADVERTENCIA DISCIPLINAR', 16, 22);
        $addLine('Selva Seguranca - Registro administrativo para historico do colaborador', 10, 16);
        $addSpace(8);
        $addLine('DADOS DO COLABORADOR', 12, 18);
        $addLine('Colaborador: ' . $this->value($warning['colaborador_nome'] ?? null));
        $addLine('CPF: ' . $this->value($warning['cpf'] ?? null));
        $addLine('Posto de servico: ' . $this->value($warning['posto_servico'] ?? null));
        $addSpace(6);
        $addLine('DADOS DA ADVERTENCIA', 12, 18);
        $addLine('Data da ocorrencia: ' . $this->formatDate($warning['data_ocorrencia'] ?? null));
        $addLine('Data da advertencia: ' . $this->formatDate($warning['data_advertencia'] ?? null));
        $addLine('Tipo: ' . $this->value($warning['tipo_advertencia'] ?? null));
        $addLine('Classificacao da falta: ' . $this->value($warning['classificacao_falta'] ?? null));
        $addLine('Evolucao disciplinar: ' . $this->value($warning['medida_disciplinar'] ?? null));
        $addLine('Motivo padronizado: ' . $this->value($warning['motivo'] ?? null));
        $addSpace(6);
        $addLine('DESCRICAO DETALHADA', 12, 18);
        $addLine($this->value($warning['descricao'] ?? null), 10, 14);
        $addSpace(6);
        $addLine('OCORRENCIA VINCULADA', 12, 18);
        $addLine('Tipo da ocorrencia: ' . $this->value($warning['ocorrencia_tipo_label'] ?? $warning['ocorrencia_tipo'] ?? null));
        $addLine('Data e hora da ocorrencia: ' . $this->formatDateTime($warning['ocorrencia_data_hora'] ?? null));
        $addLine('Descricao da ocorrencia: ' . $this->value($warning['ocorrencia_descricao'] ?? null), 10, 14);
        $addSpace(6);
        $addLine('RESPONSAVEL PELA APLICACAO', 12, 18);
        $addLine($this->value($warning['responsavel_nome'] ?? null));
        $addLine('Gerado em: ' . (new DateTimeImmutable())->format('d/m/Y H:i'));
        $addSpace(22);
        $addLine('Assinatura do responsavel: ________________________________________________');
        $addSpace(12);
        $addLine('Assinatura do colaborador: _______________________________________________');

        return $this->renderPdf($pages);
    }

    private function renderPdf(array $pages): string {
        $objects = [];
        $pageCount = count($pages);
        $pageObjectIds = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjectId = 4 + ($i * 2);
            $contentObjectId = $pageObjectId + 1;
            $pageObjectIds[] = $pageObjectId . ' 0 R';

            $stream = $this->buildPageStream($pages[$i]);
            $objects[$pageObjectId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentObjectId . ' 0 R >>';
            $objects[$contentObjectId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageObjectIds) . '] /Count ' . $pageCount . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function buildPageStream(array $lines): string {
        $stream = '';

        foreach ($lines as $line) {
            $stream .= "BT /F1 " . (int) $line['fontSize'] . " Tf 50 " . (int) $line['y'] . " Td ("
                . $this->escapePdfString($line['text'])
                . ") Tj ET\n";
        }

        return $stream;
    }

    private function wrapText(string $text, int $limit): array {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?: '';

        if ($text === '') {
            return ['Nao informado'];
        }

        $words = explode(' ', $text);
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;

            if (strlen($this->toPdfEncoding($candidate)) > $limit && $line !== '') {
                $lines[] = $line;
                $line = $word;
                continue;
            }

            $line = $candidate;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function escapePdfString(string $text): string {
        $encoded = $this->toPdfEncoding($text);
        $encoded = str_replace('\\', '\\\\', $encoded);
        $encoded = str_replace('(', '\\(', $encoded);
        $encoded = str_replace(')', '\\)', $encoded);
        return str_replace(["\r", "\n"], ' ', $encoded);
    }

    private function toPdfEncoding(string $text): string {
        $converted = false;

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        }

        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '', $text) ?: '';
        }

        return $converted;
    }

    private function value($value): string {
        $value = trim((string) $value);
        return $value !== '' ? $value : 'Nao informado';
    }

    private function formatDate($value): string {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Nao informado';
        }

        try {
            return (new DateTimeImmutable(substr($value, 0, 10)))->format('d/m/Y');
        } catch (Throwable $e) {
            return $value;
        }
    }

    private function formatDateTime($value): string {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Nao informado';
        }

        try {
            return (new DateTimeImmutable($value))->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return $value;
        }
    }
}
