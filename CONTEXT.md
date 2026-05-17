# Контекст проекта 1001ranobe.ru

## Сайт
- WordPress на Timeweb (логин magsport)
- Тема: Audio Podcast в /wp-content/themes/audio-podcast/
- Зеркало: 1001ранобе.рф
- ABS сервер: https://audiobook.1001ranobe.ru (Docker, IP 94.41.21.24:13378)

## Структура темы
- functions.php — ядро (2500+ строк): шорткоды, плеер, AJAX, SEO, авторизация, каталог, текстовые книги
- index.php — главная: шорткоды + каталог
- single.php — страница аудиокниги
- single-ranobe.php — страница текстовой книги
- single-chapter.php — читалка главы
- single-post-layout.php — шаблон аудиокниги (плеер, рейтинг, избранное)
- style.css / player-style.css — все стили
- js/abs-player.js — старый плеер (Howler.js)
- js/abs-player-native.js — новый плеер (HTML5 Audio, без Howler)
- /includes/ — модули
- /template-parts/ — шаблоны

## Ключевые модули
- abs-catalog.php — каталог книг (аудио + текст)
- abs-ajax.php — AJAX обработчики (прогресс, рейтинг, избранное)
- abs-user-dashboard.php — личный кабинет
- abs-authors.php — список авторов
- abs-genres-widget.php — виджет жанров
- abs-parser-ranobe-fb2.php — парсер FB2 (ranobe.me)
- abs-parser-ifreedom.php — парсер ifreedom.su (старый)
- abs-ifreedom-v2.php — парсер ifreedom.su (новый, v2)
- abs-ifreedom-v2-admin.php — админка парсера v2
- abs-ifreedom-v2-cron.php — крон парсера v2
- abs-importer.php — импорт аудиокниг из ABS
- abs-duration-importer.php — длительности треков

## Таблицы БД
- abs_book_cache — кэш аудиокниг из ABS
- abs_audio_meta — метаданные аудиокниг
- abs_track_durations — длительности треков
- abs_progress — прогресс прослушивания
- abs_reading_progress — прогресс чтения
- abs_favorites — избранное
- abs_ratings — оценки
- abs_book_status — статусы книг
- abs_book_views — просмотры
- abs_book_stats — статистика книг
- abs_user_stats — статистика пользователей
- abs_summaries — саммари глав
- abs_parser_queue_fb2 — очередь FB2 парсера
- abs_ifreedom_v2_queue — очередь ifreedom парсера v2
- abs_voice_orders — заказы на озвучку

## API и ключи
- ABS_API_KEY: в wp-config.php
- ABS server URL: https://audiobook.1001ranobe.ru
- T-Bank терминал: 1778777475774
- CloudTips: https://pay.cloudtips.ru/p/db763c18
- Telegram бот: @otrsnobeBot (токен и chat_id в коде)

## Роли пользователей
- Админ (administrator)
- Подписчик (subscriber) — обычный пользователь
- В будущем: Автор (author) — загрузка книг

## Шорткоды
- [abs_player] — аудиоплеер
- [abs_player_native] — тестовый плеер (на HTML5 Audio)
- [abs_catalog] — каталог книг
- [abs_continue] — продолжить прослушивание/чтение
- [abs_favorites] — избранное
- [abs_new_releases] — новинки
- [abs_popular] — популярное
- [abs_similar] — похожие книги
- [abs_related] — читают также
- [abs_genres] — облако жанров
- [abs_authors] — список авторов
- [abs_my_library] — личный кабинет
- [user_stats] — мини-статистика
- [user_history] — история просмотров
- [user_achievements] — достижения
- [abs_profile_card] — карточка профиля
- [abs_profile_edit] — редактирование профиля
- [abs_login] / [abs_register] / [abs_lostpassword] / [abs_resetpassword] — авторизация
- [abs_order_voice] — заказ озвучки
- [abs_test_payment] — тестовый платёж T-Bank

## Принципы работы (ОБНОВЛЕНО 18.05.2026)
- GitHub только для задач (Issues) и контекста (CHECKLIST.md, CONTEXT.md).
- Никакого git на сервере. Работаем напрямую с файлами.
- Вы даёте файл или кусок кода → я даю правку в формате «найти → заменить».
- Перед любой правкой — копия файла (cp file.php file.php.bak).
- Команды: только nano, cp, grep, tail. Никаких rm, find, xargs.
- WP_DEBUG включён для логов.
- Одно изменение за раз. Сломалось — стоп, разбираемся.
- Никаких опасных экспериментов на живом сайте.

## Технологический стек
- Backend: WordPress (PHP 8.2) с глубокой кастомизацией
- Аудио: Audiobookshelf (Docker) + HTML5 Audio плеер
- Парсинг: Собственные парсеры на PHP (FB2, ifreedom.su)
- Хранение: MySQL (Timeweb)
- Платежи: CloudTips (донаты), T‑Bank EACQ (озвучка)
- Оповещения: Telegram Bot API

## Идеи и заметки
- Всегда проверять работу плеера на iOS (Safari).
- После каждого крупного изменения проверять:
  - Плеер на десктопе и мобильном
  - Читалку (навигация, настройки, тема)
  - Парсер (загрузка книг, логи, Telegram)
  - Каталог (фильтры, отображение)
  - Админку (заказы, импорт, парсер)
- Держать бэкап перед каждой сессией.
