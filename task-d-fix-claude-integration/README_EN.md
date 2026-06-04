# MABI AI Mentor — Fixed Claude Integration

## Bugs found

The original code (`ai-mentor-original.js`) contained **5 bugs** that made the Claude API integration non-functional.

### 1. System prompt in the wrong place

**Problem:** A `{ role: "system" }` object was passed inside the `messages` array. Claude API **does not accept** a `"system"` role in the messages array — unlike OpenAI.

**Fix:** The system prompt was moved to a separate top-level `system` parameter:

```js
// WRONG (OpenAI way):
messages: [
  { role: "system", content: systemPrompt },
  { role: "user", content: userQuestion },
]

// CORRECT (Claude way):
system: systemPrompt,
messages: [
  { role: "user", content: userQuestion },
]
```

**This is the root cause of Claude "not respecting the prompts"** — the system message was either ignored or caused an API error.

### 2. Wrong response parsing

**Problem:** The code used `response.choices[0].message.content` — that's OpenAI's format. Claude returns the response in a completely different structure.

**Fix:**

```js
// WRONG (OpenAI format):
return response.choices[0].message.content;

// CORRECT (Claude format):
return response.content[0].text;
```

### 3. Wrong model name

**Problem:** The model `"claude-opus-4-5"` is not suitable for this use case.

**Fix:** Changed to `"claude-sonnet-4-6"` — faster and cheaper for mentor/assistant scenarios.

### 4. No error handling

**Problem:** No `try/catch` block around the API call. No input validation. On error the program would crash without an informative message.

**Fix:** Added:
- Validation of `userQuestion` (must be a non-empty string)
- `try/catch` block with logging and a descriptive error throw

### 5. No null safety for userContext

**Problem:** If `userContext` is `undefined` or `null`, accessing `ctx.name` and `ctx.business_type` in `buildSystemPrompt()` would throw `TypeError: Cannot read properties of undefined`.

**Fix:** A `safeContext` object with fallback defaults:

```js
const safeContext = {
  name: (userContext && userContext.name) || "Unknown client",
  business_type: (userContext && userContext.business_type) || "unspecified",
};
```

---

## Comparison: OpenAI API vs Claude API

| Feature | OpenAI API | Claude API (Anthropic) |
|---|---|---|
| **System prompt** | `{ role: "system" }` in `messages[]` | Separate top-level `system` parameter |
| **Response format** | `response.choices[0].message.content` | `response.content[0].text` |
| **Messages array** | Accepts `system`, `user`, `assistant` | Accepts **only** `user` and `assistant` |
| **SDK package** | `openai` | `@anthropic-ai/sdk` |
| **Request method** | `client.chat.completions.create()` | `client.messages.create()` |
| **Token limit param** | `max_tokens` (optional) | `max_tokens` (**required**) |
| **Content structure** | String | Array of content blocks (`[{ type: "text", text: "..." }]`) |
| **Model names** | `gpt-4o`, `gpt-4-turbo`, etc. | `claude-sonnet-4-6`, `claude-opus-4-6`, etc. |

---

## How to test

### 1. Install dependencies

```bash
npm install
```

### 2. Set the API key

```bash
# Linux/macOS:
export ANTHROPIC_API_KEY="your-key-here"

# Windows (PowerShell):
$env:ANTHROPIC_API_KEY = "your-key-here"
```

### 3. Run the test

```bash
node test.js
```

The test file runs three scenarios:
- With full user context
- Without context (null safety check)
- Invalid input (should throw an error)

---

## Files

| File | Description |
|---|---|
| `ai-mentor.js` | Fixed version with comments on each fix |
| `ai-mentor-original.js` | Original broken code (for reference) |
| `README.md` | Documentation in Bulgarian |
| `README_EN.md` | This file — documentation in English |
