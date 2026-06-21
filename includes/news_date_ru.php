<?php
declare(strict_types=1);

/**
 * Дата публикации новости для отображения (русские названия месяцев в родительном падеже).
 */
function news_published_label_ru(?string $publishedAt, bool $withTime = false): string
{
    if ($publishedAt === null || $publishedAt === '') {
        return '—';
    }
    $ts = strtotime($publishedAt);
    if ($ts === false) {
        return $publishedAt;
    }
    static $months = [
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря',
    ];
    $n = (int) date('n', $ts);
    $day = (int) date('j', $ts);
    $year = (int) date('Y', $ts);
    $mname = $months[$n] ?? date('m', $ts);
    $out = $day . ' ' . $mname . ' ' . $year;
    if ($withTime) {
        $out .= ', ' . date('H:i', $ts);
    }

    return $out;
}
