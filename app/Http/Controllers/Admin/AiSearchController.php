<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiSearchService;
use Illuminate\Http\Request;

class AiSearchController extends Controller
{
    public function index()
    {
        return view('admin.ai.index');
    }

    public function ask(Request $request, AiSearchService $aiSearchService)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:300'],
        ]);

        $result = $aiSearchService->search($data['q']);

        return view('admin.ai.index', [
            'query' => $data['q'],
            'answer' => $result['answer'],
            'items' => $result['items'],
        ]);
    }
}
