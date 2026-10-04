"""
Interface translations for kipora-core and the kipora theme.

Source strings are English (in the PHP code). This file holds Estonian and
Russian and writes WordPress .l10n.php files:

  python tools/i18n.py          check coverage and write languages/*.l10n.php

Coverage check: every __()/esc_html__() string with the 'kipora' domain must
have both translations, otherwise the script fails and lists what is missing.
"""

import glob
import json
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, "wp-content", "plugins", "kipora-core", "languages")

T = {
    "Check the email address.": ("Kontrollige e-posti aadressi.", "Проверьте адрес электронной почты."),
    "This email is already used by another account.": ("See e-posti aadress on juba teise kontoga seotud.", "Этот адрес уже привязан к другому аккаунту."),
    "The file did not upload. Try again.": ("Faili üleslaadimine ebaõnnestus. Proovige uuesti.", "Файл не загрузился. Попробуйте ещё раз."),
    "The file is larger than %d MB.": ("Fail on suurem kui %d MB.", "Файл больше %d МБ."),
    "Allowed formats: JPG, PNG, WEBP and PDF.": ("Lubatud vormingud: JPG, PNG, WEBP ja PDF.", "Допустимые форматы: JPG, PNG, WEBP и PDF."),
    "%1$s: order %2$s is paid": ("%1$s: tellimus %2$s on makstud", "%1$s: заказ %2$s оплачен"),
    "Hello, %1$s.\n\nThank you, we have received the payment for order %2$s.\n\n%3$s\n\nWhen the work is done, the photo report will appear in your account:\n%4$s": (
        "Tere, %1$s.\n\nAitäh, oleme tellimuse %2$s makse kätte saanud.\n\n%3$s\n\nKui töö on tehtud, ilmub fotoaruanne teie kontole:\n%4$s",
        "Здравствуйте, %1$s.\n\nСпасибо, мы получили оплату заказа %2$s.\n\n%3$s\n\nКогда работа будет выполнена, фотоотчёт появится в личном кабинете:\n%4$s",
    ),
    "%1$s: order %2$s is done": ("%1$s: tellimus %2$s on täidetud", "%1$s: заказ %2$s выполнен"),
    "Hello, %1$s.\n\nThe work for order %2$s is done. The photo report is in your account:\n%3$s": (
        "Tere, %1$s.\n\nTellimuse %2$s töö on tehtud. Fotoaruanne on teie kontol:\n%3$s",
        "Здравствуйте, %1$s.\n\nРабота по заказу %2$s выполнена. Фотоотчёт в личном кабинете:\n%3$s",
    ),
    "Memorial card": ("Mälestuskaart", "Карточка памяти"),
    "Customer": ("Klient", "Клиент"),
    "Comment": ("Kommentaar", "Комментарий"),
    "New paid order %1$s, %2$s": ("Uus makstud tellimus %1$s, %2$s", "Новый оплаченный заказ %1$s, %2$s"),
    "Total": ("Kokku", "Итого"),
    "Memorial cards": ("Mälestuskaardid", "Карточки памяти"),
    "Enter the name.": ("Sisestage nimi.", "Укажите имя."),
    "Card not found.": ("Kaarti ei leitud.", "Карточка не найдена."),
    "sector %s": ("sektor %s", "сектор %s"),
    "plot %s": ("plats %s", "место %s"),
    "Online payment is not connected yet.": ("Veebimakse pole veel ühendatud.", "Онлайн-оплата ещё не подключена."),
    "The payment service did not respond. Try again in a minute.": ("Makseteenus ei vastanud. Proovige minuti pärast uuesti.", "Платёжный сервис не ответил. Попробуйте через минуту."),
    "Orders": ("Tellimused", "Заказы"),
    "Order": ("Tellimus", "Заказ"),
    "Search orders": ("Otsi tellimusi", "Искать заказы"),
    "Awaiting payment": ("Ootab makset", "Ожидает оплаты"),
    "Paid": ("Makstud", "Оплачен"),
    "In progress": ("Töös", "В работе"),
    "Done": ("Tehtud", "Выполнен"),
    "Payment failed": ("Makse ebaõnnestus", "Оплата не прошла"),
    "Refunded": ("Tagastatud", "Возврат"),
    "Cancelled": ("Tühistatud", "Отменён"),
    "Type": ("Tüüp", "Тип"),
    "Pet": ("Lemmikloom", "Питомец"),
    "Person": ("Inimene", "Человек"),
    "Years": ("Eluaastad", "Годы жизни"),
    "Location": ("Asukoht", "Место"),
    "Biography": ("Elulugu", "Биография"),
    "Archive": ("Arhiiv", "Архив"),
    "Status": ("Staatus", "Статус"),
    "Service": ("Teenus", "Услуга"),
    "Date": ("Kuupäev", "Дата"),
    "Name": ("Nimi", "Имя"),
    "Photo report": ("Fotoaruanne", "Фотоотчёт"),
    "Language": ("Keel", "Язык"),
    "Payment": ("Makse", "Оплата"),
    "History": ("Ajalugu", "История"),
    'When the status becomes "Done", the customer gets an email with a link to the photo report.': (
        'Kui staatus muutub "Tehtud", saab klient e-kirja fotoaruande lingiga.',
        'Когда статус меняется на "Выполнен", клиент получает письмо со ссылкой на фотоотчёт.',
    ),
    "Before": ("Enne", "До"),
    "After": ("Pärast", "После"),
    "Delete": ("Kustuta", "Удалить"),
    'Photos are added to the order and to the memorial card archive. The customer sees them in the account. Click "Update" to save.': (
        'Fotod lisatakse tellimusele ja mälestuskaardi arhiivi. Klient näeb neid oma kontol. Salvestamiseks vajutage "Uuenda".',
        'Фото добавляются к заказу и в архив карточки памяти. Клиент видит их в личном кабинете. Чтобы сохранить, нажмите "Обновить".',
    ),
    "Settings": ("Seaded", "Настройки"),
    "saved; leave empty to keep": ("salvestatud; jätke tühjaks, et säilitada", "сохранён; оставьте пустым, чтобы не менять"),
    "Set in wp-config.php.": ("Määratud failis wp-config.php.", "Задано в wp-config.php."),
    "KIPORA settings": ("KIPORA seaded", "Настройки KIPORA"),
    "Settings saved.": ("Seaded salvestatud.", "Настройки сохранены."),
    "General": ("Üldine", "Общие"),
    "Service name": ("Teenuse nimi", "Название сервиса"),
    "Shown on the phone during login and in emails.": ("Kuvatakse sisselogimisel telefonis ja e-kirjades.", "Показывается на телефоне при входе и в письмах."),
    "Email for new orders": ("E-post uute tellimuste jaoks", "Почта для новых заказов"),
    "Environment": ("Keskkond", "Среда"),
    "DEMO uses public SK test accounts. Live needs the agreement with SK ID Solutions and the values below.": (
        "DEMO kasutab SK avalikke testkontosid. Live vajab lepingut SK ID Solutionsiga ja allolevaid väärtusi.",
        "DEMO использует публичные тестовые аккаунты SK. Для Live нужен договор с SK ID Solutions и значения ниже.",
    ),
    "Prices": ("Hinnad", "Цены"),
    "Grave care: services": ("Kalmistu hooldus: teenused", "Уход за захоронениями: услуги"),
    "Price, €": ("Hind, €", "Цена, €"),
    "× plot size": ("× platsi suurus", "× размер участка"),
    "Extra work allowed": ("Lisatööd lubatud", "Доп. работы доступны"),
    "Plot sizes": ("Platsi suurused", "Размеры участка"),
    "Coefficient": ("Koefitsient", "Коэффициент"),
    "Additional work": ("Lisatööd", "Дополнительные работы"),
    "Cemeteries": ("Kalmistud", "Кладбища"),
    "Zone": ("Tsoon", "Зона"),
    "Distance, km": ("Kaugus, km", "Расстояние, км"),
    "Pets: base services": ("Lemmikloomad: põhiteenused", "Питомцы: базовые услуги"),
    "Pets: urns": ("Lemmikloomad: urnid", "Питомцы: урны"),
    "Pets: options": ("Lemmikloomad: lisavalikud", "Питомцы: опции"),
    "Prices saved. The calculator uses them right away.": ("Hinnad salvestatud. Kalkulaator kasutab neid kohe.", "Цены сохранены. Калькулятор сразу считает по ним."),
    'Price of a plot service = price × plot coefficient (if "× plot size" is on). Cemeteries in Harju County add distance × price per km. "Hidden" rows disappear from the calculator but stay in old orders.': (
        'Platsiteenuse hind = hind × platsi koefitsient (kui "× platsi suurus" on sees). Harjumaa kalmistutel lisandub kaugus × kilomeetri hind. "Peidetud" read kaovad kalkulaatorist, kuid jäävad vanadesse tellimustesse.',
        'Цена услуги по участку = цена × коэффициент участка (если включено "× размер участка"). Для кладбищ Харьюмаа добавляется расстояние × цена за км. "Скрытые" строки пропадают из калькулятора, но остаются в старых заказах.',
    ),
    "Hidden": ("Peidetud", "Скрыто"),
    "Add row": ("Lisa rida", "Добавить строку"),
    "Harju County: price per km, €": ("Harjumaa: kilomeetri hind, €", "Харьюмаа: цена за км, €"),
    "Save prices": ("Salvesta hinnad", "Сохранить цены"),
    "Tallinn": ("Tallinn", "Таллин"),
    "Harju County": ("Harjumaa", "Харьюмаа"),
    "Remove": ("Eemalda", "Удалить"),
    "Check the personal identification code: it has 11 digits.": ("Kontrollige isikukoodi: selles on 11 numbrit.", "Проверьте личный код: в нём 11 цифр."),
    "Enter an Estonian mobile number, for example 5123 4567.": ("Sisestage Eesti mobiilinumber, näiteks 5123 4567.", "Введите эстонский мобильный номер, например 5123 4567."),
    "No active account found for this code. Check the code or choose another login method.": (
        "Selle koodiga aktiivset kontot ei leitud. Kontrollige koodi või valige teine sisselogimisviis.",
        "Для этого кода не найден активный аккаунт. Проверьте код или выберите другой способ входа.",
    ),
    "Login was cancelled on the phone.": ("Sisselogimine katkestati telefonis.", "Вход отменён на телефоне."),
    "The phone did not answer in time. Start again.": ("Telefon ei vastanud õigel ajal. Alustage uuesti.", "Телефон не ответил вовремя. Начните заново."),
    "A different control code was chosen on the phone. Start again and compare the codes.": (
        "Telefonis valiti teine kontrollkood. Alustage uuesti ja võrrelge koode.",
        "На телефоне выбран другой контрольный код. Начните заново и сравните коды.",
    ),
    "Smart-ID cannot be used on this device. Open the Smart-ID app to check.": (
        "Smart-ID-d ei saa selles seadmes kasutada. Avage kontrollimiseks Smart-ID rakendus.",
        "Smart-ID нельзя использовать на этом устройстве. Откройте приложение Smart-ID, чтобы проверить.",
    ),
    "The phone could not be reached. Check that it is on and try again.": ("Telefoniga ei saadud ühendust. Kontrollige, et see oleks sees, ja proovige uuesti.", "Телефон недоступен. Проверьте, что он включён, и попробуйте снова."),
    "Too many attempts. Wait a few minutes and try again.": ("Liiga palju katseid. Oodake mõni minut ja proovige uuesti.", "Слишком много попыток. Подождите несколько минут и попробуйте снова."),
    "The login session expired. Start again.": ("Sisselogimise seanss aegus. Alustage uuesti.", "Сеанс входа истёк. Начните заново."),
    "Check the phone number and personal code: they must belong to the same Mobile-ID.": (
        "Kontrollige telefoninumbrit ja isikukoodi: need peavad kuuluma samale Mobiil-ID-le.",
        "Проверьте номер телефона и личный код: они должны относиться к одному Mobiil-ID.",
    ),
    "Login is temporarily unavailable. Try again in a few minutes.": ("Sisselogimine pole ajutiselt võimalik. Proovige mõne minuti pärast uuesti.", "Вход временно недоступен. Попробуйте через несколько минут."),
    "Too many messages in a short time. Try again in an hour or call us.": ("Liiga palju sõnumeid lühikese aja jooksul. Proovige tunni pärast uuesti või helistage meile.", "Слишком много сообщений за короткое время. Попробуйте через час или позвоните нам."),
    "Fill in the name, email and message.": ("Täitke nimi, e-post ja sõnum.", "Заполните имя, почту и сообщение."),
    "Each file must be up to 10 MB.": ("Iga fail võib olla kuni 10 MB.", "Каждый файл должен быть не больше 10 МБ."),
    "Message from the website: %s": ("Sõnum kodulehelt: %s", "Сообщение с сайта: %s"),
    "The message was not sent. Write to us by email or call.": ("Sõnumit ei saadetud. Kirjutage meile e-postiga või helistage.", "Сообщение не отправлено. Напишите нам на почту или позвоните."),
    "Please accept the terms of sale.": ("Palun nõustuge müügitingimustega.", "Пожалуйста, примите условия продажи."),
    "Enter an email address: we send the order confirmation and photo report notice there.": (
        "Sisestage e-posti aadress: saadame sinna tellimuse kinnituse ja teate fotoaruande kohta.",
        "Укажите почту: туда придёт подтверждение заказа и уведомление о фотоотчёте.",
    ),
    "Choose at least one file.": ("Valige vähemalt üks fail.", "Выберите хотя бы один файл."),
    "What do you need?": ("Mida vajate?", "Что нужно?"),
    "Grave care": ("Kalmistu hooldus", "Уход за захоронением"),
    "Cleaning, seasonal care, flowers and candles": ("Koristus, hooajaline hooldus, lilled ja küünlad", "Уборка, сезонный уход, цветы и свечи"),
    "Pet services": ("Lemmikloomateenused", "Услуги для питомцев"),
    "Cremation, transport, urn and memorial page": ("Tuhastamine, transport, urn ja mälestusleht", "Кремация, транспортировка, урна и страница памяти"),
    "Plot size": ("Platsi suurus", "Размер участка"),
    "Cemetery": ("Kalmistu", "Кладбище"),
    "Choose a cemetery": ("Valige kalmistu", "Выберите кладбище"),
    "%s per km": ("%s kilomeetri kohta", "%s за км"),
    "Services": ("Teenused", "Услуги"),
    "Urn": ("Urn", "Урна"),
    "Options": ("Lisavalikud", "Опции"),
    "from %s": ("alates %s", "от %s"),
    "Continue to order": ("Jätka tellimusega", "Перейти к заказу"),
    "Your order": ("Teie tellimus", "Ваш заказ"),
    "Choose the service, plot size and cemetery to see the price.": ("Hinna nägemiseks valige teenus, platsi suurus ja kalmistu.", "Выберите услугу, размер участка и кладбище, чтобы увидеть цену."),
    "Choose at least one service to see the price.": ("Hinna nägemiseks valige vähemalt üks teenus.", "Выберите хотя бы одну услугу, чтобы увидеть цену."),
    "The price could not be calculated. Refresh the page and try again.": ("Hinda ei õnnestunud arvutada. Värskendage lehte ja proovige uuesti.", "Не удалось рассчитать цену. Обновите страницу и попробуйте снова."),
    "Your cemetery is not on the list? Write to us and we will calculate the price.": ("Teie kalmistut pole nimekirjas? Kirjutage meile ja arvutame hinna.", "Вашего кладбища нет в списке? Напишите нам, и мы рассчитаем цену."),
    "Check your phone. Enter PIN1 if the control code matches.": ("Vaadake telefoni. Sisestage PIN1, kui kontrollkood klapib.", "Посмотрите на телефон. Введите PIN1, если контрольный код совпадает."),
    "Control code": ("Kontrollkood", "Контрольный код"),
    "Logged in. Opening your page…": ("Sisse logitud. Avame teie lehe…", "Вход выполнен. Открываем вашу страницу…"),
    "No connection to the server. Check the internet and try again.": ("Serveriga puudub ühendus. Kontrollige internetti ja proovige uuesti.", "Нет связи с сервером. Проверьте интернет и попробуйте снова."),
    "Cancel": ("Katkesta", "Отмена"),
    "Thank you, the message is sent. We usually answer within one working day.": ("Aitäh, sõnum on saadetud. Vastame tavaliselt ühe tööpäeva jooksul.", "Спасибо, сообщение отправлено. Обычно отвечаем в течение рабочего дня."),
    "Memorial card saved.": ("Mälestuskaart salvestatud.", "Карточка памяти сохранена."),
    "Memorial card created. You can add photos and documents below.": ("Mälestuskaart loodud. Allpool saate lisada fotosid ja dokumente.", "Карточка памяти создана. Ниже можно добавить фото и документы."),
    "Files added to the archive.": ("Failid lisati arhiivi.", "Файлы добавлены в архив."),
    "File deleted.": ("Fail kustutatud.", "Файл удалён."),
    "Contact details saved.": ("Kontaktandmed salvestatud.", "Контактные данные сохранены."),
    "The order is saved, but online payment is not connected yet. We will contact you about payment.": (
        "Tellimus on salvestatud, kuid veebimakse pole veel ühendatud. Võtame makse osas teiega ühendust.",
        "Заказ сохранён, но онлайн-оплата ещё не подключена. Мы свяжемся с вами по оплате.",
    ),
    "Contact details": ("Kontaktandmed", "Контактные данные"),
    "Hello, %s": ("Tere, %s", "Здравствуйте, %s"),
    "Log out": ("Logi välja", "Выйти"),
    "Account sections": ("Konto osad", "Разделы кабинета"),
    "There are no memorial cards yet. A card is created with the first order, or you can add one here: a name, years of life, a few words and photos.": (
        "Mälestuskaarte veel pole. Kaart luuakse esimese tellimusega või saate selle lisada siin: nimi, eluaastad, paar sõna ja fotod.",
        "Карточек памяти пока нет. Карточка создаётся с первым заказом, или её можно добавить здесь: имя, годы жизни, несколько слов и фото.",
    ),
    "Add a memorial card": ("Lisa mälestuskaart", "Добавить карточку памяти"),
    "Create card": ("Loo kaart", "Создать карточку"),
    "All memorial cards": ("Kõik mälestuskaardid", "Все карточки памяти"),
    "Edit card": ("Muuda kaarti", "Изменить карточку"),
    "Save": ("Salvesta", "Сохранить"),
    "Photos and documents are visible only to you. Photo reports from our team are added here automatically.": (
        "Fotosid ja dokumente näete ainult teie. Meie fotoaruanded lisatakse siia automaatselt.",
        "Фото и документы видите только вы. Наши фотоотчёты добавляются сюда автоматически.",
    ),
    "Photo report: before": ("Fotoaruanne: enne", "Фотоотчёт: до"),
    "Photo report: after": ("Fotoaruanne: pärast", "Фотоотчёт: после"),
    "Delete this file? This cannot be undone.": ("Kas kustutada see fail? Seda ei saa tagasi võtta.", "Удалить этот файл? Отменить это нельзя."),
    "Choose photos or documents": ("Valige fotod või dokumendid", "Выберите фото или документы"),
    "JPG, PNG, WEBP or PDF, up to %d MB each": ("JPG, PNG, WEBP või PDF, igaüks kuni %d MB", "JPG, PNG, WEBP или PDF, каждый до %d МБ"),
    "Upload": ("Laadi üles", "Загрузить"),
    "Orders for this card": ("Selle kaardi tellimused", "Заказы по этой карточке"),
    "No orders yet.": ("Tellimusi veel pole.", "Заказов пока нет."),
    "Calculate the price": ("Arvuta hind", "Рассчитать стоимость"),
    "We send order confirmations and photo report notices to this email.": ("Saadame sellele aadressile tellimuse kinnitused ja teated fotoaruannete kohta.", "На эту почту приходят подтверждения заказов и уведомления о фотоотчётах."),
    "Email": ("E-post", "Почта"),
    "Phone": ("Telefon", "Телефон"),
    "Logged in with personal code %s. The name comes from your Smart-ID or Mobile-ID certificate.": (
        "Sisse logitud isikukoodiga %s. Nimi tuleb teie Smart-ID või Mobiil-ID sertifikaadist.",
        "Вход выполнен с личным кодом %s. Имя берётся из сертификата Smart-ID или Mobiil-ID.",
    ),
    "The price calculator needs JavaScript. Turn it on in the browser or contact us for a price.": (
        "Hinnakalkulaator vajab JavaScripti. Lülitage see brauseris sisse või küsige hinda meilt.",
        "Для калькулятора нужен JavaScript. Включите его в браузере или узнайте цену у нас.",
    ),
    "Choose a service in the calculator first: the order is made from it.": ("Valige esmalt kalkulaatoris teenus: tellimus koostatakse selle põhjal.", "Сначала выберите услугу в калькуляторе: заказ оформляется на её основе."),
    "Open the calculator": ("Ava kalkulaator", "Открыть калькулятор"),
    "Change the order": ("Muuda tellimust", "Изменить заказ"),
    "Thank you, the order is paid": ("Aitäh, tellimus on makstud", "Спасибо, заказ оплачен"),
    "Order %s is confirmed. We have sent a confirmation to your email. When the work is done, the photo report will appear in your account.": (
        "Tellimus %s on kinnitatud. Saatsime kinnituse teie e-postile. Kui töö on tehtud, ilmub fotoaruanne teie kontole.",
        "Заказ %s подтверждён. Мы отправили подтверждение на вашу почту. Когда работа будет выполнена, фотоотчёт появится в личном кабинете.",
    ),
    "Waiting for payment confirmation": ("Ootame makse kinnitust", "Ждём подтверждения оплаты"),
    "The bank usually confirms the payment within a minute. This page updates by itself.": ("Pank kinnitab makse tavaliselt minuti jooksul. See leht uueneb ise.", "Банк обычно подтверждает оплату в течение минуты. Страница обновится сама."),
    "The payment did not go through": ("Makse ei õnnestunud", "Оплата не прошла"),
    "No money was charged. You can try again or choose another payment method.": ("Raha ei võetud. Võite uuesti proovida või valida teise makseviisi.", "Деньги не списаны. Можно попробовать снова или выбрать другой способ оплаты."),
    "Pay again": ("Maksa uuesti", "Оплатить снова"),
    "Go to my orders": ("Minu tellimused", "Мои заказы"),
    "For whom": ("Kellele", "Для кого"),
    "Whose resting place": ("Kelle puhkepaik", "Чьё место захоронения"),
    "The order is saved to a memorial card. Photo reports and documents stay there too.": ("Tellimus salvestatakse mälestuskaardile. Sinna jäävad ka fotoaruanded ja dokumendid.", "Заказ сохраняется в карточке памяти. Там же остаются фотоотчёты и документы."),
    "New memorial card": ("Uus mälestuskaart", "Новая карточка памяти"),
    "Pet name": ("Lemmiklooma nimi", "Кличка питомца"),
    "Full name of the deceased": ("Lahkunu ees- ja perekonnanimi", "ФИО усопшего"),
    "Born": ("Sündinud", "Дата рождения"),
    "Died": ("Surnud", "Дата смерти"),
    "Sector": ("Sektor", "Сектор"),
    "Plot number": ("Platsi number", "Номер места"),
    "Comment for the team": ("Kommentaar meeskonnale", "Комментарий для команды"),
    "optional": ("valikuline", "необязательно"),
    "Bank link": ("Pangalink", "Банковская ссылка"),
    "Card": ("Kaart", "Карта"),
    "I accept the %s": ("Nõustun %s", "Я принимаю %s"),
    "terms of sale": ("müügitingimustega", "условия продажи"),
    "Pay %s": ("Maksa %s", "Оплатить %s"),
    "Payment is processed by Montonio. After payment you return here.": ("Makset töötleb Montonio. Pärast makset naasete siia.", "Оплату проводит Montonio. После оплаты вы вернётесь сюда."),
    "Message": ("Sõnum", "Сообщение"),
    "Attach photos or documents": ("Lisa fotod või dokumendid", "Прикрепить фото или документы"),
    "Up to 3 files, 10 MB each. For example, a photo of the plot.": ("Kuni 3 faili, igaüks kuni 10 MB. Näiteks foto platsist.", "До 3 файлов, каждый до 10 МБ. Например, фото участка."),
    "Send": ("Saada", "Отправить"),
    "Log in": ("Logi sisse", "Войти"),
    "The order is linked to your memorial cards, so we ask you to log in. No password or registration is needed.": (
        "Tellimus seotakse teie mälestuskaartidega, seepärast palume sisse logida. Parooli ega registreerimist pole vaja.",
        "Заказ привязывается к вашим карточкам памяти, поэтому нужен вход. Пароль и регистрация не нужны.",
    ),
    "Your memorial cards, orders and photo reports are here. No password or registration is needed.": (
        "Siin on teie mälestuskaardid, tellimused ja fotoaruanded. Parooli ega registreerimist pole vaja.",
        "Здесь ваши карточки памяти, заказы и фотоотчёты. Пароль и регистрация не нужны.",
    ),
    "Personal identification code": ("Isikukood", "Личный код (isikukood)"),
    "Mobile number": ("Mobiilinumber", "Номер телефона"),
    "Test environment: SK DEMO accounts": ("Testkeskkond: SK DEMO kontod", "Тестовая среда: аккаунты SK DEMO"),
    "Real Smart-ID and Mobile-ID do not work here yet. Use the test data, the login is confirmed automatically.": (
        "Päris Smart-ID ja Mobiil-ID siin veel ei tööta. Kasutage testandmeid, sisselogimine kinnitatakse automaatselt.",
        "Настоящие Smart-ID и Mobiil-ID здесь пока не работают. Используйте тестовые данные, вход подтверждается автоматически.",
    ),
    "Main menu": ("Peamenüü", "Главное меню"),
    "Footer menu": ("Jaluse menüü", "Меню в подвале"),
    "Skip to content": ("Liigu sisu juurde", "Перейти к содержимому"),
    "My account": ("Minu konto", "Личный кабинет"),
    "Page not found": ("Lehte ei leitud", "Страница не найдена"),
    "Go to the home page": ("Mine avalehele", "На главную"),
    "Tallinn • Harju County": ("Tallinn • Harjumaa", "Таллин • Харьюмаа"),
    "Grave lantern with a burning candle": ("Kalmulatern põleva küünlaga", "Кладбищенский фонарь с горящей свечой"),
    "Once or for the whole season": ("Ühekordselt või terve hooaja", "Разово или на весь сезон"),
    "Pets": ("Lemmikloomad", "Питомцы"),
    "Cremation, urn and memorial page": ("Tuhastamine, urn ja mälestusleht", "Кремация, урна и страница памяти"),

    "Menu": ("Menüü", "Меню"),
    'A grave plot overgrown with leaves and weeds': ('Lehtede ja umbrohuga kattunud hauaplats', 'Участок, заросший листьями и сорняками'),
    'Usually it goes *like this*': ('Tavaliselt käib see *nii*', 'Обычно это *выглядит так*'),
    'A trip to the cemetery once a season, if it works out': ('Sõit kalmistule kord hooajal, kui õnnestub', 'Поездка на кладбище раз в сезон, если получится'),
    'Calling relatives who live closer': ('Helistamine sugulastele, kes elavad lähemal', 'Звонки родственникам, которые живут ближе'),
    'Weeds and leaves grow faster than you can come': ('Umbrohi ja lehed kasvavad kiiremini, kui jõuate tulla', 'Сорняки и листья растут быстрее, чем вы успеваете приехать'),
    'No way to know whether the candle is burning': ('Pole teada, kas küünal põleb', 'Непонятно, горит ли свеча'),
    'A heavy feeling on memorial days': ('Raske tunne mälestuspäevadel', 'Тяжело на душе в памятные даты'),
    '*Memory does not depend on distance.* The plot can be cared for even when you are on the other side of the world.': ('*Mälestus ei sõltu kaugusest.* Plats võib olla korras ka siis, kui olete teisel pool maailma.', '*Память не зависит от расстояния.* Участок может быть в порядке, даже когда вы на другом конце света.'),
    'How it *works*': ('Kuidas see *käib*', 'Как это *устроено*'),
    'Four steps': ('Neli sammu', 'Четыре шага'),
    'Choose the service, plot size and cemetery. The price appears right away.': ('Valige teenus, platsi suurus ja kalmistu. Hind on kohe näha.', 'Выберите услугу, размер участка и кладбище. Цена появится сразу.'),
    'Person choosing a service on a phone': ('Inimene valib telefonis teenust', 'Человек выбирает услугу в телефоне'),
    'Log in and pay': ('Logige sisse ja makske', 'Войдите и оплатите'),
    'Smart-ID or Mobiil-ID, then a bank link or a card.': ('Smart-ID või Mobiil-ID, siis pangalink või kaart.', 'Smart-ID или Mobiil-ID, затем банковская ссылка или карта.'),
    'Paying on a phone': ('Maksmine telefonis', 'Оплата в телефоне'),
    'We do the work': ('Teeme töö ära', 'Мы выполняем работу'),
    'We clean the plot, care for the plants, bring flowers and a candle.': ('Koristame platsi, hooldame taimi, toome lilled ja küünla.', 'Убираем участок, ухаживаем за растениями, приносим цветы и свечу.'),
    'Cleaning a grave plot': ('Hauaplatsi koristamine', 'Уборка участка'),
    'See the photos': ('Vaadake fotosid', 'Смотрите фото'),
    'Photos before and after appear in your account.': ('Fotod enne ja pärast ilmuvad teie kontole.', 'Фото до и после появятся в личном кабинете.'),
    'A tidy grave with flowers and a candle': ('Korras haud lillede ja küünlaga', 'Ухоженная могила с цветами и свечой'),
    'Step %s': ('%s. samm', 'Шаг %s'),
    'An honest *comparison*': ('Aus *võrdlus*', 'Честное *сравнение*'),
    'Time': ('Aeg', 'Время'),
    'Half a day or more': ('Pool päeva või rohkem', 'Полдня или больше'),
    'A few minutes to order': ('Paar minutit tellimiseks', 'Несколько минут на заказ'),
    'The trip': ('Sõit', 'Дорога'),
    'You drive yourself': ('Sõidate ise', 'Едете сами'),
    'We go': ('Sõidame meie', 'Едем мы'),
    'Tools and materials': ('Tööriistad ja materjalid', 'Инструменты и материалы'),
    'Bring your own': ('Tuleb kaasa võtta', 'Нужно везти с собой'),
    'We bring them': ('Meil on kaasas', 'Привозим с собой'),
    'Checking the result': ('Tulemuse kontroll', 'Проверка результата'),
    'Only on site': ('Ainult kohapeal', 'Только на месте'),
    'Photos before and after': ('Fotod enne ja pärast', 'Фото до и после'),
    'Over the season': ('Hooaja jooksul', 'В течение сезона'),
    'Every visit from scratch': ('Iga kord otsast peale', 'Каждый раз заново'),
    'Seasonal care in one payment': ('Hooajaline hooldus ühe maksega', 'Сезонный уход одной оплатой'),
    'Going yourself': ('Ise kohale', 'Самостоятельно'),
    'You pay only for what you choose. *The price is fixed before payment.*': ('Maksate ainult selle eest, mille valite. *Hind on teada enne maksmist.*', 'Вы платите только за то, что выбрали. *Цена известна до оплаты.*'),
    'A *memorial card* for each loved one': ('*Mälestuskaart* iga lähedase kohta', '*Карточка памяти* для каждого близкого'),
    'Every order is linked to a memorial card. It keeps everything you want to preserve:': ('Iga tellimus on seotud mälestuskaardiga. Sinna jääb kõik, mida soovite hoida:', 'Каждый заказ связан с карточкой памяти. В ней остаётся всё, что вы хотите сохранить:'),
    'Name and years of life': ('Nimi ja eluaastad', 'Имя и годы жизни'),
    'A story and photos': ('Lugu ja fotod', 'История и фотографии'),
    'Documents': ('Dokumendid', 'Документы'),
    'The exact place: sector and plot': ('Täpne asukoht: sektor ja plats', 'Точное место: сектор и номер'),
    'Every photo report': ('Kõik fotoaruanded', 'Все фотоотчёты'),
    'Old family photographs in a box': ('Vanad perefotod karbis', 'Старые семейные фотографии в коробке'),
    'Only you can see the card. You log in with Smart-ID or Mobiil-ID.': ('Kaarti näete ainult teie. Sisenete Smart-ID või Mobiil-ID-ga.', 'Карточку видите только вы. Вход через Smart-ID или Mobiil-ID.'),
    'Frequently asked *questions*': ('Korduma kippuvad *küsimused*', 'Частые *вопросы*'),
    'How do I know the work is done?': ('Kuidas ma tean, et töö on tehtud?', 'Как я узнаю, что работа выполнена?'),
    'After every visit we upload photos before and after to your account and send you an email.': ('Pärast iga külastust laadime fotod enne ja pärast teie kontole ja saadame e-kirja.', 'После каждого выезда мы загружаем фото до и после в ваш кабинет и присылаем письмо.'),
    'Do I need to be in Estonia?': ('Kas pean olema Eestis?', 'Нужно ли быть в Эстонии?'),
    'No. Ordering, payment and photos are online. To log in you need Smart-ID or Mobiil-ID.': ('Ei. Tellimine, makse ja fotod on veebis. Sisenemiseks on vaja Smart-ID-d või Mobiil-ID-d.', 'Нет. Заказ, оплата и фото онлайн. Для входа нужен Smart-ID или Mobiil-ID.'),
    'How do I pay?': ('Kuidas maksta?', 'Как оплатить?'),
    'Through Montonio, by bank link or card. Seasonal care is paid once for the whole season.': ('Montonio kaudu pangalingi või kaardiga. Hooajaline hooldus makstakse korraga kogu hooaja eest.', 'Через Montonio банковской ссылкой или картой. Сезонный уход оплачивается сразу за весь сезон.'),
    'My cemetery is not in the calculator': ('Minu kalmistut pole kalkulaatoris', 'Моего кладбища нет в калькуляторе'),
    'Write to us with the name of the cemetery and, if you can, a photo of the plot. We will calculate the price.': ('Kirjutage meile kalmistu nimi ja võimalusel foto platsist. Arvutame hinna.', 'Напишите нам название кладбища и, если можете, фото участка. Мы рассчитаем цену.'),
    'Do you also help with pets?': ('Kas aitate ka lemmikloomadega?', 'Вы помогаете и с питомцами?'),
    'Yes: cremation, transport, an urn, engraving and a digital memorial page.': ('Jah: tuhastamine, transport, urn, graveering ja digitaalne mälestusleht.', 'Да: кремация, транспортировка, урна, гравировка и цифровая страница памяти.'),
    'Who can see the memorial card?': ('Kes näeb mälestuskaarti?', 'Кто видит карточку памяти?'),
    'Only you. Photos and documents in the archive are not public and are not shown to search engines.': ('Ainult teie. Arhiivi fotod ja dokumendid ei ole avalikud ega jõua otsingumootoritesse.', 'Только вы. Фото и документы в архиве не публичны и не попадают в поисковики.'),
    'Did not find an answer?': ('Ei leidnud vastust?', 'Не нашли ответ?'),
    'Write to us': ('Kirjutage meile', 'Напишите нам'),
    'Address': ('Aadress', 'Адрес'),
    'Contacts on the site': ('Kontaktid kodulehel', 'Контакты на сайте'),
    'Shown on the contact page and in the footer. An empty field is not shown.': ('Kuvatakse kontaktilehel ja jaluses. Tühja välja ei kuvata.', 'Показываются на странице контактов и в подвале. Пустое поле не выводится.'),
    'Tell us how we can *help*': ('Rääkige, kuidas saame *aidata*', 'Расскажите, чем мы можем *помочь*'),
    'Send a message': ('Saada sõnum', 'Написать сообщение'),
    'Write *to us*': ('Kirjutage *meile*', 'Напишите *нам*'),
    'We will answer to the email you enter in the form.': ('Vastame e-postile, mille vormis märgite.', 'Ответим на почту, которую вы укажете в форме.'),
    # Home page headline used when the old page title is gone (tools/wp/home-blocks.php).
    'We look after the *resting place* when you *can’t be there* yourself': ('Hoolitseme *puhkepaiga* eest, kui te ise *kohale ei jõua*', 'Ухаживаем за *местом памяти*, когда вы *не можете приехать* сами'),
    # Block editor (theme blocks/*/block.json and assets/js/blocks.js).
    'block title\x04First screen': ('Esimene ekraan', 'Первый экран'),
    'block description\x04Headline, button, photo or video and service cards.': ('Pealkiri, nupp, foto või video ja teenuste kaardid.', 'Заголовок, кнопка, фото или видео и карточки услуг.'),
    'block title\x04Service card': ('Teenuse kaart', 'Карточка услуги'),
    'block description\x04A card on the first screen that opens the calculator.': ('Kaart esimesel ekraanil, mis avab kalkulaatori.', 'Карточка на первом экране, открывает калькулятор.'),
    'block title\x04Photo and list': ('Foto ja loend', 'Фото и список'),
    'block description\x04Photo, heading, numbered list and a quote below.': ('Foto, pealkiri, nummerdatud loend ja tsitaat all.', 'Фото, заголовок, нумерованный список и цитата под ними.'),
    'block title\x04List item': ('Loendi rida', 'Пункт списка'),
    'block description\x04One line of a numbered list.': ('Üks nummerdatud loendi rida.', 'Одна строка нумерованного списка.'),
    'block title\x04Steps': ('Sammud', 'Шаги'),
    'block description\x04Heading and cards with numbered steps.': ('Pealkiri ja nummerdatud sammude kaardid.', 'Заголовок и карточки с пронумерованными шагами.'),
    'block title\x04Step': ('Samm', 'Шаг'),
    'block description\x04A step card: photo, title and text.': ('Sammu kaart: foto, pealkiri ja tekst.', 'Карточка шага: фото, заголовок и текст.'),
    'block title\x04Comparison': ('Võrdlus', 'Сравнение'),
    'block description\x04Two columns compared row by row, a quote and a button.': ('Kaks veergu rida-realt, tsitaat ja nupp.', 'Две колонки, сравнение по строкам, цитата и кнопка.'),
    'block title\x04Comparison row': ('Võrdluse rida', 'Строка сравнения'),
    'block description\x04What is compared and the value in each column.': ('Mida võrreldakse ja väärtus kummaski veerus.', 'Что сравниваем и значение в каждой колонке.'),
    'block title\x04List and photo with a card': ('Loend ja foto kaardiga', 'Список и фото с карточкой'),
    'block description\x04Heading, text, numbered list, photo and a small card on it.': ('Pealkiri, tekst, nummerdatud loend, foto ja väike kaart sellel.', 'Заголовок, текст, нумерованный список, фото и маленькая карточка на нём.'),
    'block title\x04Questions and answers': ('Küsimused ja vastused', 'Вопросы и ответы'),
    'block description\x04Heading, questions that open on click and a link below.': ('Pealkiri, klõpsuga avanevad küsimused ja link all.', 'Заголовок, вопросы, которые раскрываются по клику, и ссылка ниже.'),
    'block title\x04Question': ('Küsimus', 'Вопрос'),
    'block description\x04A question and its answer.': ('Küsimus ja vastus.', 'Вопрос и ответ.'),
    'Heading. Select words and press Ctrl+I for italic': ('Pealkiri. Kursiiviks valige sõnad ja vajutage Ctrl+I', 'Заголовок. Чтобы выделить слова курсивом, выделите их и нажмите Ctrl+I'),
    'Headline. Select words and press Ctrl+I for italic': ('Pealkiri. Kursiiviks valige sõnad ja vajutage Ctrl+I', 'Заголовок. Чтобы выделить слова курсивом, выделите их и нажмите Ctrl+I'),
    'Click to replace the photo': ('Foto vahetamiseks klõpsake', 'Нажмите, чтобы заменить фото'),
    'Choose photo': ('Vali foto', 'Выбрать фото'),
    'Restore the original': ('Taasta algne', 'Вернуть исходное'),
    'What is in the photo (for screen readers and search)': ('Mis on fotol (ekraanilugeritele ja otsingule)', 'Что на фото (для экранных чтецов и поиска)'),
    'Leave empty to open the price calculator in the language of the page.': ('Jätke tühjaks, et avada hinnakalkulaator lehe keeles.', 'Оставьте пустым, чтобы открывался калькулятор на языке страницы.'),
    'Photo and video': ('Foto ja video', 'Фото и видео'),
    'Photo': ('Foto', 'Фото'),
    'Video over the photo': ('Video foto peal', 'Видео поверх фото'),
    'A short muted loop. Without a video of your own, the original photo shows the candle video.': ('Lühike helita kordus. Kui oma videot pole, näitab algne foto küünla videot.', 'Короткое зацикленное видео без звука. Без своего видео на исходном фото показывается видео со свечой.'),
    'Choose video': ('Vali video', 'Выбрать видео'),
    'Remove video': ('Eemalda video', 'Убрать видео'),
    'Button': ('Nupp', 'Кнопка'),
    'Button link': ('Nupu link', 'Ссылка кнопки'),
    'Line above the headline, e.g. the area you work in': ('Rida pealkirja kohal, nt teie tööpiirkond', 'Строка над заголовком, например, где вы работаете'),
    'One or two sentences under the headline': ('Üks-kaks lauset pealkirja all', 'Одно-два предложения под заголовком'),
    'Button text': ('Nupu tekst', 'Текст кнопки'),
    'Service card': ('Teenuse kaart', 'Карточка услуги'),
    'Opens the calculator with': ('Avab kalkulaatori valikuga', 'Открывает калькулятор с выбором'),
    'Own link instead': ('Oma link', 'Своя ссылка вместо этого'),
    'Leave empty to open the calculator.': ('Jätke tühjaks, et avada kalkulaator.', 'Оставьте пустым, чтобы открывался калькулятор.'),
    'Card title': ('Kaardi pealkiri', 'Заголовок карточки'),
    'Short text': ('Lühike tekst', 'Короткий текст'),
    'List item': ('Loendi rida', 'Пункт списка'),
    'Quote under the section. Ctrl+I for italic': ('Tsitaat ploki all. Kursiiv: Ctrl+I', 'Цитата под блоком. Курсив: Ctrl+I'),
    'Label between the lines': ('Silt joonte vahel', 'Подпись между линиями'),
    'Step title': ('Sammu pealkiri', 'Заголовок шага'),
    'What happens at this step': ('Mis selles sammus toimub', 'Что происходит на этом шаге'),
    'What is compared': ('Mida võrreldakse', 'Что сравниваем'),
    'First column': ('Esimene veerg', 'Первая колонка'),
    'Second column': ('Teine veerg', 'Вторая колонка'),
    'Text under the comparison. Ctrl+I for italic': ('Tekst võrdluse all. Kursiiv: Ctrl+I', 'Текст под сравнением. Курсив: Ctrl+I'),
    'Photos': ('Fotod', 'Фото'),
    'Large photo': ('Suur foto', 'Большое фото'),
    'Photo on the small card': ('Foto väikesel kaardil', 'Фото на маленькой карточке'),
    'Text above the list': ('Tekst loendi kohal', 'Текст над списком'),
    'Text of the small card': ('Väikese kaardi tekst', 'Текст маленькой карточки'),
    'Link': ('Link', 'Ссылка'),
    'Link under the questions': ('Link küsimuste all', 'Ссылка под вопросами'),
    'Leave empty to open the contact page in the language of the page.': ('Jätke tühjaks, et avada kontaktileht lehe keeles.', 'Оставьте пустым, чтобы открывалась страница контактов на языке страницы.'),
    'Text before the link': ('Tekst enne linki', 'Текст перед ссылкой'),
    'Link text': ('Lingi tekst', 'Текст ссылки'),
    'Question': ('Küsimus', 'Вопрос'),
    'Answer': ('Vastus', 'Ответ'),
    # Inner page blocks and breadcrumbs.
    'block title\x04Cemetery card': ('Kalmistu kaart', 'Карточка кладбища'),
    'block description\x04Address, area, who manages it, links to the map and the grave search, order button.': ('Aadress, pindala, haldaja, lingid kaardile ja maetu otsingule, tellimise nupp.', 'Адрес, площадь, кто управляет, ссылки на карту и поиск захоронения, кнопка заказа.'),
    'block title\x04Page heading': ('Lehe päis', 'Шапка страницы'),
    'block description\x04Page headline, text, buttons and a photo at the top of an inner page.': ('Lehe pealkiri, tekst, nupud ja foto sisulehe alguses.', 'Заголовок страницы, текст, кнопки и фото в начале внутренней страницы.'),
    'block title\x04Subpage cards': ('Alamlehtede kaardid', 'Карточки подстраниц'),
    'block description\x04Cards linking to the subpages of this page (or of another page).': ('Kaardid selle (või teise) lehe alamlehtedele.', 'Карточки со ссылками на подстраницы этой (или другой) страницы.'),
    'block title\x04Price list': ('Hinnakiri', 'Прайс'),
    'block description\x04Prices from the calculator settings, so they always match the calculator.': ('Hinnad kalkulaatori seadetest, nii et need ühtivad alati kalkulaatoriga.', 'Цены из настроек калькулятора, поэтому они всегда совпадают с калькулятором.'),
    'Home': ('Avaleht', 'Главная'),
    'Breadcrumbs': ('Leivapuru', 'Навигационная цепочка'),
    'District': ('Linnaosa', 'Район'),
    'Area': ('Pindala', 'Площадь'),
    'Managed by': ('Haldaja', 'Кто управляет'),
    'Find a grave in the cemetery portal': ('Otsi hauaplatsi kalmistute portaalist', 'Найти захоронение в портале кладбищ'),
    'On the map': ('Kaardil', 'На карте'),
    'Directions': ('Teekond', 'Маршрут'),
    'Travel to cemeteries outside Tallinn': ('Transport väljaspool Tallinna asuvatele kalmistutele', 'Выезд на кладбища за пределами Таллина'),
    'Calculator opens with': ('Kalkulaator avaneb valikuga', 'Калькулятор открывается с выбором'),
    'Nothing chosen': ('Valikuta', 'Ничего не выбрано'),
    'Not chosen': ('Valimata', 'Не выбрано'),
    'Second link': ('Teine link', 'Вторая ссылка'),
    'Shown only with a link and a text.': ('Kuvatakse ainult siis, kui on nii link kui tekst.', 'Показывается, только если есть и ссылка, и текст.'),
    'Page headline. Select words and press Ctrl+I for italic': ('Lehe pealkiri. Kursiiviks valige sõnad ja vajutage Ctrl+I', 'Заголовок страницы. Чтобы выделить слова курсивом, выделите их и нажмите Ctrl+I'),
    'Second link text': ('Teise lingi tekst', 'Текст второй ссылки'),
    'Price list': ('Hinnakiri', 'Прайс'),
    'Which prices': ('Millised hinnad', 'Какие цены'),
    'Prices are edited in KIPORA → Prices, the same ones the calculator uses.': ('Hindu muudetakse menüüs KIPORA → Hinnad, samu kasutab kalkulaator.', 'Цены меняются в меню KIPORA → Цены, их же использует калькулятор.'),
    'Grave care by plot size': ('Hauahooldus platsi suuruse järgi', 'Уход по размеру участка'),
    'Flowers and candle': ('Lilled ja küünal', 'Цветы и свеча'),
    'Urns': ('Urnid', 'Урны'),
    'Memorial page': ('Mälestusleht', 'Страница памяти'),
    'Heading': ('Pealkiri', 'Заголовок'),
    'Note under the prices': ('Märkus hindade all', 'Примечание под ценами'),
    'The button opens the calculator.': ('Nupp avab kalkulaatori.', 'Кнопка открывает калькулятор.'),
    'Cemetery in the calculator': ('Kalmistu kalkulaatoris', 'Кладбище в калькуляторе'),
    'The order button opens the calculator with this cemetery chosen.': ('Tellimise nupp avab kalkulaatori, kus see kalmistu on valitud.', 'Кнопка заказа открывает калькулятор с этим кладбищем.'),
    'Number in the cemetery portal': ('Number kalmistute portaalis', 'Номер в портале кладбищ'),
    'From the address kalmistud.ee/cemetery/25: here 25.': ('Aadressist kalmistud.ee/cemetery/25: siin 25.', 'Из адреса kalmistud.ee/cemetery/25: здесь 25.'),
    'Latitude': ('Laiuskraad', 'Широта'),
    'Longitude': ('Pikkuskraad', 'Долгота'),
    'Subpage cards': ('Alamlehtede kaardid', 'Карточки подстраниц'),
    'Parent page number': ('Ülemlehe number', 'Номер родительской страницы'),
    'Leave 0 to show the subpages of this page. The card text is the page excerpt.': ('Jätke 0, et näidata selle lehe alamlehti. Kaardi tekst on lehe väljavõte.', 'Оставьте 0, чтобы показать подстраницы этой страницы. Текст карточки — отрывок страницы.'),
}

PATTERN = re.compile(r"(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*(['\"])((?:\\.|(?!\1).)*)\1\s*,\s*'kipora'")


def source_strings():
    files = glob.glob(os.path.join(ROOT, "wp-content/plugins/kipora-core/src/**/*.php"), recursive=True)
    files += glob.glob(os.path.join(ROOT, "wp-content/plugins/kipora-core/templates/**/*.php"), recursive=True)
    files += glob.glob(os.path.join(ROOT, "wp-content/themes/kipora/**/*.php"), recursive=True)
    files += glob.glob(os.path.join(ROOT, "wp-content/themes/kipora/assets/js/*.js"))
    files += glob.glob(os.path.join(ROOT, "tools/wp/*.php"))
    found = []
    # Block titles and descriptions are translated by WordPress with a context.
    for path in glob.glob(os.path.join(ROOT, "wp-content/themes/kipora/blocks/*/block.json")):
        meta = json.load(open(path, encoding="utf-8"))
        for key in ("title", "description"):
            if meta.get(key) and f"block {key}\x04{meta[key]}" not in found:
                found.append(f"block {key}\x04{meta[key]}")
    for path in files:
        text = open(path, encoding="utf-8").read()
        for m in PATTERN.finditer(text):
            quote, value = m.group(1), m.group(2)
            if quote == "'":
                value = value.replace("\\'", "'")
            else:
                value = value.replace('\\"', '"').replace("\\$", "$").replace("\\n", "\n")
            if value not in found:
                found.append(value)
    return found


def php_string(value):
    # A context ("block title") is joined to the string by the EOT character.
    parts = ["'" + part.replace("\\", "\\\\").replace("'", "\\'") + "'" for part in value.split("\x04")]
    return ' . "\\x04" . '.join(parts)


def write(locale, index):
    lines = ["<?php", "// Generated by tools/i18n.py. Edit translations there, not here.", "return ["]
    lines.append("\t'domain' => 'kipora',")
    lines.append("\t'language' => " + php_string(locale) + ",")
    lines.append("\t'messages' => [")
    for source, pair in sorted(T.items()):
        lines.append("\t\t" + php_string(source) + " => " + php_string(pair[index]) + ",")
    lines.append("\t],")
    lines.append("];")
    path = os.path.join(OUT, f"kipora-{locale}.l10n.php")
    with open(path, "w", encoding="utf-8", newline="\n") as f:
        f.write("\n".join(lines) + "\n")
    return path


def main():
    missing = [s for s in source_strings() if s not in T]
    unused = [s for s in T if s not in source_strings()]
    if missing:
        print("Missing translations:")
        for s in missing:
            print("  " + repr(s))
        sys.exit(1)
    for s in unused:
        print("unused (remove from T): " + repr(s))
    os.makedirs(OUT, exist_ok=True)
    for locale, index in (("et", 0), ("ru_RU", 1)):
        print("wrote " + os.path.relpath(write(locale, index), ROOT))
        print("wrote " + os.path.relpath(write_js(locale, index), ROOT))


def write_js(locale, index):
    """Translations of the block editor script, in the JSON format of wp_set_script_translations()."""
    text = open(os.path.join(ROOT, "wp-content/themes/kipora/assets/js/blocks.js"), encoding="utf-8").read()
    messages = {"": {"domain": "messages", "lang": locale}}
    for m in PATTERN.finditer(text):
        source = m.group(2).replace("\\'", "'")
        messages[source] = [T[source][index]]
    data = {"domain": "messages", "locale_data": {"messages": messages}}
    path = os.path.join(ROOT, "wp-content/themes/kipora/languages", f"kipora-{locale}-kipora-blocks.json")
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8", newline="\n") as f:
        json.dump(data, f, ensure_ascii=False)
    return path


if __name__ == "__main__":
    main()
