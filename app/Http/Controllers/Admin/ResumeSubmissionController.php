<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobPosition;
use App\Models\ResumeSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResumeSubmissionController extends Controller
{
    /**
     * Display resume submissions and job positions
     */
    public function index(Request $request)
    {
        $query = ResumeSubmission::query();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by position
        if ($request->has('position') && $request->position !== '') {
            $query->where('position', $request->position);
        }

        // Search
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $submissions = $query->orderBy('created_at', 'desc')->paginate(20);

        $jobPositions = JobPosition::ordered()->get();

        // Get unique positions from submissions for filter
        $submissionPositions = ResumeSubmission::distinct()->pluck('position');

        return Inertia::render('admin/ResumeSubmissions/Index', [
            'submissions' => $submissions,
            'jobPositions' => $jobPositions,
            'submissionPositions' => $submissionPositions,
            'filters' => $request->only(['status', 'position', 'search']),
        ]);
    }

    /**
     * Show a single submission
     */
    public function show(ResumeSubmission $resumeSubmission)
    {
        return Inertia::render('admin/ResumeSubmissions/Show', [
            'submission' => $resumeSubmission,
        ]);
    }

    /**
     * Update submission status
     */
    public function updateStatus(Request $request, ResumeSubmission $resumeSubmission)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,reviewed,contacted,hired,rejected',
            'notes' => 'nullable|string',
        ]);

        $resumeSubmission->update($validated);

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    /**
     * Delete a submission
     */
    public function destroy(ResumeSubmission $resumeSubmission)
    {
        $resumeSubmission->delete();

        return redirect()->route('admin.resume-submissions.index')
            ->with('success', 'Submission deleted successfully.');
    }

    /**
     * Store a new job position
     */
    public function storePosition(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:job_positions',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        JobPosition::create($validated);

        return redirect()->back()->with('success', 'Job position added successfully.');
    }

    /**
     * Update a job position
     */
    public function updatePosition(Request $request, JobPosition $jobPosition)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:job_positions,title,'.$jobPosition->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $jobPosition->update($validated);

        return redirect()->back()->with('success', 'Job position updated successfully.');
    }

    /**
     * Toggle position active status
     */
    public function togglePosition(JobPosition $jobPosition)
    {
        $jobPosition->update(['is_active' => ! $jobPosition->is_active]);

        return redirect()->back()->with('success', 'Position status updated.');
    }

    /**
     * Delete a job position
     */
    public function destroyPosition(JobPosition $jobPosition)
    {
        // Check if there are submissions for this position
        $count = ResumeSubmission::where('position', $jobPosition->title)->count();

        if ($count > 0) {
            return redirect()->back()->withErrors(['position' => 'Cannot delete position with existing submissions.']);
        }

        $jobPosition->delete();

        return redirect()->back()->with('success', 'Job position deleted successfully.');
    }
}
