# МАБИ AI Ментор — Поправена Claude интеграция

## Открити бъгове

Оригиналният код (`ai-mentor-original.js`) съдържаше **5 бъга**, които правеха интеграцията с Claude API неработеща.

### 1. Системният промпт е на грешно място

**Проблем:** В масива `messages` беше подаден обект с `{ role: "system" }`. Claude API **не приема** роля `"system"` в масива с съобщения — за разлика от OpenAI.

**Поправка:** Системният промпт е преместен като отделен top-level параметър `system` в заявката:

```js
// ГРЕШНО (OpenAI начин):
messages: [
  { role: "system", content: systemPrompt },
  { role: "user", content: userQuestion },
]

// ПРАВИЛНО (Claude начин):
system: systemPrompt,
messages: [
  { role: "user", content: userQuestion },
]
```

**Това е основната причина Claude да "не зачита промптовете"** — системното съобщение или се игнорираше, или предизвикваше грешка от API-то.

### 2. Грешно четене на отговора

**Проблем:** Кодът използваше `response.choices[0].message.content` — това е форматът на OpenAI. Claude връща отговора в съвсем различна структура.

**Поправка:**

```js
// ГРЕШНО (OpenAI формат):
return response.choices[0].message.content;

// ПРАВИЛНО (Claude формат):
return response.content[0].text;
```

### 3. Грешно име на модел

**Проблем:** Използваният модел `"claude-opus-4-5"` не е подходящ за този use case.

**Поправка:** Променен на `"claude-sonnet-4-6"` — по-бърз и по-евтин за менторски/асистентски сценарии.

### 4. Липса на обработка на грешки

**Проблем:** Нямаше `try/catch` блок около API извикването. Нямаше валидация на входните данни. При грешка програмата щеше да крашне без информативно съобщение.

**Поправка:** Добавени са:
- Валидация на `userQuestion` (трябва да е непразен стринг)
- `try/catch` блок с логване и хвърляне на разбираема грешка

### 5. Липса на null safety за userContext

**Проблем:** Ако `userContext` е `undefined` или `null`, достъпът до `ctx.name` и `ctx.business_type` в `buildSystemPrompt()` щеше да хвърли `TypeError: Cannot read properties of undefined`.

**Поправка:** Създаден е `safeContext` обект с fallback стойности по подразбиране:

```js
const safeContext = {
  name: (userContext && userContext.name) || "Неизвестен клиент",
  business_type: (userContext && userContext.business_type) || "неуточнен",
};
```

---

## Сравнение: OpenAI API vs Claude API

| Характеристика | OpenAI API | Claude API (Anthropic) |
|---|---|---|
| **Системен промпт** | `{ role: "system" }` в `messages[]` | Отделен top-level параметър `system` |
| **Формат на отговора** | `response.choices[0].message.content` | `response.content[0].text` |
| **Масив messages** | Приема `system`, `user`, `assistant` | Приема **само** `user` и `assistant` |
| **SDK пакет** | `openai` | `@anthropic-ai/sdk` |
| **Метод за заявка** | `client.chat.completions.create()` | `client.messages.create()` |
| **Параметър за лимит** | `max_tokens` (незадължителен) | `max_tokens` (**задължителен**) |
| **Структура на content** | Стринг | Масив от content блокове (`[{ type: "text", text: "..." }]`) |
| **Имена на модели** | `gpt-4o`, `gpt-4-turbo`, и т.н. | `claude-sonnet-4-6`, `claude-opus-4-6`, и т.н. |

---

## Как да тествате

### 1. Инсталирайте зависимостите

```bash
npm install @anthropic-ai/sdk
```

### 2. Задайте API ключа

```bash
# Linux/macOS:
export ANTHROPIC_API_KEY="вашият-ключ-тук"

# Windows (PowerShell):
$env:ANTHROPIC_API_KEY = "вашият-ключ-тук"
```

### 3. Бърз тест

Създайте файл `test.js`:

```js
const { askMentor } = require("./ai-mentor");

async function main() {
  // Тест 1: С пълен контекст
  const answer1 = await askMentor(
    "Как да привлека повече клиенти онлайн?",
    { name: "Иван Петров", business_type: "онлайн магазин" }
  );
  console.log("Отговор 1:", answer1);

  // Тест 2: Без контекст (проверка на null safety)
  const answer2 = await askMentor("Какви са най-добрите маркетинг стратегии?");
  console.log("Отговор 2:", answer2);

  // Тест 3: Невалиден вход (трябва да хвърли грешка)
  try {
    await askMentor("");
  } catch (e) {
    console.log("Очаквана грешка:", e.message);
  }
}

main();
```

Стартирайте:

```bash
node test.js
```

---

## Файлове

| Файл | Описание |
|---|---|
| `ai-mentor.js` | Поправената версия с коментари за всяка поправка |
| `ai-mentor-original.js` | Оригиналният счупен код (за справка) |
| `README.md` | Този файл — обяснение на бъговете и разликите |
