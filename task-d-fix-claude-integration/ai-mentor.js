// ai-mentor.js — fixed version for Claude API (Anthropic SDK)
// This file corrects all bugs from the original broken integration.

const Anthropic = require("@anthropic-ai/sdk");
const client = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY });

/**
 * Sends a question to the Claude AI Mentor and returns the response.
 *
 * @param {string} userQuestion - The user's question in free text.
 * @param {object} [userContext] - Optional context about the user/client.
 * @param {string} [userContext.name] - Client name.
 * @param {string} [userContext.business_type] - Type of business.
 * @returns {Promise<string>} The mentor's text response.
 */
async function askMentor(userQuestion, userContext) {
  // FIX #4: Input validation — the original had no checks at all
  if (!userQuestion || typeof userQuestion !== "string") {
    throw new Error("userQuestion must be a non-empty string.");
  }

  // FIX #5: Null safety — if userContext is undefined/null, use safe defaults
  // The original would throw "Cannot read properties of undefined" on ctx.name
  const safeContext = {
    name: (userContext && userContext.name) || "Неизвестен клиент",
    business_type: (userContext && userContext.business_type) || "неуточнен",
  };

  const systemPrompt = buildSystemPrompt(safeContext);

  // FIX #4 (continued): Wrap the API call in try/catch for proper error handling
  try {
    const response = await client.messages.create({
      // FIX #3: Model name — changed from "claude-opus-4-5" to "claude-sonnet-4-6"
      // Choose the model appropriate for the use case; Sonnet is faster and cheaper
      // for a mentor/assistant scenario.
      model: "claude-sonnet-4-6",

      max_tokens: 1024,

      // FIX #1: System prompt placement
      // Claude API does NOT accept { role: "system" } inside the messages array.
      // Unlike OpenAI, Claude requires the system prompt as a separate top-level
      // parameter. This was the root cause of "Claude not respecting prompts" —
      // the system message was either ignored or caused an API error.
      system: systemPrompt,

      // FIX #1 (continued): The messages array now contains ONLY user/assistant
      // messages. No "system" role here.
      messages: [
        { role: "user", content: userQuestion },
      ],
    });

    // FIX #2: Response parsing
    // OpenAI returns: response.choices[0].message.content
    // Claude returns:  response.content[0].text
    // The response structure is completely different between the two APIs.
    // Claude's response.content is an array of content blocks; each text block
    // has a .text property (not .content).
    return response.content[0].text;

  } catch (error) {
    // FIX #4 (continued): Graceful error handling instead of unhandled rejections
    console.error("Claude API error:", error.message || error);
    throw new Error(
      `Failed to get response from AI Mentor: ${error.message || "Unknown error"}`
    );
  }
}

/**
 * Builds the system prompt for the AI Mentor.
 * Now receives a safe context object with guaranteed properties.
 *
 * @param {object} ctx - Safe context with name and business_type.
 * @returns {string} The system prompt in Bulgarian.
 */
function buildSystemPrompt(ctx) {
  return `Ти си AI Ментор в платформата МАБИ. Помагай на потребителя с полезни, конкретни и практични съвети.
Клиент: ${ctx.name}, бизнес: ${ctx.business_type}.
Отговаряй винаги на български език.`;
}

module.exports = { askMentor };
