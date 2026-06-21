<?php
declare(strict_types=1);

/**
 * Соответствие блока калькулятора (s1–s6) теме обращения в кабинете.
 */
function appeal_topic_for_calc_service_id(string $serviceId): string
{
    return match ($serviceId) {
        's2', 's6' => 'bad_product',
        's1', 's3', 's4', 's5' => 'other',
        default => 'other',
    };
}

/**
 * Выбор темы по суммам по услугам (руб.): берётся услуга с наибольшей суммой, при равенстве — приоритет s6…s1.
 *
 * @param array<string,int|float> $sumByService
 */
function appeal_topic_preset_from_service_totals(array $sumByService): string
{
    if ($sumByService === []) {
        return 'other';
    }
    $ints = [];
    foreach ($sumByService as $sid => $v) {
        $ints[(string) $sid] = (int) round((float) $v);
    }
    $max = max($ints);
    $candidates = array_keys(array_filter($ints, static fn (int $v): bool => $v === $max));
    $tieOrder = ['s6', 's2', 's5', 's3', 's4', 's1'];
    foreach ($tieOrder as $id) {
        if (in_array($id, $candidates, true)) {
            return appeal_topic_for_calc_service_id($id);
        }
    }
    sort($candidates);

    return appeal_topic_for_calc_service_id((string) ($candidates[0] ?? 'other'));
}

/** Проверка значения темы для appeals.topic / requests.appeal_topic_preset. */
function appeal_topic_normalize(string $topic): string
{
    $allowed = ['bad_product', 'delay', 'warranty_refusal', 'housing', 'other'];

    return in_array($topic, $allowed, true) ? $topic : 'other';
}

/**
 * Убирает из строки услуги калькулятора хвост с суммой (как в списке обращений без рублей).
 * Примеры: «… — Вариант: 8000 ₽» → «… — Вариант»; «…: 3 ч × 880 ₽ = 2640 ₽» → «…».
 */
function appeal_strip_price_suffix(string $line): string
{
    $line = trim($line);
    if ($line === '') {
        return $line;
    }
    // Почасовая / экспертиза: «: N ч × 880 ₽ = 2640 ₽»
    $line = (string) preg_replace('/:\s*\d+\s*ч\s*×\s*\d+\s*₽\s*=\s*[\d\s]+\s*₽\s*$/u', '', $line);
    // Обычная строка: «: 8000 ₽» или «: 1 000 ₽»
    $line = (string) preg_replace('/:\s*[\d\s]+\s*₽\s*$/u', '', $line);

    return trim($line);
}

/**
 * Краткая подпись для списка обращений: первая позиция из блока услуг калькулятора в description.
 * Классификатор topic у калькулятора часто «other», а пользователь ожидает видеть выбранную услугу.
 */
function appeal_subject_line_from_description(?string $description, int $maxLen = 140): ?string
{
    if ($description === null || $description === '') {
        return null;
    }
    $headers = [
        'Услуги из заказа калькулятора:',
        'Услуги из оплаченного заказа:',
    ];
    foreach ($headers as $header) {
        $pos = function_exists('mb_stripos')
            ? mb_stripos($description, $header, 0, 'UTF-8')
            : stripos($description, $header);
        if ($pos === false) {
            continue;
        }
        $start = $pos + (function_exists('mb_strlen') ? mb_strlen($header, 'UTF-8') : strlen($header));
        $after = function_exists('mb_substr')
            ? mb_substr($description, $start, null, 'UTF-8')
            : substr($description, $start);
        $after = ltrim((string) $after, "\r\n");
        if ($after === '') {
            continue;
        }
        $parts = preg_split('/\R/u', $after, 2);
        $firstLine = trim((string) ($parts[0] ?? ''));
        $firstLine = preg_replace('/^[•\-\*]\s*/u', '', $firstLine);
        $firstLine = trim($firstLine);
        if ($firstLine === '') {
            continue;
        }
        $firstLine = appeal_strip_price_suffix($firstLine);
        if ($firstLine === '') {
            continue;
        }

        return appeal_truncate_one_line($firstLine, $maxLen);
    }

    return null;
}

function appeal_truncate_one_line(string $line, int $maxLen): string
{
    if ($maxLen < 4) {
        $maxLen = 4;
    }
    if (function_exists('mb_strlen') && mb_strlen($line, 'UTF-8') > $maxLen) {
        return rtrim(mb_substr($line, 0, $maxLen - 1, 'UTF-8')) . '…';
    }
    if (strlen($line) > $maxLen) {
        return rtrim(substr($line, 0, $maxLen - 1)) . '…';
    }

    return $line;
}

/**
 * Текст выбранных услуг из заявки калькулятора (для блока обращения).
 *
 * @param array<string, mixed> $row строка requests: appeal_service_summary, comment
 */
function appeal_service_summary_for_display(array $row): string
{
    $col = isset($row['appeal_service_summary']) ? trim((string) $row['appeal_service_summary']) : '';
    if ($col !== '') {
        return $col;
    }
    $comment = isset($row['comment']) ? (string) $row['comment'] : '';
    if ($comment !== '' && preg_match('/Состав заказа:\r?\n([\s\S]*?)(?:\r?\n\r?\nСитуация:|\z)/u', $comment, $m)) {
        $parsed = trim($m[1]);
        if ($parsed !== '') {
            return $parsed;
        }
    }

    return '';
}

/**
 * Разбор текста обращения для экрана «Описание»: блок услуг / тема и текст ситуации.
 *
 * @return array{services_block: string, user_text: string, has_split: bool}
 */
function appeal_split_description_for_display(string $description): array
{
    $description = trim($description);
    if ($description === '') {
        return ['services_block' => '', 'user_text' => '', 'has_split' => false];
    }

    $calcHeaders = ['Услуги из заказа калькулятора:', 'Услуги из оплаченного заказа:'];
    foreach ($calcHeaders as $header) {
        $pos = function_exists('mb_stripos')
            ? mb_stripos($description, $header, 0, 'UTF-8')
            : stripos($description, $header);
        if ($pos === false) {
            continue;
        }
        $hLen = function_exists('mb_strlen') ? mb_strlen($header, 'UTF-8') : strlen($header);
        $afterHeader = trim(function_exists('mb_substr')
            ? mb_substr($description, $pos + $hLen, null, 'UTF-8')
            : substr($description, $pos + $hLen));
        if ($afterHeader === '') {
            return ['services_block' => $header, 'user_text' => '', 'has_split' => true];
        }
        $parts = preg_split('/\R\s*\R/u', $afterHeader, 2);
        if (is_array($parts) && isset($parts[1]) && trim($parts[1]) !== '') {
            return [
                'services_block' => trim((string) $parts[0]),
                'user_text' => trim((string) $parts[1]),
                'has_split' => true,
            ];
        }
        $lines = preg_split('/\R/u', $afterHeader);
        $serviceLines = [];
        $i = 0;
        foreach ($lines as $idx => $line) {
            $t = trim((string) $line);
            if ($t === '') {
                $i = $idx + 1;
                break;
            }
            if (preg_match('/^[•\-\*]\s*/u', $t)) {
                $serviceLines[] = $line;
                $i = $idx + 1;
            } else {
                $i = $idx;
                break;
            }
        }
        $remaining = trim(implode("\n", array_slice($lines, $i)));

        return [
            'services_block' => $serviceLines !== [] ? implode("\n", $serviceLines) : $afterHeader,
            'user_text' => $remaining,
            'has_split' => true,
        ];
    }

    $classifier = 'Тема по классификатору:';
    $pos = function_exists('mb_stripos')
        ? mb_stripos($description, $classifier, 0, 'UTF-8')
        : stripos($description, $classifier);
    if ($pos !== false) {
        $hLen = function_exists('mb_strlen') ? mb_strlen($classifier, 'UTF-8') : strlen($classifier);
        $after = trim(function_exists('mb_substr')
            ? mb_substr($description, $pos + $hLen, null, 'UTF-8')
            : substr($description, $pos + $hLen));
        $parts = preg_split('/\R\s*\R/u', $after, 2);
        $topicLine = trim((string) ($parts[0] ?? ''));
        $userText = isset($parts[1]) ? trim((string) $parts[1]) : '';

        return [
            'services_block' => $classifier . ' ' . $topicLine,
            'user_text' => $userText,
            'has_split' => true,
        ];
    }

    return ['services_block' => '', 'user_text' => $description, 'has_split' => false];
}

/**
 * Подсказка блока услуги калькулятора (s1–s6) для публикации отзыва после модерации.
 */
function appeal_guess_calc_service_id_from_text(string $servicesBlock, string $userText): string
{
    $hay = mb_strtolower($servicesBlock . "\n" . $userText, 'UTF-8');
    if (strpos($hay, 'товароведческ') !== false) {
        return 's6';
    }
    if (preg_match('/консультаци[яи].*экспертиз|экспертиз[аы].*товар/u', $hay)) {
        return 's2';
    }
    if (preg_match('/судебн|заседани|ведение дела|апелляц|кассац/u', $hay)) {
        return 's5';
    }
    if (preg_match('/юрлиц|ип\b|ответ на претензию|отзыв на иск/u', $hay)) {
        return 's4';
    }
    if (preg_match('/претензи|исков|жалоб|ходатайств/u', $hay)) {
        return 's3';
    }
    if (strpos($hay, 'консультац') !== false) {
        return 's1';
    }

    return 's1';
}
