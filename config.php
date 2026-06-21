<?php 

define('APP_URL', 'http://localhost');

/** WebSocket-чат: URL сервера (локально ws://localhost:8080, на сайте wss://ваш-домен или wss://ws.ваш-домен) */
define('CHAT_WS_URL', 'ws://localhost:8080');
/** Общий секрет с Node-сервером (realtime/chat-server/.env → WS_SECRET) */
define('CHAT_WS_SECRET', 'diplom-chat-ws-change-me-on-production');

define('DB_HOST', 'MySQL-8.0');
define('DB_NAME', 'diplom');
define('DB_USER', 'root');
define('DB_PASS', '');

// Настройки SMS-шлюза SMS Aero
define('SMS_AERO_LOGIN', 'kataytsev.fadey@mail.ru'); // e-mail, на который зарегистрирован аккаунт SMS Aero
define('SMS_AERO_API_KEY', 'JKZMDYKXS7T6ECv3f9UGo_J9q9_Wmvan'); // ваш API-ключ
// Подпись отправителя (обязательна для SMS Aero). Если нет своей подписи, попробуйте 'SMS Aero' (часто доступна в тесте).
define('SMS_AERO_SIGN', 'SMS Aero');

/** Виджет Яндекс.Карт: офис (г. Курган, ул. Максима Горького, 209), координаты по OpenStreetMap */
define(
    'YANDEX_MAP_OFFICE_WIDGET',
    'https://yandex.ru/map-widget/v1/?ll=65.3639086%2C55.4455637&z=17&pt=65.3639086%2C55.4455637,pm2rdm'
);

