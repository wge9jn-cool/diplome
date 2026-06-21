-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0:3306
-- Время создания: Июн 21 2026 г., 22:47
-- Версия сервера: 8.0.43
-- Версия PHP: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `diplom`
--

-- --------------------------------------------------------

--
-- Структура таблицы `appeals`
--

CREATE TABLE `appeals` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `topic` enum('bad_product','delay','warranty_refusal','housing','other') NOT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT NULL,
  `description` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `generated_doc_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `appeals`
--

INSERT INTO `appeals` (`id`, `user_id`, `topic`, `difficulty`, `description`, `attachment_path`, `generated_doc_path`, `created_at`, `updated_at`) VALUES
(2, 5, 'other', NULL, 'мчмсыв', 'uploads/appeals/5_69b7d70b1d2b79.58275144.jpg', 'uploads/docs/2_draft_user_20260316_151033.txt', '2026-03-16 15:10:19', '2026-03-16 15:10:33'),
(3, 5, 'warranty_refusal', NULL, 'ауцац', NULL, 'uploads/docs/3_draft_user_20260319_161943.txt', '2026-03-16 22:00:24', '2026-03-19 16:19:43'),
(5, 5, 'delay', NULL, 'фывпфывп пфывпфывп пукпцуп пцупцуп вывпывп пывпывп', 'uploads/appeals/5_69c6cf95b57998.85242439.jpg', NULL, '2026-03-27 23:42:29', '2026-03-27 23:42:29'),
(9, 5, 'other', NULL, 'Услуги из заказа калькулятора:\n• Составление документов (юрлица/ИП) — Ответ на претензию: 4000 ₽\n\nвафыпафыцп', NULL, NULL, '2026-05-11 20:59:51', '2026-05-11 20:59:51'),
(10, 5, 'other', NULL, 'Услуги из заказа калькулятора:\n• Составление документов (юрлица/ИП) — Исковое заявление: 8000 ₽\n\nн3курукр', NULL, NULL, '2026-05-11 21:00:26', '2026-05-11 21:00:26'),
(11, 5, 'bad_product', NULL, 'Услуги из заказа калькулятора:\n• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽\n\nсыфвмывм', 'uploads/appeals/5_6a023c9202a4b9.48663309.png', NULL, '2026-05-12 01:31:14', '2026-05-12 01:31:14'),
(12, 5, 'other', NULL, 'Услуги из заказа калькулятора:\n• Составление документов (физлица) — Простая претензия (типовая): 3000 ₽\n\nл', NULL, NULL, '2026-05-12 01:54:10', '2026-05-12 01:54:10'),
(13, 5, 'bad_product', NULL, 'Услуги из заказа калькулятора:\n• Товароведческая экспертиза — Обувные изделия: 1 ч × 880 ₽ = 880 ₽\n\nпфывп', NULL, 'uploads/docs/13_draft_20260512_172558.txt', '2026-05-12 16:34:45', '2026-05-12 17:25:58'),
(14, 5, 'bad_product', NULL, 'Услуги из заказа калькулятора:\n• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽\n\nфпук', NULL, NULL, '2026-05-13 00:22:32', '2026-05-13 00:22:32'),
(15, 5, 'other', NULL, 'Услуги из заказа калькулятора:\n• Консультация по защите прав потребителей — Письменная консультация: 2500 ₽\n\nфеукоцфкеофкео', 'uploads/appeals/5_6a0383e6ab9279.80749953.png', NULL, '2026-05-13 00:47:50', '2026-05-13 00:47:50'),
(16, 5, 'bad_product', NULL, 'Услуги из заказа калькулятора:\n• Товароведческая экспертиза — Обувные изделия: 1 ч × 880 ₽ = 880 ₽\n\nфыкрвыкрыуп', NULL, NULL, '2026-05-13 00:55:31', '2026-05-13 00:55:31'),
(17, 5, 'other', NULL, 'Услуги из заказа калькулятора:\n• Консультация по защите прав потребителей — Устная консультация (60 мин): 1800 ₽\n\nрфарвыааряувкр', NULL, NULL, '2026-05-13 00:59:03', '2026-05-13 00:59:03'),
(18, 5, 'other', NULL, 'Услуги из заказа калькулятора:\n• Консультация по экспертизе товаров — Полная консультация с анализом недостатков: 2000 ₽\n• Составление документов (физлица) — Сложная претензия (с расчётом неустойки): 5000 ₽\n\nфвтквыатыяват', NULL, 'uploads/docs/18_6a37ee28b204b7.66421586.docx', '2026-05-13 01:25:25', '2026-06-21 18:59:04');

-- --------------------------------------------------------

--
-- Структура таблицы `appeal_archive_comments`
--

CREATE TABLE `appeal_archive_comments` (
  `id` int UNSIGNED NOT NULL,
  `appeal_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL DEFAULT '5' COMMENT '1–5',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = на сайте без имени',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `calc_service_id` varchar(8) DEFAULT NULL COMMENT 's1–s6 из каталога калькулятора, задаётся при одобрении',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `moderated_at` datetime DEFAULT NULL,
  `moderator_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `appeal_archive_comments`
--

INSERT INTO `appeal_archive_comments` (`id`, `appeal_id`, `user_id`, `body`, `rating`, `is_anonymous`, `status`, `calc_service_id`, `created_at`, `moderated_at`, `moderator_id`) VALUES
(2, 14, 5, 'все прошло хорошо, обращайтесь', 5, 0, 'rejected', NULL, '2026-05-13 00:23:21', '2026-05-13 00:26:18', 5);

-- --------------------------------------------------------

--
-- Структура таблицы `appeal_comments`
--

CREATE TABLE `appeal_comments` (
  `id` int UNSIGNED NOT NULL,
  `appeal_id` int UNSIGNED NOT NULL,
  `admin_id` int UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `appeal_messages`
--

CREATE TABLE `appeal_messages` (
  `id` int UNSIGNED NOT NULL,
  `appeal_id` int UNSIGNED NOT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `sender_id` int UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `appeal_messages`
--

INSERT INTO `appeal_messages` (`id`, `appeal_id`, `sender_type`, `sender_id`, `message`, `created_at`) VALUES
(7, 5, 'admin', 5, 'привет', '2026-03-27 23:47:40'),
(8, 5, 'admin', 5, 'привет', '2026-03-27 23:48:07'),
(9, 5, 'admin', 5, 'приветт', '2026-03-27 23:55:04'),
(10, 5, 'user', 5, 'привет', '2026-03-28 14:58:35'),
(11, 5, 'admin', 5, 'dfvd', '2026-04-12 18:40:54'),
(13, 13, 'admin', 5, 'das', '2026-05-12 17:25:23'),
(14, 13, 'admin', 5, 'dsa', '2026-05-12 17:25:26'),
(15, 13, 'admin', 5, 'dsa', '2026-05-12 17:25:44'),
(16, 18, 'admin', 5, 'фаыфыа', '2026-05-13 01:25:30'),
(18, 18, 'admin', 5, 'fwesfw', '2026-05-15 23:13:40');

-- --------------------------------------------------------

--
-- Структура таблицы `appeal_statuses`
--

CREATE TABLE `appeal_statuses` (
  `id` int UNSIGNED NOT NULL,
  `appeal_id` int UNSIGNED NOT NULL,
  `status` enum('accepted','processing','answered','completed','rejected') NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `appeal_statuses`
--

INSERT INTO `appeal_statuses` (`id`, `appeal_id`, `status`, `comment`, `created_at`) VALUES
(4, 2, 'accepted', 'Обращение принято к рассмотрению.', '2026-03-16 15:10:19'),
(5, 3, 'accepted', 'Обращение принято к рассмотрению.', '2026-03-16 22:00:24'),
(6, 3, 'processing', NULL, '2026-03-16 22:03:12'),
(9, 5, 'accepted', 'Обращение принято к рассмотрению.', '2026-03-27 23:42:29'),
(13, 9, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-11 20:59:51'),
(14, 10, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-11 21:00:26'),
(15, 11, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-12 01:31:14'),
(16, 12, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-12 01:54:10'),
(17, 13, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-12 16:34:45'),
(18, 13, 'completed', NULL, '2026-05-12 17:05:16'),
(19, 14, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-13 00:22:32'),
(20, 14, 'completed', NULL, '2026-05-13 00:22:58'),
(21, 12, 'completed', NULL, '2026-05-13 00:40:33'),
(22, 15, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-13 00:47:50'),
(23, 15, 'completed', NULL, '2026-05-13 00:49:12'),
(24, 16, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-13 00:55:31'),
(25, 17, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-13 00:59:03'),
(26, 18, 'accepted', 'Обращение принято к рассмотрению.', '2026-05-13 01:25:25');

-- --------------------------------------------------------

--
-- Структура таблицы `news`
--

CREATE TABLE `news` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `published_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `phone_verification_codes`
--

CREATE TABLE `phone_verification_codes` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `phone_verification_codes`
--

INSERT INTO `phone_verification_codes` (`id`, `user_id`, `code`, `expires_at`, `used`, `created_at`) VALUES
(5, 5, '803696', '2026-03-16 14:54:23', 0, '2026-03-16 14:44:23'),
(6, 5, '135989', '2026-03-16 14:58:13', 0, '2026-03-16 14:48:13'),
(7, 5, '977774', '2026-03-16 15:07:06', 0, '2026-03-16 14:57:06'),
(8, 5, '115419', '2026-03-16 15:16:28', 1, '2026-03-16 15:06:28'),
(10, 10, '232868', '2026-04-09 15:06:30', 1, '2026-04-09 14:56:30'),
(11, 14, '955279', '2026-06-21 22:22:46', 1, '2026-06-21 22:12:46');

-- --------------------------------------------------------

--
-- Структура таблицы `requests`
--

CREATE TABLE `requests` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `service` varchar(50) NOT NULL,
  `sum` int UNSIGNED NOT NULL DEFAULT '0',
  `comment` text,
  `payment_method` varchar(20) NOT NULL DEFAULT 'yookassa',
  `status` varchar(100) NOT NULL DEFAULT 'Новое',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `appeal_topic_preset` varchar(32) DEFAULT NULL,
  `appeal_service_summary` text,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `service`, `sum`, `comment`, `payment_method`, `status`, `is_paid`, `appeal_topic_preset`, `appeal_service_summary`, `created_at`) VALUES
(1, 5, 'calculator', 73800, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Устная консультация (60 мин): 1800 ₽\n• Консультация по экспертизе товаров — Полная консультация с анализом недостатков: 2000 ₽\n• Составление документов (физлица) — Исковое заявление (сложное): 10000 ₽\n• Составление документов (юрлица/ИП) — Исковое заявление: 8000 ₽\n• Представительство в суде — Ведение дела «под ключ» (до 3 заседаний) + срочно (+30%): 52000 ₽', 'later', 'Новое', 0, NULL, NULL, '2026-03-18 14:02:42'),
(2, 5, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Устная консультация (до 15 мин) — первая бесплатная: 0 ₽\n• Консультация по защите прав потребителей — Устная консультация (30 мин): 1000 ₽', 'yookassa', 'Оплачено', 1, NULL, NULL, '2026-03-19 17:02:31'),
(3, 5, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Устная консультация (до 15 мин) — первая бесплатная: 0 ₽\n• Консультация по защите прав потребителей — Устная консультация (30 мин): 1000 ₽', 'yookassa', 'Оплачено', 1, NULL, NULL, '2026-03-20 01:14:06'),
(4, 5, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Устная консультация (30 мин): 1000 ₽', 'yookassa', 'Оплачено', 1, NULL, NULL, '2026-03-25 16:23:57'),
(5, 10, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', 'yookassa', 'Ожидает оплаты', 0, NULL, NULL, '2026-04-09 14:59:28'),
(6, 5, 'calculator', 3800, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Устная консультация (60 мин): 1800 ₽\n• Консультация по экспертизе товаров — Полная консультация с анализом недостатков: 2000 ₽', 'later', 'Новое', 0, NULL, NULL, '2026-04-12 18:27:23'),
(7, 5, 'calculator', 3000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по экспертизе товаров — Полная консультация с анализом недостатков: 2000 ₽\n• Консультация по защите прав потребителей — Устная консультация (30 мин): 1000 ₽', 'later', 'Новое', 0, NULL, NULL, '2026-04-12 18:27:38'),
(8, 5, 'calculator', 2500, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Письменная консультация: 2500 ₽', 'later', 'Новое', 0, NULL, NULL, '2026-04-12 18:29:40'),
(9, 5, 'calculator', 2500, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Письменная консультация: 2500 ₽', 'later', 'Новое', 0, NULL, NULL, '2026-04-12 18:34:10'),
(10, 5, 'calculator', 2500, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Письменная консультация: 2500 ₽', 'yookassa', 'Оплачено', 1, NULL, NULL, '2026-04-12 18:34:25'),
(11, 5, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Устная консультация (30 мин): 1000 ₽', 'yookassa', 'Оплачено', 1, NULL, NULL, '2026-05-11 20:02:50'),
(12, 5, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', 'yookassa', 'Оплачено', 1, 'bad_product', NULL, '2026-05-11 20:22:00'),
(13, 5, 'calculator', 3000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Составление документов (физлица) — Простая претензия (типовая): 3000 ₽', 'yookassa', 'Оплачено', 1, 'other', '• Составление документов (физлица) — Простая претензия (типовая): 3000 ₽', '2026-05-11 20:35:07'),
(14, 5, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', 'yookassa', 'Оплачено', 1, 'bad_product', '• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', '2026-05-11 20:47:56'),
(15, 5, 'calculator', 3000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Составление документов (физлица) — Простая претензия (типовая): 3000 ₽', 'yookassa', 'Оплачено', 1, 'other', '• Составление документов (физлица) — Простая претензия (типовая): 3000 ₽', '2026-05-11 20:48:27'),
(16, 5, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', 'yookassa', 'Оплачено', 1, 'bad_product', '• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', '2026-05-11 20:50:38'),
(17, 5, 'calculator', 4000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Составление документов (юрлица/ИП) — Ответ на претензию: 4000 ₽', 'yookassa', 'Оплачено', 1, 'other', '• Составление документов (юрлица/ИП) — Ответ на претензию: 4000 ₽', '2026-05-11 20:51:25'),
(18, 5, 'calculator', 8000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Составление документов (юрлица/ИП) — Исковое заявление: 8000 ₽', 'yookassa', 'Оплачено', 1, 'other', '• Составление документов (юрлица/ИП) — Исковое заявление: 8000 ₽', '2026-05-11 21:00:12'),
(19, 5, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', 'later', 'Новое', 0, 'bad_product', '• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', '2026-05-12 01:12:48'),
(20, 5, 'calculator', 3000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Составление документов (физлица) — Простая претензия (типовая): 3000 ₽', 'later', 'Новое', 0, 'other', '• Составление документов (физлица) — Простая претензия (типовая): 3000 ₽', '2026-05-12 01:53:43'),
(21, 5, 'calculator', 880, 'Заявка из калькулятора.\n\nСостав заказа:\n• Товароведческая экспертиза — Обувные изделия: 1 ч × 880 ₽ = 880 ₽', 'later', 'Новое', 0, 'bad_product', '• Товароведческая экспертиза — Обувные изделия: 1 ч × 880 ₽ = 880 ₽', '2026-05-12 16:33:45'),
(22, 5, 'calculator', 1000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', 'later', 'Новое', 0, 'bad_product', '• Консультация по экспертизе товаров — Краткая консультация по документам: 1000 ₽', '2026-05-13 00:22:29'),
(23, 5, 'calculator', 2500, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Письменная консультация: 2500 ₽', 'later', 'Новое', 0, 'other', '• Консультация по защите прав потребителей — Письменная консультация: 2500 ₽', '2026-05-13 00:47:38'),
(24, 5, 'calculator', 880, 'Заявка из калькулятора.\n\nСостав заказа:\n• Товароведческая экспертиза — Обувные изделия: 1 ч × 880 ₽ = 880 ₽', 'later', 'Новое', 0, 'bad_product', '• Товароведческая экспертиза — Обувные изделия: 1 ч × 880 ₽ = 880 ₽', '2026-05-13 00:55:29'),
(25, 5, 'calculator', 1800, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по защите прав потребителей — Устная консультация (60 мин): 1800 ₽', 'later', 'Новое', 0, 'other', '• Консультация по защите прав потребителей — Устная консультация (60 мин): 1800 ₽', '2026-05-13 00:59:01'),
(26, 5, 'calculator', 7000, 'Заявка из калькулятора.\n\nСостав заказа:\n• Консультация по экспертизе товаров — Полная консультация с анализом недостатков: 2000 ₽\n• Составление документов (физлица) — Сложная претензия (с расчётом неустойки): 5000 ₽', 'later', 'Новое', 0, 'other', '• Консультация по экспертизе товаров — Полная консультация с анализом недостатков: 2000 ₽\n• Составление документов (физлица) — Сложная претензия (с расчётом неустойки): 5000 ₽', '2026-05-13 01:25:15');

-- --------------------------------------------------------

--
-- Структура таблицы `services`
--

CREATE TABLE `services` (
  `id` int UNSIGNED NOT NULL,
  `slug` varchar(32) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `hint` varchar(255) DEFAULT NULL COMMENT 'подзаголовок в калькуляторе',
  `calc_json` longtext COMMENT 'JSON: reviews, variants, extras, kind, warning',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 = скрыто везде'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings`
--

CREATE TABLE `settings` (
  `id` int UNSIGNED NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`) VALUES
(1, 'vk_link', 'https://vk.ru/club152590471');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_phone_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `role` enum('client','admin','employee') NOT NULL DEFAULT 'client',
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `is_phone_confirmed`, `role`, `is_blocked`, `created_at`) VALUES
(5, 'Кат Фад', 'kataytev.fadey@mail.ru', '8888', '$2y$10$LV/fG90B2QKvmydAFkOBVurLywLv4zEYzJs47feZgCN5sbtmkifcm', 1, 'admin', 0, '2026-03-16 15:06:28'),
(10, 'Осипова Ксения Алексеевна', 'ksewkue@gmail.com', '4444', '$2y$10$/y4eoMNsZU3oAf4H9oQYKeISHlqubOKC9bh0olBuekbMtDq8WPSxy', 1, 'client', 0, '2026-04-09 14:56:30'),
(11, 'Сотрудник 6767', 'employee6767@kosp.local', '6767', '$2y$10$bU/tXeezniVwEzH38gRePeDbCZu5XqAspIsQsb5hFtrj.YnZg.57K', 1, 'employee', 0, '2026-06-21 18:51:24'),
(14, 'Катайцев Фадей Евгеньевич', 'a.script@bk.ru', '79080038698', '$2y$10$uj07ommW8v1mTBPhrP.nS./5LLjwmeyUuJ0WHS7NmOS5wqtTY/B6.', 1, 'client', 0, '2026-06-21 22:12:46');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `appeals`
--
ALTER TABLE `appeals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_appeals_users` (`user_id`);

--
-- Индексы таблицы `appeal_archive_comments`
--
ALTER TABLE `appeal_archive_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aac_status_service` (`status`,`calc_service_id`),
  ADD KEY `idx_aac_appeal` (`appeal_id`),
  ADD KEY `fk_aac_user` (`user_id`),
  ADD KEY `fk_aac_moderator` (`moderator_id`);

--
-- Индексы таблицы `appeal_comments`
--
ALTER TABLE `appeal_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comments_appeal` (`appeal_id`),
  ADD KEY `fk_comments_admin` (`admin_id`);

--
-- Индексы таблицы `appeal_messages`
--
ALTER TABLE `appeal_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_msg_appeal` (`appeal_id`);

--
-- Индексы таблицы `appeal_statuses`
--
ALTER TABLE `appeal_statuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_status_appeal` (`appeal_id`);

--
-- Индексы таблицы `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `phone_verification_codes`
--
ALTER TABLE `phone_verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_codes_user` (`user_id`);

--
-- Индексы таблицы `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_requests_users` (`user_id`);

--
-- Индексы таблицы `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_services_slug` (`slug`);

--
-- Индексы таблицы `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `appeals`
--
ALTER TABLE `appeals`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `appeal_archive_comments`
--
ALTER TABLE `appeal_archive_comments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `appeal_comments`
--
ALTER TABLE `appeal_comments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `appeal_messages`
--
ALTER TABLE `appeal_messages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `appeal_statuses`
--
ALTER TABLE `appeal_statuses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT для таблицы `news`
--
ALTER TABLE `news`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `phone_verification_codes`
--
ALTER TABLE `phone_verification_codes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT для таблицы `services`
--
ALTER TABLE `services`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `appeals`
--
ALTER TABLE `appeals`
  ADD CONSTRAINT `fk_appeals_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `appeal_archive_comments`
--
ALTER TABLE `appeal_archive_comments`
  ADD CONSTRAINT `fk_aac_appeal` FOREIGN KEY (`appeal_id`) REFERENCES `appeals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_aac_moderator` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_aac_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `appeal_comments`
--
ALTER TABLE `appeal_comments`
  ADD CONSTRAINT `fk_comments_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_comments_appeal` FOREIGN KEY (`appeal_id`) REFERENCES `appeals` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `appeal_messages`
--
ALTER TABLE `appeal_messages`
  ADD CONSTRAINT `fk_msg_appeal` FOREIGN KEY (`appeal_id`) REFERENCES `appeals` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `appeal_statuses`
--
ALTER TABLE `appeal_statuses`
  ADD CONSTRAINT `fk_status_appeal` FOREIGN KEY (`appeal_id`) REFERENCES `appeals` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `phone_verification_codes`
--
ALTER TABLE `phone_verification_codes`
  ADD CONSTRAINT `fk_codes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_requests_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
