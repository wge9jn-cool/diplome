<?php
declare(strict_types=1);

/** Нормализация оценки 1–5 (для старых записей без поля — 5). */
function review_normalize_stars(mixed $v): int
{
    $n = (int) $v;
    if ($n < 1 || $n > 5) {
        return 5;
    }

    return $n;
}

/**
 * Ряд звёзд для публичной страницы (допустимые только целые 1–5).
 */
function review_stars_html(int $stars): string
{
    $stars = max(1, min(5, $stars));
    $inner = '';
    for ($i = 1; $i <= 5; ++$i) {
        $on = $i <= $stars;
        $inner .= '<span class="svc-review-card__star' . ($on ? ' svc-review-card__star--on' : ' svc-review-card__star--off') . '" aria-hidden="true">'
            . ($on ? '★' : '☆') . '</span>';
    }

    return '<span class="svc-review-card__stars" role="img" aria-label="Оценка ' . $stars . ' из 5">' . $inner . '</span>';
}

/** Компактная строка для админки / кабинета (без лишней вёрстки). */
function review_stars_text(int $stars): string
{
    $stars = max(1, min(5, $stars));
    $filled = str_repeat('★', $stars);
    $empty = str_repeat('☆', 5 - $stars);

    return $filled . $empty;
}
