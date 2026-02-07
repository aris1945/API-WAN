<?php

namespace App\Imports;

use App\Models\Site;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow; // Ganti WithHeadingRow jadi WithStartRow

use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class SitesImport implements ToModel, WithStartRow, WithChunkReading, WithBatchInserts{
    /**
     * Tentukan baris berapa data mulai dibaca.
     * Karena baris 1 adalah Judul/Header, kita mulai dari baris 2.
     */
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        // Validasi: Jika kolom B kosong, jangan diimport (skip baris ini)
        if (!isset($row[1])) {
            return null;
        }

        return new Site([
            // KITA PAKAI NOMOR INDEX DI SINI
            
            // Kolom A ($row[0]) kita abaikan (karena isinya cuma angka 'f')
            'site_id'    => $row[1], // Kolom B -> Site ID
            'site_name'  => $row[2], // Kolom C -> Site Name
            'sto'        => $row[3], // Kolom D -> STO
            'pic_wan'    => $row[4], // Kolom E -> PIC WAN
            'metro'      => $row[5], // Kolom F -> Metro
            'port_metro' => $row[6], // Kolom G -> Port Metro
            
            // Kolom H ($row[7]) -> VLAN 2G
            // Cek jika isinya "n/a", maka simpan NULL, jika tidak simpan isinya
            'vlan_2g'    => ($row[7] == 'n/a') ? null : $row[7], 
            
            'vlan_3g'    => ($row[8] == 'n/a') ? null : $row[8], // Kolom I -> VLAN 3G
            'vlan_4g'    => ($row[9] == 'n/a') ? null : $row[9], // Kolom J -> VLAN 4G
            'vlan_oam'   => ($row[10] == 'n/a') ? null : $row[10], // Kolom K -> VLAN OAM
            'vlan_recti' => ($row[11] == 'n/a') ? null : $row[11], // Kolom L -> VLAN RECTI
            
            'olt'        => $row[12], // Kolom M -> OLT
            'port_olt'   => $row[13], // Kolom N -> Port OLT
            'sn_ont'     => $row[14], // Kolom O -> SN ONT
            'ip_olt'     => $row[15], // Kolom P -> IP OLT

            'vlan_mgt'   => ($row[16] == 'n/a') ? null : $row[16], // Kolom Q -> VLAN MGT

            'ip_ont'     => $row[17], // Kolom R -> IP ONT

            'mac_bawah'  => $row[18], // Kolom S -> MAC BAWAH
            'lat'        => $row[19], // Kolom T -> LAT
            'long'       => $row[20], // Kolom U -> LONG

            'odp'        => $row[21], // Kolom V -> ODP
            'latlong_odp' => $row[22],
            'odc'        => $row[23], // Kolom W -> ODC
            'latlong_odc' => $row[24],

            'datek_ea'   => $row[25], // Kolom X -> DATEK EA
            'datek_oa'   => $row[26]  // Kolom Y -> DATEK OA
        ]);
    }

public function chunkSize(): int
    {
        return 1000;
    }

    // 4. Tambahkan method batchSize (Simpan ke DB per 1000 baris)
    public function batchSize(): int
    {
        return 1000;
    }
}