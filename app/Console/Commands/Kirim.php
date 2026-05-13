<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Semester;
use App\Models\User;

class Kirim extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kirim:dapodik {satuan?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim Data Dapodik ke e-Rapor SMK v8';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::with('sekolah')->first();
        if($user){
            if($user->sekolah_id){
                if($user->sekolah->url_erapor){
                    $list_data = [
                        'semua_data',
                        'ptk', 
                        'rombongan_belajar', 
                        'peserta_didik_aktif', 
                        'peserta_didik_keluar', 
                        'anggota_rombel_pilihan',
                        'pembelajaran', 
                        'ekstrakurikuler', 
                        'anggota_ekskul',
                        'dudi'
                    ];
                    if($this->argument('satuan')){
                    } else {
                        $satuan = $this->choice(
                            'Pilih data untuk di kirim ke e-Rapor SMK v8!',
                            $list_data
                        );
                        $this->info($satuan);
                    }
                    $data = collect($list_data);
                    if($satuan != 'semua_data'){
                        if($data->contains($satuan)){
                            $dapodik = $this->kirim_data($satuan, $user);
                            $this->info($dapodik['notif']['text']);
                        } else {
                            $this->error('Permintaan '.$satuan.' tidak ditemukan');
                        }
                    } else {
                        $data->forget(0);
                        foreach($data as $d){
                            $dapodik = $this->kirim_data($d, $user);
                            $this->info($dapodik['notif']['text']);
                        }
                    }
                } else {
                    $this->error('URL e-Rapor SMK v8 belum di input!');
                }
            } else {
                $this->error('Sekolah tidak ditemukan!');
            }
        } else {
            $this->error('Aplikasi belum diaktifkan!');
        }
    }
    private function kirim_data($aksi, $user){
        $semester = Semester::where('periode_aktif', 1)->first();
        
        $data_sync = [
            'sekolah_id' => $user->sekolah_id,
            'npsn' => $user->sekolah->npsn,
            'tahun_ajaran_id' => $semester->tahun_ajaran_id,
            'semester_id' => $semester->semester_id,
            'table' => $aksi,
            'url_erapor' => $user->sekolah->url_erapor,
        ];
        
        $data = null;
        if($aksi){
            if($aksi == 'ptk'){
                $data_sync['text'] = 'Data PTK';
                $data_sync['next'] = 'rombongan_belajar';
                $data = getPtk($user->sekolah_id, $semester->tahun_ajaran_id, $data_sync);
            }
            if($aksi == 'rombongan_belajar'){
                $data_sync['text'] = 'Data Rombongan Belajar';
                $data_sync['next'] = 'peserta_didik_aktif';
                $data = getRombonganBelajar($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id, $data_sync);
            }
            if($aksi == 'peserta_didik_aktif'){
                $data_sync['text'] = 'Data Peserta Didik Aktif';
                $data_sync['next'] = 'peserta_didik_keluar';
                $data = getPd(1, $user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id, $data_sync);
            }
            if($aksi == 'peserta_didik_keluar'){
                $data_sync['text'] = 'Data Peserta Didik Keluar';
                $data_sync['next'] = 'anggota_rombel_pilihan';
                $data = getPd(0, $user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id, $data_sync);
            }
            if($aksi == 'anggota_rombel_pilihan'){
                $data_sync['text'] = 'Data Anggota Rombel Matpel Pilihan';
                $data_sync['next'] = 'pembelajaran';
                $data = getAnggotaPilihan($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id, $data_sync);
            }
            if($aksi == 'pembelajaran'){
                $data_sync['text'] = 'Data Pembelajaran';
                $data_sync['next'] = 'ekstrakurikuler';
                $data = getPembelajaran($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id, $data_sync);
            }
            if($aksi == 'ekstrakurikuler'){
                $data_sync['text'] = 'Data Ekstrakurikuler';
                $data_sync['next'] = 'anggota_ekskul';
                $data = getEkskul($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id, $data_sync);
            }
            if($aksi == 'anggota_ekskul'){
                $data_sync['text'] = 'Data Anggota Ekstrakurikuler';
                $data_sync['next'] = 'dudi';
                $data = getAnggotaEkskul($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id, $data_sync);
            }
            if($aksi == 'dudi'){
                $data_sync['text'] = 'Data DUDI';
                $data_sync['next'] = FALSE;
                $data = getDudi($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id, $data_sync);
            }
            return $data;
        }
    }
}
