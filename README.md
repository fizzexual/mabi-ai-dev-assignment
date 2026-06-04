# МАБИ — Тестово задание

Решение на техническото задание за позиция **AI Developer** в Tonkin / МАБИ.

Заданието покрива четири области от реалната архитектура на платформата: WordPress REST API, Next.js интеграция с Claude, инфраструктурна миграция и дебъгване на счупена AI интеграция.

---

## Съдържание

```
├── task-a-wordpress-plugin/        WordPress REST API плъгин
├── task-b-nextjs-claude-api/       Next.js API route за AI Ментор
├── task-c-migration-plan/          План за миграция на 3 сървъра
└── task-d-fix-claude-integration/  Поправка на Claude интеграция
```

## Задача A — WordPress плъгин

**PHP** | REST endpoint за данни на членове

Плъгин, който регистрира `GET /wp-json/mabi/v1/member/{user_id}` и връща обобщени данни за даден потребител — членство, завършени курсове, последен логин и т.н.

- Автентикация и оторизация (401 / 403 / 404)
- Кеширане с transients (5 мин)
- Unit тестове с `WP_UnitTestCase`

→ [Пълна документация](task-a-wordpress-plugin/README.md)

## Задача B — Next.js + Claude API

**TypeScript / Next.js** | AI Ментор endpoint

`POST /api/ask-mentor` — приема въпрос и опционален потребителски контекст, изгражда system prompt с МАБИ контекст и връща отговор от Claude.

- Валидация, error handling (400 / 429 / 502)
- Rate limiting — 10 заявки/мин на IP
- Mock режим за тестване без API ключ
- Тестова UI страница на `localhost:3000`

```bash
cd task-b-nextjs-claude-api
npm install
cp .env.example .env.local
npm run dev
```

→ [Пълна документация](task-b-nextjs-claude-api/README.md)

## Задача C — Миграционен план

**Документ** | Миграция на 3 сървъра за 2 месеца

Пълен план за преместване на WordPress сайт, Next.js приложение и видео стриймър от текущата инфраструктура (Superhosting + Contabo) към нова, с конкретни препоръки за доставчици, стъпки за zero-downtime миграция, анализ на рисковете и график по седмици.

→ [Миграционен план](task-c-migration-plan/migration-plan.md)

## Задача D — Поправка на Claude интеграция

**JavaScript** | Дебъгване и поправка на 5 бъга

Оригиналният код е писан за OpenAI и преработен за Claude, но не работи. Документът описва всеки бъг, защо Claude "не зачиташе промптовете" и ключовите разлики между двете API-та.

```bash
cd task-d-fix-claude-integration
npm install
node test.js
```

→ [Пълна документация](task-d-fix-claude-integration/README.md)

---

## Технологии

- PHP 8.x, WordPress REST API
- TypeScript, Next.js 16, Anthropic SDK
- Node.js
