# Мир манипуляторов — v2 (формы реально отправляются + каталог из CSV)

## Запуск (Windows)
1) Распакуй архив
2) Открой PowerShell в папке сайта
3) Запусти:
   - `python server.py`
   - или `py server.py`

Открой: http://localhost:8000

## Реальная отправка форм
Фронт отправляет JSON на `POST /api/lead`.
Сервер:
- пишет лид в `leads/leads.csv`
- (опционально) отправляет в Telegram
- (опционально) отправляет Email через SMTP

### Подключить Telegram
PowerShell:
```
$env:TELEGRAM_BOT_TOKEN="ВАШ_ТОКЕН"
$env:TELEGRAM_CHAT_ID="ВАШ_CHAT_ID"
python server.py
```

### Подключить Email (SMTP)
```
$env:SMTP_HOST="smtp.gmail.com"
$env:SMTP_PORT="587"
$env:SMTP_USER="you@gmail.com"
$env:SMTP_PASS="app_password"
$env:SMTP_TO="you@gmail.com"
python server.py
```

## Реальные карточки из Excel/CSV
Источник: `data/products.csv`

Как сделать из Excel:
- Файл → Сохранить как → CSV (UTF-8) → заменить `data/products.csv`

Если меняешь CSV — обнови JSON:
```
python tools/build.py
```

## Что добавим дальше (топ по лидогенерации и SEO)
- Фильтры каталога (бренд/статус/год) + сортировка
- “Похожие товары” на карточке
- FAQ-блоки на услугах/брендах (Schema FAQ)
- Кейсы/фото работ + отзывы
- Аналитика: Яндекс.Метрика/GA4 + цели (клики/формы/телефон)
- 301 редиректы со старых URL + карта редиректов

## v3: Админка + детальные карточки + пересборка

### Запуск
```bash
python server.py
```

Открыть:
- Сайт: http://localhost:8000
- Админка: http://localhost:8000/admin/
- Лиды: http://localhost:8000/admin/leads/

### Логин/пароль админки
По умолчанию: `admin / admin`

Можно переопределить:
- Windows PowerShell:
```powershell
$env:ADMIN_USER="anton"; $env:ADMIN_PASS="strongpass"; py server.py
```
- Mac/Linux:
```bash
ADMIN_USER=anton ADMIN_PASS=strongpass python3 server.py
```

### Что делает админка
- CRUD товаров (title/slug/описание/характеристики/цена/статус/город/картинка)
- Автопересборка `/catalog/<category>/` и `/catalog/<category>/<slug>/`
- Пересборка `sitemap.xml` и `robots.txt`
- Просмотр лидов + экспорт `leads.csv`

## Фильтры в каталоге
- На страницах /catalog/<category>/ добавлены фильтры (поиск, бренд, год, сортировка).
- Данные берутся из публичного эндпоинта /api/public/products.

- В фильтрах каталога добавлены: Груз, Вылет, Секций (по данным из характеристик).
