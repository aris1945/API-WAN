<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Services\TelegramService;
use App\Models\User;

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
        // 1. Validasi Input
        $request->validate([
            'unit' => 'required',
            'deskripsi' => 'required',
            // Tambahkan validasi lain jika perlu
        ]);

        // 2. GENERATE NOMOR INTERNAL (BAGIAN YANG HILANG TADI)
        // Logika: Cari tiket terakhir, ambil ID-nya, tambah 1.
        // Format Contoh: TKT-0001, TKT-0002
        $lastTicket = Ticket::orderBy('id', 'desc')->first();
        $nextId = $lastTicket ? $lastTicket->id + 1 : 1;
        $nomor_internal = 'INV-' . sprintf('%04d', $nextId); 
        // ----------------------------------------------------

        // 3. Simpan ke Database
        $ticket = Ticket::create([
            'nomor_internal' => $nomor_internal, // Variabel ini sekarang sudah ada isinya
            'nomor_sistem' => $request->nomor_sistem,
            'unit' => $request->unit,
            'jenis' => $request->jenis,
            'site_name' => $request->site_name,
            'site_id' => $request->site_id,
            'deskripsi' => $request->deskripsi,
            'petugas' => $request->petugas, 
            'status' => 'Open',
        ]);
        dispatch(function() use ($ticket) {
             \App\Services\GoogleSheetService::send($ticket, 'create');
        })->afterResponse();

        // 4. Kirim Notifikasi Telegram (Dengan Pengaman Try-Catch)
        try {
            // Pastikan fungsi notifyTechnicians ada di paling bawah class Controller ini
            $this->notifyTechnicians($ticket);
        } catch (\Exception $e) {
            // Jika Telegram error, catat di log saja, jangan bikin error 500 di browser
            Log::error("Gagal mengirim notifikasi Telegram: " . $e->getMessage());
        }

        return response()->json([
            'status' => true, 
            'message' => 'Tiket berhasil dibuat', 
            'data' => $ticket
        ], 201);
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
            // Validasi field baru (nullable/boleh kosong)
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

        // 2. Simpan Log (Riwayat)
        $ticket->logs()->create([
            'user_id' => $request->user()->id,
            'status' => $request->status,
            // Kita gabungkan info segmentasi ke deskripsi log agar terbaca di riwayat
            'deskripsi' => $request->deskripsi . 
                           ($request->odp ? " [ODP: $request->odp]" : "") .
                           ($request->odc ? " [ODC: $request->odc]" : ""),
            'image_path' => $imagePath
        ]);

        // 3. Update Status & Segmentasi di Tabel Utama Tiket
        $updateData = ['status' => $request->status];
        
        // Hanya update jika user mengisi data (agar data lama tidak tertimpa kosong)
        if ($request->filled('odp')) $updateData['odp'] = $request->odp;
        if ($request->filled('odc')) $updateData['odc'] = $request->odc;
        if ($request->filled('ftm')) $updateData['ftm'] = $request->ftm;

        $ticket->update($updateData);

        // 4. Kirim ke Google Sheet
        $ticket->refresh(); 
        try {
            \App\Services\GoogleSheetService::send($ticket, 'update');
        } catch (\Exception $e) {
             \Log::error("Gagal update Sheet: " . $e->getMessage());
        }

        return response()->json(['status' => true, 'message' => 'Worklog & Segmentasi berhasil diupdate']);
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
	
	private function notifyTechnicians($ticket)
    {
        // 1. Ambil string petugas, misal: "Budi (1001), Andi (1002)"
        $petugasString = $ticket->petugas;
        
        // 2. Ekstrak NIK menggunakan Regex
        // Mencari angka di dalam kurung (...)
        preg_match_all('/\((.*?)\)/', $petugasString, $matches);
        $niks = $matches[1]; // Array berisi ['1001', '1002']

        if (empty($niks)) return;

        // 3. Cari User berdasarkan NIK yang punya telegram_chat_id
        $users = User::whereIn('nik', $niks)
                     ->whereNotNull('telegram_chat_id')
                     ->get();

        // 4. Kirim Pesan ke masing-masing teknisi
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
}