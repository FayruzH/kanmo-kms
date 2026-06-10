<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFeedback;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Throwable;

class ChatbotFeedbackController extends Controller
{
    public function create()
    {
        return view('employee.feedback');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:3000'],
            'detail' => ['nullable', 'string', 'max:3000'],
        ]);

        $actor = $this->resolveInteractionUser($request);
        $payload = [
            'user_id' => $actor?->id,
            'user_name' => $actor?->name,
            'user_email' => $actor?->email,
            'question' => $data['question'],
            'detail' => $data['detail'] ?? null,
        ];

        try {
            ChatbotFeedback::query()->create($payload);
        } catch (QueryException $exception) {
            report($exception);

            return redirect()
                ->route('employee.feedback.create')
                ->withInput()
                ->with('warning', 'Feedback belum bisa disimpan karena database sedang bermasalah. Silakan coba lagi.');
        }

        return redirect()
            ->route('employee.feedback.create')
            ->with('success', 'Terima kasih. Feedback kamu sudah terkirim ke admin.');
    }

    private function resolveInteractionUser(Request $request): ?User
    {
        if ($request->user()) {
            return $request->user();
        }

        $sessionKey = 'public_interaction_user_id';
        $sessionUserId = $request->session()->get($sessionKey);
        if ($sessionUserId) {
            try {
                $user = User::query()->find((int) $sessionUserId);
                if ($user) {
                    return $user;
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return null;
    }
}
