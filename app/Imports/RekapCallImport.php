<?php

namespace App\Imports;

use App\Models\DataLeads;
use App\Models\DataLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
// app/Imports/RekapCallImport.php

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RekapCallImport implements ToCollection, WithHeadingRow
{
    protected $currentRow = 0;
    protected $kcu;
    protected $missingRecords = [];


    private $tanggal_awal_call;
    private $tanggal_akhir_call;
    private $importedData = [];
    public function headingRow(): int
    {
        return 2; // Set the heading row to 3
    }

    public function __construct($kcu, $tanggal_awal_call, $tanggal_akhir_call)
    {
        $this->kcu = $kcu;
        $this->tanggal_awal_call = $tanggal_awal_call;
        $this->tanggal_akhir_call = $tanggal_akhir_call;
    }


    public function collection(Collection $rows)
    {                

        set_time_limit(120);
        $this->currentRow++;
        $foundCustomerNames = [];

        foreach ($rows as $row) {

          
           
            if (empty(array_filter($row->toArray()))) {
                // Baris kosong, skip pemrosesan
                continue;
            }

            
            $namaPerusahaan = $row['nama_nasabah'];

            // Ubah nama perusahaan menjadi huruf kecil
            $namaPerusahaanLowercase = strtolower($namaPerusahaan);
           
            
    
            $existingLead = DataLeads::where('cust_name',  $namaPerusahaanLowercase)
            ->where('kcu', $this->kcu) // Menambahkan kondisi untuk memastikan kcu sesuai
            ->where('tanggal_awal', $this->tanggal_awal_call)
            ->where('tanggal_akhir', $this->tanggal_akhir_call)
            ->first();


            if ($existingLead) {
                // Update the existing lead with data from the Excel file
                                // Pemeriksaan apakah jenis data dan status kosong atau tidak
                    if (!empty($row['status_follow_up'])) {
                        $validStatusValues = ['tidak terhubung', 'diskusi internal', 'berminat', 'tidak berminat', 'no. telp tidak valid', 'call again', 'closing'];

                        $lowercaseStatus = strtolower($row['status_follow_up']);
                        if (!in_array($lowercaseStatus, $validStatusValues)) {
                            // Jika tidak valid, buat pesan error
                            throw new \Exception("Invalid status value '{$row['status_follow_up']}'.");
                        }
                        
                    }

                    if (!empty($row['data_leads_referral_cabang'])) {
                        $validJenisDataValues = ['Data Leads', 'Referral'];
                        if (!in_array($row['data_leads_referral_cabang'], $validJenisDataValues)) {
                            // Jika tidak valid, buat pesan error
                            throw new \Exception("Invalid jenis data value '{$row['data_leads_referral_cabang']}'.");
                        }
                    }
                    
                
                    $tanggalFollowUp = null;

                    // Check if 'tanggal_follow_up' is not null in the Excel data
                    if (!is_null($row['tanggal_follow_up'])) {
                        // Check if 'tanggal_follow_up' is a string
                        if (is_string($row['tanggal_follow_up'])) {
                            // Jika 'tanggal_follow_up' berupa string, buat pesan kesalahan
                            throw new \Exception("Format Tanggal Salah '{$row['tanggal_follow_up']}'.");
                        }
                    
                        try {
                            $tanggalFollowUp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tanggal_follow_up']);
                        } catch (\Exception $e) {
                            // Jika tidak valid, buat pesan error
                            throw new \Exception("Format Tanggal Salah '{$row['tanggal_follow_up']}'.");
                        }
                    } else if (is_null($row['tanggal_follow_up'])){
                        throw new \Exception("Terdapat Tanggal Follow Up yang kosong");
                    }
                    

                        $updateData = [
                            'jenis_data' => $row['data_leads_referral_cabang'],
                            'status' => $row['status_follow_up'],
                            'tanggal_follow_up' => $tanggalFollowUp,
                            'data_tanggal' => $tanggalFollowUp,
                            
                        ];
        
                       
                        
                        // Check if 'pic_nasabah' is not null in the Excel data
                        if (!is_null($row['pic_nasabah'])) {
                            $updateData['nama_pic_kbb'] = $row['pic_nasabah'];
                        }
        
                        $tanggal_awal_rekap_call = new \DateTime($this->tanggal_awal_call);
                        $tanggal_akhir_rekap_call = new \DateTime($this->tanggal_akhir_call);


                        $tanggal_awal = new \DateTime($existingLead->tanggal_awal);
                        $tanggal_akhir = new \DateTime($existingLead->tanggal_akhir);
                       
                        
                      
                        
                        if ($tanggal_awal_rekap_call == $tanggal_awal && $tanggal_akhir_rekap_call == $tanggal_akhir) {
                           
                           
                            $existingLead->update($updateData);
                        } else {
                            // Jika tidak, buat data baru
                           
                        }            
 
                        
                            $logData = [
                                'id_data_leads' => $existingLead->id, // Assuming 'id' is the primary key of DataLeads
                                'jenis_data' => $row['data_leads_referral_cabang'],
                                'status' => $row['status_follow_up'],
                                'tanggal_follow_up' => $tanggalFollowUp,
                                'kcu' => $this->kcu, 
                                'data_tanggal' => $tanggalFollowUp,
                            ];
                            $existingLog = DataLog::where('id_data_leads', $existingLead->id)
                            ->where('tanggal_follow_up', $tanggalFollowUp)
                            ->where('status', $row['status_follow_up'])
                            ->first();


                            // Check if there is an existing log entry for the same id_data_leads

                            // Check if 'pic_nasabah' is not null in the Excel data
                            if (!is_null($row['pic_nasabah'])) {
                                $logData['nama_pic_kbb'] = $row['pic_nasabah'];
                            }
                    
                            if (!$existingLog) {
                                DataLog::create($logData);
                            }
            }
            else {
                
                if ($row['data_leads_referral_cabang'] === 'Data Leads') {

                    $tanggalFollowUp = null;

                    // Check if 'tanggal_follow_up' is not null in the Excel data
                    if (!is_null($row['tanggal_follow_up'])) {
                        // Check if 'tanggal_follow_up' is a string
                        if (is_string($row['tanggal_follow_up'])) {
                            // Jika 'tanggal_follow_up' berupa string, buat pesan kesalahan
                            throw new \Exception("Format Tanggal Salah '{$row['tanggal_follow_up']}'.");
                        }
                    
                        try {
                            $tanggalFollowUp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tanggal_follow_up']);
                        } catch (\Exception $e) {
                            // Jika tidak valid, buat pesan error
                            throw new \Exception("Format Tanggal Salah '{$row['tanggal_follow_up']}'.");
                        }
                    } else if (is_null($row['tanggal_follow_up'])){
                        throw new \Exception("Terdapat Tanggal Follow Up yang kosong");
                    }     

                    $tanggal_awal_rekap_call = new \DateTime($this->tanggal_awal_call);
                    $tanggal_akhir_rekap_call = new \DateTime($this->tanggal_akhir_call);

                    

                $lastNo = DataLeads::max('no');
                // Menambahkan 1 ke nomor terakhir
                $newNo = $lastNo + 1;
                // Simpan data baru di tabel DataLeads untuk jenis data referral
                DataLeads::create([
                    'no' => $newNo,
                    'cust_name' => $row['nama_nasabah'],
                    'jenis_data' => $row['data_leads_referral_cabang'],
                    'status' => $row['status_follow_up'],
                    'tanggal_follow_up' => $tanggalFollowUp,
                    'tanggal_awal' => $tanggal_awal_rekap_call,
                    'tanggal_akhir' => $tanggal_akhir_rekap_call,
                    'kcu' => $this->kcu, 
                    'data_tanggal' => $tanggalFollowUp,
                    'nama_pic_kbb' => $row['pic_nasabah'],
                    // Tambahkan kolom lain sesuai kebutuhan
                ]);

                $newLeadId = DataLeads::where('cust_name', $row['nama_nasabah'])->first()->id;

                // Set data untuk DataLog
                $logData = [
                    'id_data_leads' => $newLeadId,
                    'jenis_data' => 'Referral', // Sesuaikan dengan jenis data yang dibuat di DataLeads
                    'status' => $row['status_follow_up'],
                    'tanggal_follow_up' => $tanggalFollowUp,
                    'kcu' => $this->kcu, 
                    'data_tanggal' => $tanggalFollowUp,
                ];

                DataLog::create($logData);

            }

                // Jika customer name tidak ditemukan, buat data baru
                else if ($row['data_leads_referral_cabang'] === 'Referral') {

                    $tanggalFollowUp = null;

                    // Check if 'tanggal_follow_up' is not null in the Excel data
                    if (!is_null($row['tanggal_follow_up'])) {
                        // Check if 'tanggal_follow_up' is a string
                        if (is_string($row['tanggal_follow_up'])) {
                            // Jika 'tanggal_follow_up' berupa string, buat pesan kesalahan
                            throw new \Exception("Format Tanggal Salah '{$row['tanggal_follow_up']}'.");
                        }
                    
                        try {
                            $tanggalFollowUp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tanggal_follow_up']);
                        } catch (\Exception $e) {
                            // Jika tidak valid, buat pesan error
                            throw new \Exception("Format Tanggal Salah '{$row['tanggal_follow_up']}'.");
                        }
                    } else if (is_null($row['tanggal_follow_up'])){
                        throw new \Exception("Terdapat Tanggal Follow Up yang kosong");
                    }     

                    $tanggal_awal_rekap_call = new \DateTime($this->tanggal_awal_call);
                    $tanggal_akhir_rekap_call = new \DateTime($this->tanggal_akhir_call);
                    $lastNo = DataLeads::max('no');
                    // Menambahkan 1 ke nomor terakhir
                    $newNo = $lastNo + 1;
                    // Simpan data baru di tabel DataLeads untuk jenis data referral
                    DataLeads::create([
                        'no' => $newNo,
                        'cust_name' => $row['nama_nasabah'],
                        'jenis_data' => $row['data_leads_referral_cabang'],
                        'status' => $row['status_follow_up'],
                        'tanggal_follow_up' => $tanggalFollowUp,
                        'tanggal_awal' => $tanggal_awal_rekap_call,
                        'tanggal_akhir' => $tanggal_akhir_rekap_call,
                        'kcu' => $this->kcu, 
                        'data_tanggal' => $tanggalFollowUp,
                        'nama_pic_kbb' => $row['pic_nasabah'],
                        // Tambahkan kolom lain sesuai kebutuhan
                    ]);
    
                    $newLeadId = DataLeads::where('cust_name', $row['nama_nasabah'])->first()->id;
    
                    // Set data untuk DataLog
                    $logData = [
                        'id_data_leads' => $newLeadId,
                        'jenis_data' => 'Referral', // Sesuaikan dengan jenis data yang dibuat di DataLeads
                        'status' => $row['status_follow_up'],
                        'tanggal_follow_up' => $tanggalFollowUp,
                        'kcu' => $this->kcu, 
                        'data_tanggal' => $tanggalFollowUp,
                    ];
    
                    DataLog::create($logData);
    
                }
            }


        }
        $existingLeads = DataLeads::whereBetween('tanggal_awal', [$this->tanggal_awal_call, $this->tanggal_akhir_call])
        ->whereBetween('tanggal_akhir', [$this->tanggal_awal_call, $this->tanggal_akhir_call])
        ->get();


        
        $nonEmptyRows = $rows->filter(function ($row) {
            return !empty(array_filter($row->toArray()));
        });
    
        // Jika ada data yang tidak kosong, simpan ke $this->importedData
        if (!$nonEmptyRows->isEmpty()) {
            $this->importedData = $nonEmptyRows->all();
        }
        
       
        $importedDataNames = collect($this->importedData)->pluck('nama_nasabah')->toArray();

      // Memeriksa apakah ada nama yang duplikat
$duplicateNames = array_unique(array_diff_assoc($importedDataNames, array_unique($importedDataNames)));

if (!empty($duplicateNames)) {
    // Ada nama yang sama, beri pesan kesalahan
    $errorMessage = 'Dalam file Nama berikut memiliki duplikat: ' . implode(', ', $duplicateNames);
    throw new \Exception($errorMessage);

}


        $existingRecordsWithoutImport = DataLeads::where('tanggal_awal', $this->tanggal_awal_call)
        ->where('tanggal_akhir', $this->tanggal_akhir_call)
        ->whereNotIn('cust_name', $importedDataNames)
        ->get();

   
      
        // foreach ($existingRecordsWithoutImport as $existingRecord) {
        //     // Update the status and data_tanggal in existing records
           

        //     $updateDataRecord = [
        //         'status' => 'Belum Dikerjakan', // Set the desired updated status
        //         'data_tanggal' => $this->tanggal_akhir_call, // Set the desired updated data_tanggal
        //         'tanggal_follow_up' => null,
        //         'nama_pic_kbb' => null,
        //     ];
           
            
        //     $existingRecord->update($updateDataRecord);
            
        //     $logDataRecord = [
        //         'id_data_leads' =>  $existingRecord->id, // Assuming 'id' is the primary key of DataLeads
        //         'jenis_data' => 'Data Leads',
        //         'status' => 'Belum Dikerjakan',
                
        //         'kcu' => $this->kcu, 
        //         'data_tanggal' => $this->tanggal_akhir_call,
        //     ];
           


        //     $existingLog = DataLog::where('id_data_leads',$existingRecord->id)
        //                     ->where('data_tanggal', $this->tanggal_akhir_call)
        //                     ->where('status', 'Belum Dikerjakan')
        //                     ->first();


        //                     // Check if there is an existing log entry for the same id_data_leads

        //                     // Check if 'pic_nasabah' is not null in the Excel data
                         
                    
        //                     if (!$existingLog) {
        //                         DataLog::create($logDataRecord);
        //                     }
            
        // }
        
}
    // public function chunkSize(): int
    // {
    //     return 180; // Adjust the chunk size based on your needs
    // }

    public function getImportedData()
    {
        return $this->importedData;
    }
}
