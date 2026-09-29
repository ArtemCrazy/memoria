# Интеграции KIPORA: Montonio, Smart-ID, Mobile-ID

Сверено с официальной документацией 29.09.2026. Если что-то перестало работать, сначала сверьтесь с источниками в конце: API меняются.

## Montonio (Stargate)

- Адреса: боевой `https://stargate.montonio.com/api`, песочница `https://sandbox-stargate.montonio.com/api`. Ключи у каждой среды свои.
- Все запросы подписываются JWT HS256 секретным ключом (Secret Key). POST-запрос отправляет `{"data": "<JWT>"}`, GET-запрос — заголовок `Authorization: Bearer <JWT>` с полями `{accessKey, exp}`.
- Заказ создаётся запросом `POST /orders`. Обязательные поля: `accessKey`, `merchantReference` (уникальный; повтор обновляет заказ), `returnUrl`, `notificationUrl`, `currency` (EUR), `grandTotal`, `locale` (`et|ru|en|…`), `exp` (+10 минут), `payment{amount, currency, method, methodOptions}`.
- Как выбирается способ оплаты:
  - `method: paymentInitiation` без `preferredProvider` — клиент выбирает банк на странице Montonio.
  - `cardPayments` — оплата картой.
- В ответе: `uuid` (сохраняем) и `paymentUrl` (туда перенаправляем клиента).
- Возврат клиента: к `returnUrl` добавляется параметр `order-token` (JWT).
- Уведомление об оплате: POST на `notificationUrl` с телом `{"orderToken": "<JWT>"}`. Оплату проверяем так:
  - подпись верна (секретный ключ);
  - `accessKey` совпадает с нашим;
  - `uuid` совпадает с сохранённым;
  - `paymentStatus === PAID`.

  Отвечаем 200. При другом ответе Montonio повторяет уведомление 13 раз в течение 48 часов, поэтому одно уведомление может прийти несколько раз. Источник правды — уведомление, а не возврат клиента.
- Статусы: `PENDING`, `PAID`, `ABANDONED` (срок оплаты истёк), `VOIDED` (банк отклонил уже оплаченный платёж), `PARTIALLY_REFUNDED`, `REFUNDED`.

## Smart-ID (RP API v3)

- Адреса:
  - демо `https://sid.demo.sk.ee/smart-id-rp/v3/`, `relyingPartyUUID = 00000000-0000-4000-8000-000000000000`, `relyingPartyName = DEMO`;
  - боевой `https://rp-api.smart-id.com/v3/`, UUID и имя выдаёт SK по договору.
- Запуск входа: `POST authentication/notification/etsi/PNOEE-{isikukood}`, протокол `ACSP_V2`, `rpChallenge` из 64 случайных байт, `interactions` в виде base64 от JSON-массива, `vcType: numeric4`.
- Ожидание результата: `GET session/{sessionID}?timeoutMs=…` (long poll).
- Код подтверждения: последние 2 байта `sha256(rpChallenge)` как число, mod 10000, 4 цифры.
- Что подписано: строка `smart-id|ACSP_V2|{serverRandom}|{rpChallenge}|{userChallenge}|b64(rpName)|b64(brokeredRpName)|b64(sha256(interactions))|{interactionTypeUsed}|{initialCallbackUrl}|{flowType}`. Алгоритм RSASSA-PSS, SHA-512, MGF1 SHA-512, salt 64. Проверяем через phpseclib: `openssl_verify` не умеет PSS.
- Сертификат должен выстраиваться до локально сохранённых CA SK. Системное хранилище не используем. `serialNumber` в subject должен равняться `PNOEE-{код}`.
- В демо-среде подписанная строка начинается не с `smart-id`, а с `smart-id-demo`. В описании протокола этого нет; это видно в коде официального клиента (`SchemeName::DEMO`). Если перепутать, каждая подпись будет "неверной".
- phpseclib нужно передавать не сертификат, а открытый ключ, извлечённый через `openssl_pkey_get_details()`.
- Официальный PHP-клиент v3 требует PHP 8.4, поэтому протокол реализован в плагине. Проверка против демо: `php tests/sk-demo.php`.
- Сертификаты CA лежат в `kipora-core/certs/demo` (тестовые) и `certs/live` (боевые, заполнить перед запуском). OCSP-проверка отзыва сертификата пока не сделана: её нужно добавить до перехода на боевую среду.
- Демо-аккаунты с автоматическим подтверждением:
  - успешный вход: `40504040001`, `50001029996`, `39901012239`;
  - ошибки: `30403039917` — отказ, `30403039983` — таймаут.

## Mobile-ID (REST)

- Адреса:
  - демо `https://tsp.demo.sk.ee/mid-api`, `relyingPartyUUID = 00000000-0000-0000-0000-000000000000`, `relyingPartyName = DEMO`;
  - боевой `https://mid.sk.ee/mid-api`.
- Запуск входа: `POST authentication` с полями `phoneNumber`, `nationalIdentityNumber`, `hash`, `hashType` и `language` (`EST|ENG|RUS`).
- Ожидание результата: `GET authentication/session/{id}?timeoutMs=…`.
- Код подтверждения: первые 6 бит и последние 7 бит хэша, 4 цифры.
- Подписан сам отправленный хэш. Мы отправляем `hash = sha256(R)`, где R — случайные байты, и проверяем подпись как `openssl_verify(R, sig, cert, SHA256)`. EC-подпись приходит как `r‖s`, перед проверкой её переводим в DER. Официальный PHP-клиент подпись не проверяет, поэтому проверка своя.
- Демо-номера с автоматическим подтверждением:
  - успешный вход: `+37268000769` / `60001017869`, `+37200000766` / `60001019906`;
  - ошибки: отмена `+37201100266` / `60001019950`.

## Источники

- https://docs.montonio.com/api/stargate/overview
- https://docs.montonio.com/api/stargate/guides/orders
- https://docs.montonio.com/api/stargate/guides/webhooks
- https://sk-eid.github.io/smart-id-documentation/
- https://sk-eid.github.io/smart-id-documentation/rp-api/signature_protocols.html
- https://sk-eid.github.io/smart-id-documentation/test_accounts.html
- https://github.com/SK-EID/MID
- https://github.com/SK-EID/MID/wiki/Test-number-for-automated-testing-in-DEMO
