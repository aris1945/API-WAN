<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    
    public function getSchedule(Request $request)
    {
        // 1. Tangkap parameter dari Flutter
        $lok = strtolower($request->query('lok', ''));
        $dateToSearch = strtolower(trim($request->query('tanggal', '')));
        $shiftToSearch = strtolower(trim($request->query('shift', '')));

        if (!in_array($lok, ['ijk', 'mgo'])) {
            return response()->json(['status' => false, 'data' => null, 'message' => 'Lokasi tidak valid.']);
        }
        if (empty($dateToSearch)) {
            return response()->json(['status' => false, 'data' => null, 'message' => 'Tanggal tidak boleh kosong.']);
        }

        // 2. Terjemahkan Shift
        $translatedShiftToSearch = '';
        switch ($shiftToSearch) {
            case 'pagi': $translatedShiftToSearch = 'P'; break;
            case 'siang': $translatedShiftToSearch = 'S'; break;
            case 'malam': $translatedShiftToSearch = 'M'; break;
            case 'libur': $translatedShiftToSearch = 'L'; break;
            case 'bantek': $translatedShiftToSearch = 'B'; break;
            case 'cuti': $translatedShiftToSearch = 'C'; break;
        }

        // 3. Setup Akses ke Google Sheets
        try {
            $client = new \Google_Client();
            $client->setAuthConfig(base_path(env('FIREBASE_CREDENTIALS')));
            $client->addScope(\Google\Service\Sheets::SPREADSHEETS_READONLY);
            $service = new \Google\Service\Sheets($client);

            $spreadsheetId = '1UQL2lR41VKkamz0vhp5ygMPdkvMCIell35MP1__0_xI';
            $sheetName = strtoupper($lok);
            $response = $service->spreadsheets_values->get($spreadsheetId, $sheetName);
            $values = $response->getValues();
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => null, 'message' => 'Gagal akses spreadsheet: ' . $e->getMessage()]);
        }

        if (empty($values)) {
            return response()->json(['status' => false, 'data' => null, 'message' => 'Sheet kosong.']);
        }

        // 4. Cari Kolom Target
        $dateParts = explode(' ', $dateToSearch);
        $searchDay = $dateParts[0];
        $searchMonth = $dateParts[1] ?? '';

        $targetCol = -1;
        $lastMonth = '';

        for ($col = 4; $col <= 34; $col++) {
            $monthInCell = $values[2][$col] ?? '';
            $dayOfMonthInCell = $values[3][$col] ?? '';

            if (trim($monthInCell) !== '') $lastMonth = strtolower(trim($monthInCell));
            if (trim($dayOfMonthInCell) === $searchDay && $lastMonth === $searchMonth) {
                $targetCol = $col;
                break;
            }
        }

        if ($targetCol === -1) {
            return response()->json(['status' => false, 'data' => null, 'message' => "Tanggal {$dateToSearch} tidak ditemukan."]);
        }

        // 5. Ambil Data Jadwal (Pisahin per Shift)
        $shiftOrder = ['Pagi', 'Siang', 'Malam', 'Bantek', 'Libur', 'Cuti'];
        $staffWorking = array_fill_keys($shiftOrder, []);
        $helpdeskWorking = array_fill_keys($shiftOrder, []);

        for ($i = 4; $i < count($values); $i++) {
            if ($i === 26) continue; // Lewati baris 27 pemisah helpdesk

            $nik = $values[$i][2] ?? null;
            $name = $values[$i][1] ?? null;
            $scheduleEntry = $values[$i][$targetCol] ?? '';
            $rawShift = strtoupper(trim((string)$scheduleEntry));

            $translatedScheduleEntry = '';
            switch ($rawShift) {
                case 'P': $translatedScheduleEntry = 'Pagi'; break;
                case 'S': $translatedScheduleEntry = 'Siang'; break;
                case 'M': $translatedScheduleEntry = 'Malam'; break;
                case 'L': $translatedScheduleEntry = 'Libur'; break;
                case 'B': $translatedScheduleEntry = 'Bantek'; break;
                case 'C': $translatedScheduleEntry = 'Cuti'; break;
                default:  $translatedScheduleEntry = $scheduleEntry; break;
            }

            $isWorking = ($translatedScheduleEntry !== 'z' && $translatedScheduleEntry !== '');
            $isMatchingShift = true;

            if ($translatedShiftToSearch !== '') {
                if ($translatedShiftToSearch === 'z') $isWorking = true;
                $isMatchingShift = ($rawShift === $translatedShiftToSearch);
            }

            if ($nik && $name && $isWorking && $isMatchingShift) {
                $staffData = ['nik' => $nik, 'name' => $name, 'schedule' => $translatedScheduleEntry];
                if (!array_key_exists($translatedScheduleEntry, $staffWorking)) $translatedScheduleEntry = 'Cuti'; 

                if ($i < 26) {
                    $staffWorking[$translatedScheduleEntry][] = $staffData;
                } else {
                    $helpdeskWorking[$translatedScheduleEntry][] = $staffData;
                }
            }
        }

        // 6. Format Array Jadi JSON Bersih
        $staffResult = [];
        $helpdeskResult = [];

        foreach ($shiftOrder as $shift) {
            if (count($staffWorking[$shift]) > 0) {
                $staffResult[] = ['shift' => $shift, 'members' => $staffWorking[$shift]];
            }
            if (count($helpdeskWorking[$shift]) > 0) {
                $helpdeskResult[] = ['shift' => $shift, 'members' => $helpdeskWorking[$shift]];
            }
        }

        $filterInfo = $shiftToSearch ? ' shift ' . ucfirst($shiftToSearch) : '';
        $headerMessage = "Jadwal B2B " . strtoupper($lok) . " - {$searchDay} " . ucfirst($searchMonth) . $filterInfo;

        // KITA LEMPAR DATA OBJEK SEKARANG BOS!
        return response()->json([
            'status' => true,
            'data' => [
                'header' => $headerMessage,
                'teknisi' => $staffResult,
                'helpdesk' => $helpdeskResult
            ]
        ]);
    }

}