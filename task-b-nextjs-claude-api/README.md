# МАБИ AI Ментор

Next.js API route, която интегрира Claude API за персонализирани бизнес отговори.

## Стартиране локално

1. Инсталирай зависимостите:

```bash
npm install
```

2. Копирай `.env.example` → `.env.local` и попълни API ключа:

```bash
cp .env.example .env.local
```

Редактирай `.env.local` и постави своя Anthropic API ключ:

```
ANTHROPIC_API_KEY=sk-ant-api03-...
```

3. Стартирай dev сървъра:

```bash
npm run dev
```

Сървърът тръгва на `http://localhost:3000`.

## Тестване на API-то

### Минимална заявка (само въпрос)

```bash
curl -X POST http://localhost:3000/api/ask-mentor \
  -H "Content-Type: application/json" \
  -d '{"question": "Как да вдигна цените си без да губя клиенти?"}'
```

### Пълна заявка (с контекст на потребителя)

```bash
curl -X POST http://localhost:3000/api/ask-mentor \
  -H "Content-Type: application/json" \
  -d '{
    "question": "Как да вдигна цените си без да губя клиенти?",
    "user_context": {
      "name": "Иван",
      "business_type": "консултантски услуги",
      "monthly_revenue": "25000 лв",
      "membership_days": 45,
      "last_completed_resource": "Margin Fix"
    }
  }'
```

### Очакван отговор

```json
{
  "answer": "..."
}
```

### Грешки

| Код  | Кога                                     |
|------|------------------------------------------|
| 400  | Липсва `question` или невалиден JSON     |
| 429  | Над 10 заявки/минута от един IP          |
| 502  | Грешка от Claude API                     |

## Структура

```
src/app/api/ask-mentor/route.ts   — API route handler
.env.example                      — шаблон за env променливи
```
