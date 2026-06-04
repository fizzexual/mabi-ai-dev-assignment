const { askMentor } = require("./ai-mentor");

async function main() {
  // Test 1: With full context
  console.log("--- Test 1: With user context ---");
  const answer1 = await askMentor(
    "Как да привлека повече клиенти онлайн?",
    { name: "Иван Петров", business_type: "онлайн магазин" }
  );
  console.log("Answer:", answer1);

  // Test 2: Without context (null safety check)
  console.log("\n--- Test 2: Without context ---");
  const answer2 = await askMentor("Какви са най-добрите маркетинг стратегии?");
  console.log("Answer:", answer2);

  // Test 3: Invalid input (should throw)
  console.log("\n--- Test 3: Invalid input ---");
  try {
    await askMentor("");
  } catch (e) {
    console.log("Expected error:", e.message);
  }
}

main();
