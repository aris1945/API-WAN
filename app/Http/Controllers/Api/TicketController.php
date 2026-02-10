<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    // --- FUNGSI HELPER PRIVATE (LOGIKA GENERATOR) ---
    private function generateTicketNumber()
    {
        $lastTicket = Ticket::where('nomor_internal', 'like', 'INV-%')
                            ->orderBy('id', 'desc')
                            ->first();

        if (!$lastTicket) {
            return 'INV-0001';
        }

        $lastNumber = (int) substr($lastTicket->nomor_internal, 4);
        $newNumber = $lastNumber + 1;
        return 'INV-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // --- API UNTUK FRONTEND (PREVIEW NOMOR) ---
    public function getNextNumber()
    {
        $nextNumber = $this->generateTicketNumber();
        return response()->json([
            'status' => true,
            'ticket_number' => $nextNumber
        ]);
    }

    // --- FUNGSI INDEX (LIST TIKET) ---
    public function index(Request $request)
    {
        $user = $request->user(); 
        $query = Ticket::latest();

        // LOGIKA FILTER BERDASARKAN ROLE
        if ($user->role === 'teknisi') {
            // Filter: Tampilkan tiket jika kolom 'petugas' mengandung NIK si user
            $query->where('petugas', 'like', '%' . $user->nik . '%');
        }

        // Filter Pencarian Tiket
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_internal', 'like', "%{$search}%")
                  ->orWhere('site_name', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'status' => true,
            'data' => $query->paginate(10)
        ]);
    }

    // --- FUNGSI STORE (SIMPAN DATA BARU) ---
    public function store(Request $request)
    {
        $request->validate([
            'unit' => 'required',
            'jenis' => 'required',
            'deskripsi' => 'required',
        ]);

        $ticketNumber = $this->generateTicketNumber();

        $ticket = Ticket::create([
            'nomor_internal' => $ticketNumber,
            'nomor_sistem' => $request->nomor_sistem,
            'unit' => $request->unit,
            'jenis' => $request->jenis,
            'site_name' => $request->site_name,
            'site_id'   => $request->site_id, // Pastikan ID tersimpan
            'deskripsi' => $request->deskripsi,
            'petugas' => $request->petugas,
            'status' => 'Open', // Default status
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Tiket berhasil dibuat',
            'data' => $ticket
        ]);
    }

    // --- FUNGSI SHOW (DETAIL TIKET) - PENTING UNTUK HALAMAN EDIT ---
    public function show($id)
    {
        // Load tiket beserta relasi logs dan user pembuat log
        $ticket = Ticket::with(['logs.user'])->find($id);

        if (!$ticket) {
            return response()->json(['status' => false, 'message' => 'Not Found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $ticket
        ]);
    }
	
	public function addLog(Request $request, $id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return response()->json(['message' => 'Not Found'], 404);

        $request->validate([
            'status' => 'required',
            'deskripsi' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            
            // --- PERBAIKAN UTAMA ADA DISINI ---
            
            // Parameter ke-3 adalah 'public'. Ini WAJIB agar masuk ke storage/app/public
            $image->storeAs('evident', $filename, 'public'); 
            
            // Simpan path untuk Database (tanpa /public di depan)
            // Karena symlink memetakan 'storage' -> 'storage/app/public'
            $imagePath = 'storage/evident/' . $filename; 
        }

        $ticket->logs()->create([
            'user_id' => $request->user()->id,
            'status' => $request->status,
            'deskripsi' => $request->deskripsi,
            'image_path' => $imagePath
        ]);

        $ticket->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => 'Worklog berhasil ditambahkan']);
    }

    // --- FUNGSI UPDATE (EDIT DATA) - INI YANG SEBELUMNYA HILANG ---
    public function update(Request $request, $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['status' => false, 'message' => 'Tiket tidak ditemukan'], 404);
        }

        // --- LOGIKA KEAMANAN BARU ---
        // Cek Role user yang sedang login
        $userRole = $request->user()->role;
        
        // Cek Status tiket saat ini di database
        $currentStatus = $ticket->status;

        // Jika user adalah TEKNISI dan status tiket sudah CLOSED
        if ($userRole === 'teknisi' && $currentStatus === 'Closed') {
            return response()->json([
                'status' => false,
                'message' => 'Akses Ditolak: Tiket dengan status Closed tidak dapat diedit lagi oleh Teknisi.'
            ], 403); // 403 Forbidden
        }
        // -----------------------------

        // Validasi input
        $request->validate([
            'unit' => 'sometimes|required',
            'deskripsi' => 'sometimes|required',
        ]);

        // Lakukan Update
        $updated = $ticket->update([
            'nomor_sistem' => $request->nomor_sistem,
            'unit'         => $request->unit,
            'jenis'        => $request->jenis,
            'site_name'    => $request->site_name,
            'site_id'      => $request->site_id,
            'deskripsi'    => $request->deskripsi,
            'petugas'      => $request->petugas,
            'status'       => $request->status ?? $ticket->status,
        ]);

        if ($updated) {
            return response()->json(['status' => true, 'message' => 'Tiket berhasil diperbarui', 'data' => $ticket]);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal memperbarui tiket'], 500);
        }
    }

    // --- FUNGSI DESTROY (HAPUS TIKET) ---
    public function destroy(Request $request, $id)
    {
        // 1. Cek Role User
        $userRole = $request->user()->role;

        // Jika role bukan admin DAN bukan helpdesk (artinya teknisi)
        if ($userRole !== 'admin' && $userRole !== 'helpdesk') {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus tiket.'
            ], 403); // 403 Forbidden
        }

        // 2. Lanjut Hapus
        $ticket = Ticket::find($id);
        if (!$ticket) return response()->json(['message' => 'Not Found'], 404);
        
        $ticket->delete();
        return response()->json(['status' => true, 'message' => 'Tiket dihapus']);
    }
}