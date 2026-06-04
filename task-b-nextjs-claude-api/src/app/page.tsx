"use client";

import { useState } from "react";

const EXAMPLES = [
  {
    label: "Прост въпрос (без контекст)",
    body: {
      question: "Как да вдигна цените си без да губя клиенти?",
    },
  },
  {
    label: "С потребителски контекст",
    body: {
      question: "Как да вдигна цените си без да губя клиенти?",
      user_context: {
        name: "Иван",
        business_type: "консултантски услуги",
        monthly_revenue: "25000 лв",
        membership_days: 45,
        last_completed_resource: "Margin Fix",
      },
    },
  },
  {
    label: "Невалидна заявка (без въпрос)",
    body: {},
  },
  {
    label: "Извън тема (медицински съвет)",
    body: {
      question: "Какво лекарство да взема за главоболие?",
    },
  },
];

export default function Home() {
  const [response, setResponse] = useState<string | null>(null);
  const [status, setStatus] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);
  const [customQuestion, setCustomQuestion] = useState("");

  async function sendRequest(body: object) {
    setLoading(true);
    setResponse(null);
    setStatus(null);
    try {
      const res = await fetch("/api/ask-mentor", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      setStatus(res.status);
      const data = await res.json();
      setResponse(JSON.stringify(data, null, 2));
    } catch (err) {
      setResponse("Network error: " + (err as Error).message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen bg-zinc-950 text-zinc-100 p-8 font-sans">
      <div className="max-w-2xl mx-auto">
        <h1 className="text-3xl font-bold mb-2">МАБИ AI Ментор</h1>
        <p className="text-zinc-400 mb-8">
          Тестова страница за <code className="text-zinc-300 bg-zinc-800 px-1.5 py-0.5 rounded text-sm">POST /api/ask-mentor</code>
        </p>

        <section className="mb-8">
          <h2 className="text-lg font-semibold mb-3">Готови тестове</h2>
          <div className="flex flex-col gap-3">
            {EXAMPLES.map((ex) => (
              <button
                key={ex.label}
                onClick={() => sendRequest(ex.body)}
                disabled={loading}
                className="text-left px-4 py-3 rounded-lg bg-zinc-800 hover:bg-zinc-700 transition-colors disabled:opacity-50 disabled:cursor-wait"
              >
                <span className="font-medium">{ex.label}</span>
                <pre className="text-xs text-zinc-400 mt-1 overflow-x-auto">
                  {JSON.stringify(ex.body, null, 2)}
                </pre>
              </button>
            ))}
          </div>
        </section>

        <section className="mb-8">
          <h2 className="text-lg font-semibold mb-3">Собствен въпрос</h2>
          <div className="flex gap-2">
            <input
              type="text"
              value={customQuestion}
              onChange={(e) => setCustomQuestion(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === "Enter" && customQuestion.trim()) {
                  sendRequest({ question: customQuestion });
                }
              }}
              placeholder="Напиши въпрос..."
              className="flex-1 px-4 py-2 rounded-lg bg-zinc-800 border border-zinc-700 focus:outline-none focus:border-zinc-500 placeholder:text-zinc-500"
            />
            <button
              onClick={() => sendRequest({ question: customQuestion })}
              disabled={loading || !customQuestion.trim()}
              className="px-5 py-2 rounded-lg bg-white text-black font-medium hover:bg-zinc-200 transition-colors disabled:opacity-50 disabled:cursor-wait"
            >
              Изпрати
            </button>
          </div>
        </section>

        {(loading || response !== null) && (
          <section>
            <h2 className="text-lg font-semibold mb-3">
              Отговор
              {status !== null && (
                <span
                  className={`ml-2 text-sm font-mono px-2 py-0.5 rounded ${
                    status === 200
                      ? "bg-green-900 text-green-300"
                      : "bg-red-900 text-red-300"
                  }`}
                >
                  {status}
                </span>
              )}
            </h2>
            {loading ? (
              <div className="px-4 py-8 rounded-lg bg-zinc-800 text-center text-zinc-400 animate-pulse">
                Изчакване на отговор от ментора...
              </div>
            ) : (
              <pre className="px-4 py-4 rounded-lg bg-zinc-800 text-sm overflow-x-auto whitespace-pre-wrap">
                {response}
              </pre>
            )}
          </section>
        )}
      </div>
    </div>
  );
}
