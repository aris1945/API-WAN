<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nomor_internal' => 'required|unique:tickets,nomor_internal', // Wajib & Unik
            'nomor_sistem' => 'nullable', // Boleh Kosong
            'unit' => 'required',
            'jenis' => 'required',
            'site_name' => 'required',
            'deskripsi' => 'required',
            'petugas' => 'required',
        ]);

        try {
            $ticket = Ticket::create([
                'nomor_internal' => $request->nomor_internal,
                'nomor_sistem' => $request->nomor_sistem, // Bisa null
                'unit' => $request->unit,
                'jenis' => $request->jenis,
                'site_name' => $request->site_name,
                'deskripsi' => $request->deskripsi,
                'petugas' => $request->petugas,
                'status' => 'Open'
            ]);

            return response()->json(['status' => true, 'message' => 'Tiket dibuat!', 'data' => $ticket], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // Opsional: Untuk melihat list tiket nanti
    public function index(Request $request)
    {
        $query = Ticket::query();

        // Fitur Pencarian
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nomor_internal', 'like', "%{$search}%")
                  ->orWhere('nomor_sistem', 'like', "%{$search}%")
                  ->orWhere('site_name', 'like', "%{$search}%")
                  ->orWhere('petugas', 'like', "%{$search}%");
        }

        // Urutkan dari yang terbaru
        $tickets = $query->latest()->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $tickets
        ]);
    }

public function update(Request $request, $id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return response()->json(['message' => 'Tiket tidak ditemukan'], 404);

        $ticket->update($request->all());

        return response()->json(['status' => true, 'message' => 'Tiket berhasil diupdate!', 'data' => $ticket]);
    }
}