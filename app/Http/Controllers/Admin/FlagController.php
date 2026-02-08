<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement\AnnouncementFlag;
use App\Services\Announcement\FlagService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FlagController extends Controller
{
    public function __construct(
        private FlagService $service
    ) {}

    /**
     * Display flags moderation dashboard
     */
    public function index(): Response
    {
        return Inertia::render('admin/flags/Index', [
            // Synchronous
            'summary' => FlagService::summary(),
            
            // Deferred
            'flags' => Inertia::defer(fn() => FlagService::pending()),
        ]);
    }

    /**
     * Mark flag as reviewed
     */
    public function review(AnnouncementFlag $flag): RedirectResponse
    {
        try {
            $this->service->review($flag);

            return redirect()->route('admin.flags.index')
                ->with('flash', [
                    'type' => 'success',
                    'message' => 'Flag marked as reviewed.',
                ]);
        } catch (\LogicException $e) {
            return redirect()->route('admin.flags.index')
                ->with('flash', [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ]);
        }
    }

    /**
     * Dismiss flag
     */
    public function dismiss(AnnouncementFlag $flag): RedirectResponse
    {
        try {
            $this->service->dismiss($flag);

            return redirect()->route('admin.flags.index')
                ->with('flash', [
                    'type' => 'success',
                    'message' => 'Flag dismissed.',
                ]);
        } catch (\LogicException $e) {
            return redirect()->route('admin.flags.index')
                ->with('flash', [
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ]);
        }
    }

    /**
     * Delete the flagged content (admin override)
     */
    public function destroyContent(AnnouncementFlag $flag): RedirectResponse
    {
        // Delete the flagged content based on type
        $flaggable = $flag->flaggable;
        
        if (!$flaggable) {
            return redirect()->route('admin.flags.index')
                ->with('flash', [
                    'type' => 'error',
                    'message' => 'Content already deleted.',
                ]);
        }

        $flaggable->delete();

        return redirect()->route('admin.flags.index')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Flagged content deleted.',
            ]);
    }
}
