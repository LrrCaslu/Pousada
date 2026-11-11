<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MediaController extends Controller
{
    public function index()
    {
        $photos = config('site-media.photos', []);
        $selected = Setting::read('site_hero_photo', config('site-media.hero_default'));
        if (!array_key_exists($selected, $photos)) {
            $selected = config('site-media.hero_default');
        }
        return view('admin.media.index', compact('photos', 'selected'));
    }

    public function update(Request $request)
    {
        $valid = $request->validate([
            'hero_photo' => ['required', 'string', Rule::in(array_keys(config('site-media.photos', [])))],
        ]);
        Setting::updateOrCreate(['key' => 'site_hero_photo'], ['value' => $valid['hero_photo']]);
        AuditService::record('site.hero_atualizado');
        return redirect()->route('admin.media.index')->with('success', 'Imagem de destaque atualizada no site público.');
    }
}
