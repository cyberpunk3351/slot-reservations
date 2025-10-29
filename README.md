# Slots Reservations API

## Стек

* PHP 8.2+, Laravel 12
* MySQL 8+

## Установка

```bash
git clone https://github.com/cyberpunk3351/slot-reservations slots-reservations
cd slots-reservations
docker exec -it slot-reservations_app cp .env.example .env
docker exec -it slot-reservations_app composer install
docker exec -it slot-reservations_app php artisan key:generate
docker exec -it slot-reservations_app php artisan migrate --seed
```
Токен пользователя для авторизации надо будет поместить в заголовок `Authorization: Bearer <token>`.
## Эндпоинты

### 1) Доступные слоты (горячий кеш 10 с)

**GET** `/api/slots/availability`

**Пример ответа**

```json
[
  { "slot_id": 1, "capacity": 10, "remaining": 10 },
  { "slot_id": 2, "capacity": 5,  "remaining": 0 }
]
```

---

### 2) Создание холда (идемпотентно)

**POST** `/api/slots/{id}/hold`
**Headers:** `Idempotency-Key: <UUID>`

**Успех (201) — пример запроса**

```bash
curl -i -X POST http://127.0.0.1:8000/api/slots/1/hold \
  -H "Idempotency-Key: 14f0b6e8-82e1-4b76-b586-1f2f6b5d8f20"
```

**Ответ**

```json
{ "hold_id": 7, "status": "held", "expires_at": "2025-10-25T13:45:00+00:00" }
```

Повтор с тем же ключом — тот же ответ **(201)**.

**Конфликт (409), если мест нет**

```json
{ "error": "Capacity exhausted" }
```

---

### 3) Подтверждение холда (атомарное уменьшение)

**POST** `/api/holds/{id}/confirm`

**Успех (200)**

```json
{ "hold_id": 7, "status": "confirmed" }
```

**Конфликт (409) при оверселе**

```json
{ "error": "No remaining capacity" }
```

После успешного подтверждения — инвалидируется кеш доступности.

---

### 4) Отмена холда

**DELETE** `/api/holds/{id}`

**Успех (200)**

```json
{ "hold_id": 7, "status": "cancelled" }
```

Если холд был `confirmed` — остаток слота увеличивается на 1; если `held` — остаток не меняется. После отмены — инвалидируется кеш доступности.

## Замечания

* Холды живут **5 минут**.
* Идемпотентность реализована через таблицу `idempotency_keys`: повтор по одному и тому же ключу вернёт ровно тот же код и тело ответа.
