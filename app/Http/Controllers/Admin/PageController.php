<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $pages = Page::orderBy('title')->get();
        $selected = $pages->firstWhere('id', (int) $request->query('page')) ?? $pages->first();

        return view('admin.pages.index', [
            'pages' => $pages,
            'selected' => $selected,
            'publishedCount' => $pages->where('published', true)->count(),
            'draftCount' => $pages->where('published', false)->count(),
        ]);
    }


    public function store(Request $request)
    {
        $request->merge(['slug' => Str::slug((string) ($request->input('slug') ?: $request->input('title', '')))]);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')],
            'body' => ['nullable', 'string', 'max:40000'],
            'published' => ['nullable', 'boolean'],
        ]);
        $validated['published'] = (bool) ($validated['published'] ?? false);
        $page = Page::create($validated);
        AuditService::record('pagina.criada', 'page', $page->id);
        return redirect()->route('admin.pages.index', ['page' => $page->id])
            ->with('success', 'Nova página criada com sucesso.');
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:40000'],
            'published' => ['required', 'boolean'],
        ]);

        if ($request->input('intent') === 'draft') {
            $validated['published'] = false;
        } elseif ($request->input('intent') === 'publish') {
            $validated['published'] = true;
        }

        $page->update($validated);
        AuditService::record('pagina.atualizada', 'page', $page->id);

        return redirect()->route('admin.pages.index', ['page' => $page->id])
            ->with('success', 'A página foi atualizada com sucesso.');
    }
}
