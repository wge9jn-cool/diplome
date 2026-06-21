<?php

require_once __DIR__ . '/config.php';

/**
 * Отправка SMS через SMS Aero.
 *
 * Авторизация: HTTP Basic Auth (login=email, password=api_key).
 * Endpoint: https://gate.smsaero.ru/v2/sms/send
 */
function sendSms(string $phone, string $message): bool
{
    $phone = preg_replace('/\D+/', '', $phone);
    $GLOBALS['SMS_LAST_ERROR'] = null;
    if (
        $phone === '' ||
        !defined('SMS_AERO_LOGIN') || SMS_AERO_LOGIN === '' || SMS_AERO_LOGIN === 'ВАШ_EMAIL_В_SMSAERO' ||
        !defined('SMS_AERO_API_KEY') || SMS_AERO_API_KEY === ''
    ) {
        $GLOBALS['SMS_LAST_ERROR'] = 'SMS Aero не настроен: проверьте SMS_AERO_LOGIN (email) и SMS_AERO_API_KEY в config.php';
        sms_aero_log('config_missing', [
            'phone_last4' => substr($phone, -4),
            'login_set' => defined('SMS_AERO_LOGIN') && SMS_AERO_LOGIN !== '' && SMS_AERO_LOGIN !== 'ВАШ_EMAIL_В_SMSAERO',
            'key_set' => defined('SMS_AERO_API_KEY') && SMS_AERO_API_KEY !== '',
        ]);
        return false;
    }

    $query = [
        'number' => $phone,
        'text'   => $message,
        'sign'   => defined('SMS_AERO_SIGN') ? SMS_AERO_SIGN : 'SMS Aero',
    ];

    $url = 'https://gate.smsaero.ru/v2/sms/send?' . http_build_query($query, '', '&');

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, SMS_AERO_LOGIN . ':' . SMS_AERO_API_KEY);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        $code = curl_errno($ch);
        $GLOBALS['SMS_LAST_ERROR'] = 'curl_error: ' . $err;
        sms_aero_log('curl_error', [
            'errno' => $code,
            'error' => $err,
            'phone_last4' => substr($phone, -4),
        ]);
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    $data = json_decode($response, true);
    sms_aero_log('response', [
        'phone_last4' => substr($phone, -4),
        'sign' => defined('SMS_AERO_SIGN') ? SMS_AERO_SIGN : 'SMS Aero',
        'raw' => $response,
    ]);
    if (!is_array($data) || empty($data['success'])) {
        $GLOBALS['SMS_LAST_ERROR'] = is_array($data) ? ('smsaero_error: ' . ($data['message'] ?? 'unknown')) : 'smsaero_error: invalid json';
        return false;
    }
    return true;
}

function sms_aero_log(string $event, array $payload): void
{
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $file = $dir . '/sms_log.txt';
    $line = date('Y-m-d H:i:s') . ' [' . $event . '] ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}

function sms_last_error(): ?string
{
    return $GLOBALS['SMS_LAST_ERROR'] ?? null;
}

