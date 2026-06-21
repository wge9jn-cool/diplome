<?php
/**
 * Каталог услуг: калькулятор, страница «Услуги», checkout.
 * Фиксированный набор из calc_catalog_builtin.php (без БД и без правок из админки).
 */
declare(strict_types=1);

require_once __DIR__ . '/calc_catalog_builtin.php';

/**
 * @return list<array<string, mixed>>
 */
function calc_catalog_list(): array
{
    static $list = null;
    if ($list === null) {
        $list = calc_catalog_builtin_list();
    }

    return $list;
}

/** Список id услуг для проверок в админке. @return list<string> */
function calc_catalog_valid_service_ids(): array
{
    $ids = [];
    foreach (calc_catalog_list() as $svc) {
        if (!empty($svc['id'])) {
            $ids[] = (string) $svc['id'];
        }
    }

    return array_values(array_unique($ids));
}

/**
 * Короткое название варианта для строки заказа на сервере (без хвоста с ценой).
 */
function calc_catalog_variant_receipt_title(string $displayTitle): string
{
    $t = preg_replace('/\s+—\s+.*$/u', '', trim($displayTitle));

    return $t !== '' ? $t : $displayTitle;
}

/** Заголовок услуги без ведущего номера «1. » (для страницы «Услуги»). */
function calc_catalog_service_title_plain(string $title): string
{
    $t = preg_replace('/^\d+\.\s+/u', '', trim($title));

    return $t !== '' ? $t : $title;
}

/**
 * Каталог для клиентского JS (как раньше в script.js).
 *
 * @return list<array<string, mixed>>
 */
function calc_catalog_for_js(): array
{
    $out = [];
    foreach (calc_catalog_list() as $svc) {
        $row = [
            'id' => $svc['id'],
            'title' => $svc['title'],
            'hint' => $svc['hint'],
            'variants' => [],
        ];
        if (!empty($svc['kind'])) {
            $row['kind'] = $svc['kind'];
        }
        if (!empty($svc['warning'])) {
            $row['warning'] = $svc['warning'];
        }
        if (!empty($svc['extras'])) {
            $row['extras'] = $svc['extras'];
        }
        foreach ($svc['variants'] as $v) {
            $jv = ['id' => $v['id'], 'title' => $v['title']];
            if (array_key_exists('price', $v)) {
                $jv['price'] = (int) $v['price'];
            }
            if (array_key_exists('rate', $v)) {
                $jv['rate'] = (int) $v['rate'];
            } elseif (array_key_exists('price', $v)) {
                $jv['kind'] = 'fixed';
            }
            foreach (['kind', 'once', 'requiresContact', 'allowQty'] as $k) {
                if (!array_key_exists($k, $v)) {
                    continue;
                }
                if ($k === 'once' || $k === 'requiresContact' || $k === 'allowQty') {
                    $jv[$k] = (bool) $v[$k];
                } else {
                    $jv[$k] = $v[$k];
                }
            }
            $row['variants'][] = $jv;
        }
        $out[] = $row;
    }

    return $out;
}

/**
 * Формат каталога для calc_checkout.php (ключи — id услуг, во встроенном каталоге s1…s6).
 *
 * @return array<string, array<string, mixed>>
 */
function calc_catalog_checkout_map(): array
{
    $map = [];
    foreach (calc_catalog_list() as $svc) {
        $id = $svc['id'];
        $title = preg_replace('/^\d+\.\s+/u', '', $svc['title']);
        $entry = [
            'title' => $title,
            'variants' => [],
            'extras' => [],
        ];
        if (($svc['kind'] ?? '') === 'quote') {
            $entry['kind'] = 'quote';
            foreach ($svc['variants'] as $v) {
                $entry['variants'][$v['id']] = [
                    'title' => $v['title'],
                ];
            }
            $map[$id] = $entry;
            continue;
        }
        if (($svc['kind'] ?? '') === 'hourly') {
            $entry['kind'] = 'hourly';
            foreach ($svc['variants'] as $v) {
                $entry['variants'][$v['id']] = [
                    'title' => $v['title'],
                    'rate' => (int) $v['rate'],
                ];
            }
            $map[$id] = $entry;
            continue;
        }
        foreach ($svc['variants'] as $v) {
            $entry['variants'][$v['id']] = [
                'title' => calc_catalog_variant_receipt_title($v['title']),
                'price' => (int) $v['price'],
                'requires_contact' => !empty($v['requiresContact']),
            ];
        }
        foreach ($svc['extras'] ?? [] as $ex) {
            $entry['extras'][$ex['id']] = [
                'title' => $ex['id'] === 'urgency' ? 'Срочность +30%' : $ex['title'],
                'percent' => (int) $ex['percent'],
            ];
        }
        $map[$id] = $entry;
    }

    return $map;
}

/**
 * Строки для страницы services.php (id, заголовок без номера, описание, отзыв для модалки).
 *
 * @return list<array{id: string, title_plain: string, description: string, reviews: list<array{name: string, text: string, stars?: int, published_at?: string}>}>
 */
function calc_catalog_services_for_page(): array
{
    $rows = [];
    foreach (calc_catalog_list() as $svc) {
        $reviews = $svc['reviews'] ?? [];
        if (!is_array($reviews)) {
            $reviews = [];
        }
        $rows[] = [
            'id' => (string) $svc['id'],
            'title_plain' => calc_catalog_service_title_plain((string) $svc['title']),
            'description' => (string) $svc['description'],
            'reviews' => $reviews,
        ];
    }

    return $rows;
}
