<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFeedback;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ChatbotFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->input('search'));

        try {
            $items = ChatbotFeedback::query()
                ->with(['user:id,name,email'])
                ->when($keyword !== '', function ($query) use ($keyword) {
                    $query->where(function ($innerQuery) use ($keyword) {
                        $innerQuery->where('question', 'like', '%' . $keyword . '%')
                            ->orWhere('detail', 'like', '%' . $keyword . '%')
                            ->orWhere('user_name', 'like', '%' . $keyword . '%')
                            ->orWhere('user_email', 'like', '%' . $keyword . '%')
                            ->orWhereHas('user', function ($userQuery) use ($keyword) {
                                $userQuery->where('name', 'like', '%' . $keyword . '%')
                                    ->orWhere('email', 'like', '%' . $keyword . '%')
                                    ->orWhere('nip', 'like', '%' . $keyword . '%');
                            });
                        });
                })
                ->latest()
                ->paginate(15)
                ->withQueryString();
        } catch (QueryException $exception) {
            report($exception);

            return view('admin.feedback.index', [
                'items' => $this->emptyPaginator($request),
                'loadError' => 'Data feedback belum bisa dimuat. Cek koneksi database/migration.',
            ]);
        }

        return view('admin.feedback.index', [
            'items' => $items,
        ]);
    }

    private function emptyPaginator(Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            15,
            max(1, (int) $request->integer('page', 1)),
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
