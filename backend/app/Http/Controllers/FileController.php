<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    // GET /api/files — all files from the user's projects, with filters.
    public function index(Request $request)
    {
        $user = $request->user();

        $files = Attachment::query()
            ->whereHas('project.members', fn ($q) => $q->where('users.id', $user->id))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('task_id'), fn ($q) => $q->where('task_id', $request->task_id))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))
            ->with(['user', 'project'])
            ->latest()
            ->paginate($request->integer('per_page', 24));

        return AttachmentResource::collection($files);
    }

    // GET /api/files/{attachment}/download
    public function download(Request $request, Attachment $attachment)
    {
        abort_unless($attachment->project->hasMember($request->user()), 403);
        abort_unless(Storage::disk('public')->exists($attachment->path), 404);

        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }

    // DELETE /api/files/{attachment}
    public function destroy(Request $request, Attachment $attachment)
    {
        // Uploader or project owner may delete.
        abort_unless(
            $attachment->user_id === $request->user()->id
                || $attachment->project->owner_id === $request->user()->id,
            403
        );

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['message' => 'File deleted.']);
    }
}
