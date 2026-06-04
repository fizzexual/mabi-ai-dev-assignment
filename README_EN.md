# MABI — Technical Assignment

Solution for the **AI Developer** position at Tonkin / MABI.

The assignment covers four areas of the platform's real architecture: WordPress REST API, Next.js integration with Claude, infrastructure migration, and debugging a broken AI integration.

---

## Structure

```
├── task-a-wordpress-plugin/        WordPress REST API plugin
├── task-b-nextjs-claude-api/       Next.js API route for AI Mentor
├── task-c-migration-plan/          Migration plan for 3 servers
└── task-d-fix-claude-integration/  Fixed Claude API integration
```

## Task A — WordPress Plugin

**PHP** | REST endpoint for member data

Plugin that registers `GET /wp-json/mabi/v1/member/{user_id}` and returns aggregated user data — membership status, completed courses, last login, etc.

- Authentication and authorization (401 / 403 / 404)
- Transient caching (5 min)
- Unit tests with `WP_UnitTestCase`

→ [Full documentation](task-a-wordpress-plugin/README_EN.md)

## Task B — Next.js + Claude API

**TypeScript / Next.js** | AI Mentor endpoint

`POST /api/ask-mentor` — accepts a question and optional user context, builds a system prompt with MABI context, and returns a response from Claude.

- Validation, error handling (400 / 429 / 502)
- Rate limiting — 10 requests/min per IP
- Mock mode for testing without an API key
- Test UI page at `localhost:3000`

```bash
cd task-b-nextjs-claude-api
npm install
cp .env.example .env.local
npm run dev
```

→ [Full documentation](task-b-nextjs-claude-api/README_EN.md)

## Task C — Migration Plan

**Document** | Migrating 3 servers in 2 months

Complete plan for moving a WordPress site, Next.js app, and video streamer from current infrastructure (Superhosting + Contabo) to new providers, with specific recommendations, zero-downtime migration steps, risk analysis, and a week-by-week timeline.

→ [Migration plan](task-c-migration-plan/migration-plan.md) *(Bulgarian only)*

## Task D — Fix Claude Integration

**JavaScript** | Debugging and fixing 5 bugs

The original code was written for OpenAI and reworked for Claude, but doesn't work. The document describes each bug, why Claude "wasn't respecting the prompts", and the key differences between the two APIs.

```bash
cd task-d-fix-claude-integration
npm install
node test.js
```

→ [Full documentation](task-d-fix-claude-integration/README_EN.md)

---

## Technologies

- PHP 8.x, WordPress REST API
- TypeScript, Next.js 16, Anthropic SDK
- Node.js
