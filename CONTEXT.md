# Контекст проекта 1001ranobe.ru

## Сайт
- WordPress на Timeweb (логин magsport)
- Тема: Audio Podcast в /wp-content/themes/audio-podcast/
- Зеркало: 1001ранобе.рф
- ABS сервер: https://audiobook.1001ranobe.ru (Docker, IP 94.41.21.24:13378)

## Структура темы
- `functions.php` — ядро (2500+ строк): шорткоды, плеер, AJAX, SEO, авторизация, каталог, текстовые книги
- `index.php` — главная: шорткоды + каталог
- `single.php` — страница аудиокниги
- `single-ranobe.php` — страница текстовой книги
- `single-chapter.php` — читалка главы
- `single-post-layout.php` — шаблон аудиокниги (плеер, рейтинг, избранное)
- `style.css` / `player-style.css` — все стили
- `js/abs-player.js` — плеер (Howler.js)
- `/includes/` — модули
- `/template-parts/` — шаблоны

## Ключевые модули
- `abs-catalog.php` — каталог книг (аудио + текст)
- `abs-ajax.php` — AJAX обработчики (прогресс, рейтинг, избранное)
- `abs-user-dashboard.php` — личный кабинет
- `abs-authors.php` — список авторов
- `abs-genres-widget.php` — виджет жанров
- `abs-parser-ranobe-fb2.php` — парсер FB2 (ranobe.me)
- `abs-parser-ifreedom.php` — парсер ifreedom.su
- `abs-importer.php` — импорт аудиокниг из ABS
- `abs-duration-importer.php` — длительности треков

## Таблицы БД
- `abs_book_cache` — кэш аудиокниг из ABS
- `abs_audio_meta` — метаданные аудиокниг (автор, жанры, обложка из текстовых)
- `abs_track_durations` — длительности треков
- `abs_progress` — прогресс прослушивания
- `abs_reading_progress` — прогресс чтения
- `abs_favorites` — избранное
- `abs_ratings` — оценки
- `abs_book_status` — статусы книг
- `abs_book_views` — просмотры
- `abs_book_stats` — статистика книг
- `abs_user_stats` — статистика пользователей
- `abs_summaries` — саммари глав
- `abs_parser_queue_fb2` — очередь FB2 парсера
- `abs_parser_ifreedom_queue` — очередь ifreedom парсера

## API и ключи
- ABS_API_KEY: `eyJhbGci...GZ8` (в functions.php, нужно вынести в wp-config.php)
- ABS server URL: https://audiobook.1001ranobe.ru
- T-Bank 
- CloudTips: https://pay.cloudtips.ru/p/db763c18

## Роли пользователей
- Админ (administrator)
- Подписчик (subscriber) — обычный пользователь
- В будущем: Автор (author) — загрузка книг

## Шорткоды
- `[abs_player]` — аудиоплеер
- `[abs_catalog]` — каталог книг
- `[abs_continue]` — продолжить прослушивание/чтение
- `[abs_favorites]` — избранное
- `[abs_new_releases]` — новинки
- `[abs_popular]` — популярное
- `[abs_similar]` — похожие книги
- `[abs_related]` — читают также
- `[abs_genres]` — облако жанров
- `[abs_authors]` — список авторов
- `[abs_my_library]` — личный кабинет
- `[user_stats]` — мини-статистика
- `[user_history]` — история просмотров
- `[user_achievements]` — достижения
- `[abs_profile_card]` — карточка профиля
- `[abs_profile_edit]` — редактирование профиля
- `[abs_login]` / `[abs_register]` / `[abs_lostpassword]` / `[abs_resetpassword]` — авторизация
- `[abs_test_payment]` — тестовый платёж T-Bank

## Принципы работы
- Обсуждаем логику → пишем код → проверяем на сайте
- Изменения через git push/pull между GitHub и сервером
- При ошибке — стоп, разбор причины
