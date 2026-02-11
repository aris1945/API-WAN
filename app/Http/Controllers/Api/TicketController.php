<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Services\TelegramService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
        $query = Ticket::query();

        // 1. ADMIN / HELPDESK: Melihat SEMUA tiket
        if (in_array($user->role, ['admin', 'helpdesk'])) {
            // No filter
        } 
        // 2. HSA / KORLAP: Hanya melihat tiket di SA mereka
        elseif (in_array($user->role, ['hsa', 'korlap'])) {
            if ($user->sa_code) {
                $query->where('sa', $user->sa_code);
            } else {
                $query->where('id', 0); 
            }
        }
        // 3. TEKNISI: Hanya melihat tiket yang ditugaskan
        elseif ($user->role === 'teknisi') {
            $query->where(function($q) use ($user) {
                $q->where('petugas', 'like', '%' . $user->name . '%')
                  ->orWhere('petugas', 'like', '%' . $user->nik . '%');
            });
        }

        // SEARCH FILTER
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_internal', 'like', "%$search%")
                  ->orWhere('site_name', 'like', "%$search%")
                  ->orWhere('deskripsi', 'like', "%$search%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'List Tickets',
            'data' => $tickets
        ]);
    }

    // --- FUNGSI STORE (SIMPAN DATA BARU) ---
    public function store(Request $request)
    {
        $request->validate([
            'unit' => 'required',
            'deskripsi' => 'required',
            'sa' => 'required|string',
        ]);

        $nomor_internal = $this->generateTicketNumber(); // Pakai helper yang sudah dibuat

        $ticket = Ticket::create([
            'nomor_internal' => $nomor_internal,
            'nomor_sistem' => $request->nomor_sistem,
            'unit' => $request->unit,
            'jenis' => $request->jenis,
            'sa' => $request->sa,
            'site_name' => $request->site_name,
            'site_id' => $request->site_id,
            'deskripsi' => $request->deskripsi,
            'petugas' => $request->petugas, 
            'status' => 'Open',
            // closed_at otomatis NULL saat create
        ]);

        dispatch(function() use ($ticket) {
             \App\Services\GoogleSheetService::send($ticket, 'create');
        })->afterResponse();

        try {
            $this->notifyTechnicians($ticket);
        } catch (\Exception $e) {
            Log::error("Gagal mengirim notifikasi Telegram: " . $e->getMessage());
        }

        return response()->json([
            'status' => true, 
            'message' => 'Tiket berhasil dibuat', 
            'data' => $ticket
        ], 201);
    }

    // --- FUNGSI SHOW (DETAIL TIKET) ---
    public function show($id)
    {
        $ticket = Ticket::with(['logs.user'])->find($id);

        if (!$ticket) {
            return response()->json(['status' => false, 'message' => 'Not Found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $ticket
        ]);
    }
    
    // --- FUNGSI ADD LOG (UPDATE PROGRESS TEKNISI) ---
    public function addLog(Request $request, $id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return response()->json(['message' => 'Not Found'], 404);

        $request->validate([
            'status' => 'required',
            'deskripsi' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'odp' => 'nullable|string',
            'odc' => 'nullable|string',
            'ftm' => 'nullable|string',
        ]);

        // 1. Upload Gambar
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('evident', $filename, 'public'); 
            $imagePath = 'storage/evident/' . $filename; 
        }

        // 2. Simpan Log
        $ticket->logs()->create([
            'user_id' => $request->user()->id,
            'status' => $request->status,
            'deskripsi' => $request->deskripsi . 
                           ($request->odp ? " [ODP: $request->odp]" : "") .
                           ($request->odc ? " [ODC: $request->odc]" : ""),
            'image_path' => $imagePath
        ]);

        // 3. Update Status & Segmentasi di Tabel Utama Tiket
        $updateData = ['status' => $request->status];
        
        // --- LOGIKA CLOSED_AT (UPDATE 1) ---
        if ($request->status === 'Closed') {
            // Jika status berubah jadi Closed, isi waktunya
            if ($ticket->status !== 'Closed') {
                $updateData['closed_at'] = now();
            }
        } else {
            // Jika status BUKAN Closed (misal re-open), kosongkan lagi
            $updateData['closed_at'] = null;
        }
        // -----------------------------------

        if ($request->filled('odp')) $updateData['odp'] = $request->odp;
        if ($request->filled('odc')) $updateData['odc'] = $request->odc;
        if ($request->filled('ftm')) $updateData['ftm'] = $request->ftm;

        $ticket->update($updateData);

        // 4. Kirim ke Google Sheet
        $ticket->refresh(); 
        try {
            \App\Services\GoogleSheetService::send($ticket, 'update');
        } catch (\Exception $e) {
             Log::error("Gagal update Sheet: " . $e->getMessage());
        }

        return response()->json(['status' => true, 'message' => 'Worklog berhasil diupdate']);
    }

    // --- FUNGSI UPDATE (EDIT DATA ADMIN) ---
    public function update(Request $request, $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['status' => false, 'message' => 'Tiket tidak ditemukan'], 404);
        }

        $userRole = $request->user()->role;
        $currentStatus = $ticket->status;

        // Cek Izin Teknisi
        if ($userRole === 'teknisi' && $currentStatus === 'Closed') {
            return response()->json([
                'status' => false,
                'message' => 'Akses Ditolak: Tiket Closed tidak dapat diedit teknisi.'
            ], 403);
        }

        $request->validate([
            'unit' => 'sometimes|required',
            'deskripsi' => 'sometimes|required',
        ]);

        // Siapkan data update
        $dataToUpdate = [
            'nomor_sistem' => $request->nomor_sistem,
            'unit'         => $request->unit,
            'jenis'        => $request->jenis,
            'site_name'    => $request->site_name,
            'sa'           => $request->sa,
            'site_id'      => $request->site_id,
            'deskripsi'    => $request->deskripsi,
            'petugas'      => $request->petugas,
            'status'       => $request->status ?? $ticket->status,
        ];

        // --- LOGIKA CLOSED_AT (UPDATE 2) ---
        // Jika Admin mengubah status lewat menu Edit
        if ($request->has('status')) {
            if ($request->status === 'Closed') {
                 // Hanya set waktu jika sebelumnya belum closed
                 if ($ticket->status !== 'Closed') {
                     $dataToUpdate['closed_at'] = now();
                 }
            } else {
                 // Reset jika dibuka kembali
                 $dataToUpdate['closed_at'] = null;
            }
        }
        // -----------------------------------

        $updated = $ticket->update($dataToUpdate);

        \App\Services\GoogleSheetService::send($ticket, 'update');

        if ($updated) {
            return response()->json(['status' => true, 'message' => 'Tiket berhasil diperbarui', 'data' => $ticket]);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal memperbarui tiket'], 500);
        }
    }

    // --- FUNGSI DESTROY (HAPUS TIKET) ---
    public function destroy(Request $request, $id)
    {
        $userRole = $request->user()->role;

        if ($userRole !== 'admin' && $userRole !== 'helpdesk') {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus tiket.'
            ], 403);
        }

        $ticket = Ticket::find($id);
        if (!$ticket) return response()->json(['message' => 'Not Found'], 404);
        
        $ticket->delete();
        return response()->json(['status' => true, 'message' => 'Tiket dihapus']);
    }
    
    // --- NOTIFIKASI TELEGRAM ---
    private function notifyTechnicians($ticket)
    {
        $petugasString = $ticket->petugas;
        preg_match_all('/\((.*?)\)/', $petugasString, $matches);
        $niks = $matches[1]; 

        if (empty($niks)) return;

        $users = User::whereIn('nik', $niks)
                     ->whereNotNull('telegram_chat_id')
                     ->get();

        foreach ($users as $user) {
            $message = "🚨 <b>TIKET BARU DITUGASKAN!</b>\n\n" .
                       "🆔 <b>No:</b> {$ticket->nomor_internal}\n" .
                       "📍 <b>Site:</b> {$ticket->site_id} - {$ticket->site_name}\n" .
                       "⚠ <b>Deskripsi:</b> {$ticket->deskripsi}\n" .
                       "🛠 <b>Unit:</b> {$ticket->unit}\n\n" .
                       "Segera cek aplikasi untuk detailnya.";

            TelegramService::sendMessage($user->telegram_chat_id, $message);
        }
    }

    // --- DASHBOARD STATS ---
    public function dashboardStats(Request $request)
    {
        $user = $request->user();
        $query = Ticket::query();

        if (in_array($user->role, ['hsa', 'korlap'])) {
            if ($user->sa_code) {
                $query->where('sa', $user->sa_code);
            } else {
                $query->where('id', 0);
            }
        } elseif ($user->role === 'teknisi') {
            $query->where(function($q) use ($user) {
                $q->where('petugas', 'like', '%' . $user->name . '%')
                  ->orWhere('petugas', 'like', '%' . $user->nik . '%');
            });
        }

        $total = (clone $query)->count();
        $open = (clone $query)->where('status', 'Open')->count();
        $progress = (clone $query)->whereIn('status', ['On The Way', 'On Site', 'In Progress'])->count();
        $closed = (clone $query)->where('status', 'Closed')->count();

        return response()->json([
            'status' => true,
            'data' => [
                'total' => $total,
                'open' => $open,
                'in_progress' => $progress,
                'closed' => $closed
            ]
        ]);
    }
}