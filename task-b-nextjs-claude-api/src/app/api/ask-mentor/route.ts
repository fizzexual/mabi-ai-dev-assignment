import { headers } from "next/headers";
import { NextRequest } from "next/server";

const key = process.env.ANTHROPIC_API_KEY ?? "";
const MOCK_MODE = !key || key.length < 20 || key.includes("...");

function getClient() {
  const Anthropic = require("@anthropic-ai/sdk").default;
  return new Anthropic();
}

const SYSTEM_PROMPT = `Ти си AI Ментор на МАБИ — предприемаческа академия за собственици на бизнес с 200,000+ лв. годишен оборот.

Стил: директен, конкретен, практичен. Без мотивационен speaker тон.
Казваш "ти". На български.
Не даваш финансов, правен или медицински съвет.
Ако въпросът е извън бизнес темите — отклони учтиво.`;

function buildSystemPrompt(userContext?: UserContext): string {
  if (!userContext) return SYSTEM_PROMPT;

  const parts = [SYSTEM_PROMPT, "\nИнформация за потребителя:"];
  if (userContext.name) parts.push(`- Име: ${userContext.name}`);
  if (userContext.business_type)
    parts.push(`- Тип бизнес: ${userContext.business_type}`);
  if (userContext.monthly_revenue)
    parts.push(`- Месечен приход: ${userContext.monthly_revenue}`);
  if (userContext.membership_days != null)
    parts.push(`- Дни членство в МАБИ: ${userContext.membership_days}`);
  if (userContext.last_completed_resource)
    parts.push(
      `- Последен завършен ресурс: ${userContext.last_completed_resource}`
    );

  return parts.join("\n");
}

interface UserContext {
  name?: string;
  business_type?: string;
  monthly_revenue?: string;
  membership_days?: number;
  last_completed_resource?: string;
}

interface AskMentorBody {
  question?: unknown;
  user_context?: UserContext;
}

// --- Rate limiting (bonus): 10 requests per minute per IP ---
const rateLimitMap = new Map<string, number[]>();
const RATE_LIMIT_WINDOW_MS = 60_000;
const RATE_LIMIT_MAX = 10;

function isRateLimited(ip: string): boolean {
  const now = Date.now();
  const timestamps = rateLimitMap.get(ip) ?? [];
  const recent = timestamps.filter((t) => now - t < RATE_LIMIT_WINDOW_MS);
  if (recent.length >= RATE_LIMIT_MAX) {
    rateLimitMap.set(ip, recent);
    return true;
  }
  recent.push(now);
  rateLimitMap.set(ip, recent);
  return false;
}

export async function POST(request: NextRequest) {
  const headersList = await headers();
  const ip =
    headersList.get("x-forwarded-for")?.split(",")[0]?.trim() ??
    request.headers.get("x-real-ip") ??
    "unknown";

  if (isRateLimited(ip)) {
    return Response.json(
      { error: "Твърде много заявки. Опитай отново след минута." },
      { status: 429 }
    );
  }

  let body: AskMentorBody;
  try {
    body = await request.json();
  } catch {
    return Response.json(
      { error: "Невалиден JSON в тялото на заявката." },
      { status: 400 }
    );
  }

  if (!body.question || typeof body.question !== "string") {
    return Response.json(
      { error: "Полето 'question' е задължително и трябва да е текст." },
      { status: 400 }
    );
  }

  const question = body.question.trim();
  if (question.length === 0) {
    return Response.json(
      { error: "Полето 'question' не може да бъде празно." },
      { status: 400 }
    );
  }

  const systemPrompt = buildSystemPrompt(body.user_context);

  if (MOCK_MODE) {
    const name = body.user_context?.name;
    const mock =
      `[MOCK MODE — няма ANTHROPIC_API_KEY]\n\n` +
      `${name ? `${name}, добър` : "Добър"} въпрос! Ето примерен отговор:\n\n` +
      `Вдигането на цените е нормална стъпка за растящ бизнес. ` +
      `Фокусирай се върху стойността, която даваш, а не върху числото. ` +
      `Комуникирай промяната поне 30 дни предварително и предложи бонус за лоялните клиенти.\n\n` +
      `(Това е тестов отговор. Постави ANTHROPIC_API_KEY в .env.local за истински.)`;

    console.log(
      `[ask-mentor] MOCK question_length=${question.length} answer_length=${mock.length}`
    );
    return Response.json({ answer: mock });
  }

  try {
    const message = await getClient().messages.create({
      model: "claude-sonnet-4-6",
      max_tokens: 1024,
      system: systemPrompt,
      messages: [{ role: "user", content: question }],
    });

    const answer =
      message.content[0].type === "text" ? message.content[0].text : "";

    console.log(
      `[ask-mentor] question_length=${question.length} answer_length=${answer.length}`
    );

    return Response.json({ answer });
  } catch (err) {
    console.error("[ask-mentor] Claude API error:", err);
    return Response.json(
      { error: "Грешка при комуникация с AI ментора. Опитай отново." },
      { status: 502 }
    );
  }
}
