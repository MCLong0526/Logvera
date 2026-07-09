<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Models\Board;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    // GET /api/boards — boards the user belongs to.
    public function index(Request $request)
    {
        $boards = Board::query()
            ->whereHas('members', fn ($q) => $q->where('users.id', $request->user()->id))
            ->with('owner')
            ->withCount([
                'members', 'cards',
                'cards as assigned_count' => fn ($q) => $q->where('stage', 'assigned'),
                'cards as in_progress_count' => fn ($q) => $q->where('stage', 'in_progress'),
                'cards as closed_count' => fn ($q) => $q->where('stage', 'closed'),
            ])
            ->latest()
            ->get();

        return BoardResource::collection($boards);
    }

    // POST /api/boards
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['exists:users,id'],
        ]);

        $board = Board::create(['name' => $data['name'], 'owner_id' => $request->user()->id]);

        // Owner is always a member.
        $board->members()->attach(
            collect($data['member_ids'] ?? [])->map(fn ($id) => (int) $id)
                ->push($request->user()->id)->unique()->all()
        );

        return new BoardResource($board->load('owner')->loadCount(['members', 'cards']));
    }

    // GET /api/boards/{board}
    public function show(Request $request, Board $board)
    {
        $this->authorize('view', $board);

        return new BoardResource($board->load(['owner', 'members', 'cards.creator']));
    }

    // DELETE /api/boards/{board}
    public function destroy(Request $request, Board $board)
    {
        $this->authorize('delete', $board);

        $board->delete();

        return response()->json(['message' => 'Board deleted.']);
    }
}
