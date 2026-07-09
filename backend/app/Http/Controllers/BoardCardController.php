<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardCardResource;
use App\Models\Board;
use App\Models\BoardCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoardCardController extends Controller
{
    // POST /api/boards/{board}/cards — new cards always land in "assigned".
    public function store(Request $request, Board $board)
    {
        $this->authorize('view', $board);

        $data = $request->validate(['title' => ['required', 'string', 'max:255']]);

        $card = $board->cards()->create([
            'title' => $data['title'],
            'stage' => 'assigned',
            'position' => ((int) $board->cards()->where('stage', 'assigned')->max('position')) + 1,
            'created_by' => $request->user()->id,
        ]);

        return new BoardCardResource($card->load('creator'));
    }

    // PUT /api/boards/{board}/cards/reorder — drag & drop persists the full
    // column layout: { columns: { assigned: [ids…], in_progress: […], closed: […] } }
    public function reorder(Request $request, Board $board)
    {
        $this->authorize('view', $board);

        $data = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*' => ['array'],
            'columns.*.*' => ['integer'],
        ]);

        foreach ($data['columns'] as $stage => $ids) {
            abort_unless(in_array($stage, BoardCard::STAGES), 422, 'Unknown stage.');

            foreach (array_values($ids) as $i => $id) {
                $board->cards()->whereKey($id)->update(['stage' => $stage, 'position' => $i]);
            }
        }

        return BoardCardResource::collection($board->cards()->with('creator')->get());
    }

    // DELETE /api/boards/{board}/cards/{card}
    public function destroy(Request $request, Board $board, BoardCard $card)
    {
        $this->authorize('view', $board);
        abort_unless($card->board_id === $board->id, 404);
        abort_unless(
            $card->created_by === $request->user()->id || $board->owner_id === $request->user()->id,
            403, 'Only the card creator or board owner can delete it.'
        );

        $card->delete();

        return response()->json(['message' => 'Card deleted.']);
    }
}
