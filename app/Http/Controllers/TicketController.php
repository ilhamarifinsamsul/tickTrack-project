<?php

namespace App\Http\Controllers;

use App\Http\Requests\TictekStoreRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request) {
        try {
            $query = Ticket::query();
        $query->orderBy('created_at', 'desc');

        if ($request->search) {
            $query->where('code', 'like', '%' . $request->search . '%')->orWhere('title', 'like', '%' . $request->search . '%');
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if (auth()->user()->role == 'user') {
            $query->where('user_id', auth()->user()->id);
        }

        $tickets = $query->get();
        return response()->json([
            'message' => 'List Tiket Berhasil Diambil',
            'data' => TicketResource::collection($tickets) // Jika data banyak, gunakan collection
        ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(TictekStoreRequest $request)
    {
        // Validate the request
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $ticket = new Ticket;

            $ticket->user_id = auth()->user()->id;
            $ticket->code = 'TKT-' . rand(1000, 99999);
            $ticket->title = $data['title'];
            $ticket->description = $data['description'];
            $ticket->priority = $data['priority'];
            $ticket->save();

            DB::commit();

            return response()->json([
                'message' => 'Ticket created successfully',
                'data' => new TicketResource($ticket) // Jika data satu, gunakan new
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
