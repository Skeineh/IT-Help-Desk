<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Priority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    private string $apiKey;
    private string $model = 'gpt-3.5-turbo';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
    }

    // POST /api/ai/categorize
    public function categorize(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'required|string',
        ]);

        $categories = Category::where('IsActive', true)->get(['CategoryNumber', 'CategoryName']);
        $categoryList = $categories->map(fn ($c) => "{$c->CategoryNumber}: {$c->CategoryName}")->implode(', ');

        $prompt = "You are an IT help desk classifier. Given a support ticket, select the best matching category.\n\n"
            . "Available categories: {$categoryList}\n\n"
            . "Ticket title: {$request->title}\n"
            . "Ticket description: {$request->description}\n\n"
            . "Respond with ONLY the category number (integer). No explanation.";

        $result = $this->callOpenAI($prompt);
        if ($result['error']) {
            return response()->json(['message' => $result['error']], 502);
        }

        $categoryNumber = (int) trim($result['content']);
        $category = $categories->firstWhere('CategoryNumber', $categoryNumber);

        return response()->json([
            'CategoryNumber' => $categoryNumber,
            'CategoryName'   => $category?->CategoryName ?? 'Unknown',
        ]);
    }

    // POST /api/ai/priority
    public function detectPriority(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'required|string',
        ]);

        $priorities = Priority::orderBy('PriorityLevel')->get(['PriorityNumber', 'PriorityName', 'PriorityLevel']);
        $priorityList = $priorities->map(fn ($p) => "{$p->PriorityNumber}: {$p->PriorityName} (level {$p->PriorityLevel})")->implode(', ');

        $prompt = "You are an IT help desk triage specialist. Assess the urgency of this support ticket and assign a priority.\n\n"
            . "Available priorities: {$priorityList}\n\n"
            . "Priority guidelines:\n"
            . "- Critical/Urgent: System down, data loss, security breach, affects many users\n"
            . "- High: Major feature broken, significant productivity impact\n"
            . "- Medium: Partial functionality affected, workaround available\n"
            . "- Low: Minor issues, cosmetic, non-urgent requests\n\n"
            . "Ticket title: {$request->title}\n"
            . "Ticket description: {$request->description}\n\n"
            . "Respond with ONLY the priority number (integer). No explanation.";

        $result = $this->callOpenAI($prompt);
        if ($result['error']) {
            return response()->json(['message' => $result['error']], 502);
        }

        $priorityNumber = (int) trim($result['content']);
        $priority = $priorities->firstWhere('PriorityNumber', $priorityNumber);

        return response()->json([
            'PriorityNumber' => $priorityNumber,
            'PriorityName'   => $priority?->PriorityName ?? 'Unknown',
        ]);
    }

    // POST /api/ai/chat
    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role'    => 'required|in:user,assistant',
            'messages.*.content' => 'required|string',
        ]);

        $systemMessage = [
            'role'    => 'system',
            'content' => 'You are a helpful IT support assistant for an internal IT help desk system. '
                . 'Help users troubleshoot technical issues, guide them through common IT problems, '
                . 'and advise when they should submit a formal support ticket. '
                . 'Keep responses concise, practical, and friendly. '
                . 'If the issue is complex or requires hands-on support, recommend submitting a ticket.',
        ];

        $messages = array_merge([$systemMessage], $request->messages);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $this->model,
            'messages'    => $messages,
            'max_tokens'  => 500,
            'temperature' => 0.7,
        ]);

        if (!$response->successful()) {
            return response()->json(['message' => 'AI service unavailable. Please try again.'], 502);
        }

        $content = $response->json('choices.0.message.content', '');

        return response()->json(['reply' => trim($content)]);
    }

    // POST /api/ai/summarize
    public function summarize(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'required|string',
            'category'    => 'nullable|string',
            'priority'    => 'nullable|string',
            'status'      => 'nullable|string',
            'assignedTo'  => 'nullable|string',
            'comments'    => 'nullable|array',
            'comments.*.body' => 'string',
            'resolution_notes' => 'nullable|string',
        ]);

        $commentsText = '';
        if (!empty($request->comments)) {
            $lines = array_map(fn ($c) => "- " . ($c['user'] ?? 'User') . ": " . $c['body'], $request->comments);
            $commentsText = "\n\nComments:\n" . implode("\n", array_slice($lines, 0, 10));
        }

        $prompt = "You are an IT help desk assistant. Write a concise 3-5 sentence summary of this support ticket. "
            . "Cover the core issue, current status, and any key updates. Be factual and neutral.\n\n"
            . "Title: {$request->title}\n"
            . "Category: " . ($request->category ?? 'N/A') . "\n"
            . "Priority: " . ($request->priority ?? 'N/A') . "\n"
            . "Status: " . ($request->status ?? 'N/A') . "\n"
            . "Assigned To: " . ($request->assignedTo ?? 'Unassigned') . "\n"
            . "Description: {$request->description}"
            . $commentsText
            . ($request->resolution_notes ? "\n\nResolution Notes: {$request->resolution_notes}" : '');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $this->model,
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'max_tokens'  => 300,
            'temperature' => 0.4,
        ]);

        if (!$response->successful()) {
            return response()->json(['message' => 'AI service unavailable. Please try again.'], 502);
        }

        return response()->json(['summary' => trim($response->json('choices.0.message.content', ''))]);
    }

    // POST /api/ai/troubleshoot
    public function troubleshoot(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'required|string',
            'category'    => 'nullable|string',
        ]);

        $prompt = "You are a senior IT support technician. Based on this support ticket, provide 4-6 practical troubleshooting steps the user can try themselves, in numbered list format. "
            . "Be specific, actionable, and ordered from simplest to most complex. "
            . "If the issue clearly requires admin/hardware access, note that as a final step.\n\n"
            . "Ticket title: {$request->title}\n"
            . "Category: " . ($request->category ?? 'General IT') . "\n"
            . "Description: {$request->description}\n\n"
            . "Format each step as: \"1. [Action] — [Brief explanation]\"";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $this->model,
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'max_tokens'  => 600,
            'temperature' => 0.3,
        ]);

        if (!$response->successful()) {
            return response()->json(['message' => 'AI service unavailable. Please try again.'], 502);
        }

        return response()->json(['steps' => trim($response->json('choices.0.message.content', ''))]);
    }

    private function callOpenAI(string $prompt): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $this->model,
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'max_tokens'  => 10,
            'temperature' => 0,
        ]);

        if (!$response->successful()) {
            return ['content' => '', 'error' => 'AI service unavailable. Please try again.'];
        }

        return ['content' => $response->json('choices.0.message.content', ''), 'error' => null];
    }
}
