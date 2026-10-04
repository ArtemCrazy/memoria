# KIPORA: сайт сервиса ухода за захоронениями

WordPress с собственной темой `kipora` и плагином `kipora-core`. Из сторонних плагинов только Polylang (три языка) и, на тестовом сервере, SQLite Database Integration.

Тестовый сервер: https://korovai.crazytest.ru/memoria/

## Что внутри

- `wp-content/plugins/kipora-core`:
  - калькулятор;
  - заказы и оплата Montonio;
  - вход через Smart-ID и Mobiil-ID;
  - карточки памяти с закрытым архивом и фотоотчётами;
  - форма обратной связи;
  - админка: заказы, карточки, цены, настройки.
- `wp-content/themes/kipora`: тема. Визуальная система описана в `docs/design.md`, блоки редактора — в `docs/blocks.md`, инструкция для клиента — в `docs/editor-guide.md`.
- `docs/integrations.md`: как устроены Montonio, Smart-ID и Mobile-ID и какие у них особенности.
- `tools/`: выкладка, настройка сайта, переводы, сквозной тест.

На страницах стоят шорткоды, поэтому текст вокруг них клиент правит в редакторе: `[kipora_calculator]`, `[kipora_checkout]`, `[kipora_account]`, `[kipora_contact]`.

## Работа с проектом

```bash
npm install                 # один раз, для сборки SCSS
npm run build:css           # SCSS → CSS темы и плагина
python tools/i18n.py        # проверка и сборка переводов ET/RU (languages/*.l10n.php)
python tools/deploy.py      # выкладка темы и плагина на тестовый сервер
python tools/deploy.py --tests   # + юнит-тесты логики на серверном PHP 8.2
python tools/e2e.py         # сквозной тест сайта (вход, заказ, архив, оплата)
```

Проверка против демо-сервисов SK: `php tests/sk-demo.php` на сервере (`deploy.py --tests` загружает тесты в `wp-content/database/tests`).

Доступы в репозиторий не попадают:

- SFTP: `C:\Users\CrazyStudio\AppData\Local\CrazyAssistant\creds\card-306.env`;
- администратор WordPress на тестовом сервере: `card-306-wp-admin.env` в той же папке.

`tools/deploy.py --wp <script>` запускает скрипт из `tools/wp/` внутри WordPress через временный mu-plugin `kp-runner.php`. Консольный PHP на Beget не работает с SQLite. Перед передачей сайта `wp-content/mu-plugins/kp-runner.php` нужно удалить.

## Особенности тестового сервера

- База данных — SQLite, потому что MySQL на Beget не заведена. На Zone.ee сайт будет работать на MySQL, код от базы не зависит.
- Файлы архива хранятся в `wp-content/kipora-private` с расширением `.bin`. Nginx на хостинге отдаёт `.jpg` и `.pdf` напрямую, в обход `.htaccess`. На боевом сервере папку лучше вынести за пределы сайта: `define( 'KIPORA_PRIVATE_DIR', '/путь/вне/public_html' );` в `wp-config.php`.
- Правила адресов нельзя перестраивать из консоли или скрипта: Polylang добавляет префиксы `/ru/` и `/en/` только при обычном запросе. Поэтому скрипты удаляют опцию `rewrite_rules`, а сайт пересобирает её сам.

## Что сделать перед боевым запуском

1. Ключи Montonio: сначала sandbox, потом боевые. Задаются в KIPORA → Seaded или константами `KIPORA_MONTONIO_ACCESS_KEY` и `KIPORA_MONTONIO_SECRET_KEY`.
2. Договор с SK ID Solutions: получить relyingPartyUUID и имя для Smart-ID и Mobiil-ID и сообщить SK IP сервера Zone.ee.
3. Положить боевые сертификаты CA SK в `kipora-core/certs/live`.
4. Добавить проверку отзыва сертификата (OCSP). Сейчас проверяются подпись, срок действия и издатель.
5. Заменить примерные цены (KIPORA → Hinnad), тексты страниц и юридические тексты на материалы клиента.
6. Перенести сайт на Zone.ee и удалить `kp-runner.php`.
