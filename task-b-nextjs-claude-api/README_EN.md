# MABI AI Mentor

Next.js API route that integrates Claude API for personalized business advice.

## Running locally

1. Install dependencies:

```bash
npm install
```

2. Copy `.env.example` → `.env.local` and fill in the API key:

```bash
cp .env.example .env.local
```

Edit `.env.local` and paste your Anthropic API key:

```
ANTHROPIC_API_KEY=sk-ant-api03-...
```

If you leave the placeholder value or remove the key, the app runs in **mock mode** — returns test responses without calling the API.

3. Start the dev server:

```bash
npm run dev
```

The server starts at `http://localhost:3000`.

## Testing the API

### Minimal request (question only)

```bash
curl -X POST http://localhost:3000/api/ask-mentor \
  -H "Content-Type: application/json" \
  -d '{"question": "How do I raise my prices without losing clients?"}'
```

### Full request (with user context)

```bash
curl -X POST http://localhost:3000/api/ask-mentor \
  -H "Content-Type: application/json" \
  -d '{
    "question": "How do I raise my prices without losing clients?",
    "user_context": {
      "name": "Ivan",
      "business_type": "consulting services",
      "monthly_revenue": "25000 BGN",
      "membership_days": 45,
      "last_completed_resource": "Margin Fix"
    }
  }'
```

### Expected response

```json
{
  "answer": "..."
}
```

### Errors

| Code | When                                      |
|------|-------------------------------------------|
| 400  | Missing `question` or invalid JSON        |
| 429  | Over 10 requests/minute from one IP       |
| 502  | Claude API error                          |

## Project structure

```
src/app/api/ask-mentor/route.ts   — API route handler
src/app/page.tsx                  — Test UI with preset buttons
.env.example                      — Environment variable template
```
