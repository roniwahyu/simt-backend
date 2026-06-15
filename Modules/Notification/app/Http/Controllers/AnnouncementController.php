<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Announcement::with(['creator']);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('published_only')) {
            $query->published()->notExpired();
        }

        $announcements = $query->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $categories = ['UMUM', 'AKADEMIK', 'KEGIATAN', 'KEUANGAN', 'URGENT'];

        return view('announcements.index', compact('announcements', 'categories'));
    }

    public function create(): View
    {
        $categories = ['UMUM', 'AKADEMIK', 'KEGIATAN', 'KEUANGAN', 'URGENT'];

        return view('announcements.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:UMUM,AKADEMIK,KEGIATAN,KEUANGAN,URGENT',
            'is_pinned' => 'boolean',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['created_by'] = auth()->id();

        if (!isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if (!isset($validated['is_pinned'])) {
            $validated['is_pinned'] = false;
        }

        $announcement = Announcement::create($validated);

        return redirect()->route('announcements.show', $announcement)
            ->with('success', 'Pengumuman berhasil dibuat.');
    }

    public function show(Announcement $announcement): View
    {
        $announcement->load('creator');

        return view('announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement): View
    {
        $categories = ['UMUM', 'AKADEMIK', 'KEGIATAN', 'KEUANGAN', 'URGENT'];

        return view('announcements.edit', compact('announcement', 'categories'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:UMUM,AKADEMIK,KEGIATAN,KEUANGAN,URGENT',
            'is_pinned' => 'boolean',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
        ]);

        if (!isset($validated['is_pinned'])) {
            $validated['is_pinned'] = false;
        }

        $announcement->update($validated);

        return redirect()->route('announcements.show', $announcement)
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }

    public function togglePin(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['is_pinned' => !$announcement->is_pinned]);

        $status = $announcement->is_pinned ? 'disematkan' : 'lepas sematan';

        return redirect()->back()
            ->with('success', "Pengumuman berhasil {$status}.");
    }

    public function publish(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['published_at' => now()]);

        return redirect()->back()
            ->with('success', 'Pengumuman berhasil dipublikasikan.');
    }
}
