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
        //$text = 'Data Dapodik';
        $items = [];
        $next = FALSE;
        if($aksi){
            if($aksi == 'ptk'){
                $items = getPtk($user->sekolah_id, $semester->tahun_ajaran_id);
                $text = 'Data PTK';
                $next = 'rombongan_belajar';
            }
            if($aksi == 'rombongan_belajar'){
                $items = getRombonganBelajar($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id);
                $text = 'Data Rombongan Belajar';
                $next = 'peserta_didik_aktif';
            }
            if($aksi == 'peserta_didik_aktif'){
                $items = getPd(1, $user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id);
                $text = 'Data Peserta Didik Aktif';
                $next = 'peserta_didik_keluar';
            }
            if($aksi == 'peserta_didik_keluar'){
                $items = getPd(0, $user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id);
                $text = 'Data Peserta Didik Keluar';
                $next = 'anggota_rombel_pilihan';
            }
            if($aksi == 'anggota_rombel_pilihan'){
                $items = getAnggotaPilihan($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id);
                $text = 'Data Anggota Rombel Matpel Pilihan';
                $next = 'pembelajaran';
            }
            if($aksi == 'pembelajaran'){
                $items = getPembelajaran($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id);
                $text = 'Data Pembelajaran';
                $next = 'ekstrakurikuler';
            }
            if($aksi == 'ekstrakurikuler'){
                $items = getEkskul($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id);
                $text = 'Data Ekstrakurikuler';
                $next = 'anggota_ekskul';
            }
            if($aksi == 'anggota_ekskul'){
                $items = getAnggotaEkskul($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id);
                $text = 'Data Anggota Ekstrakurikuler';
                $next = 'dudi';
            }
            if($aksi == 'dudi'){
                $items = getDudi($user->sekolah_id, $semester->tahun_ajaran_id, $semester->semester_id);
                $text = 'Data DUDI';
                $next = FALSE;
            }
            $data_sync = [
                'sekolah_id' => $user->sekolah_id,
                'npsn' => $user->sekolah->npsn,
                'tahun_ajaran_id' => $semester->tahun_ajaran_id,
                'semester_id' => $semester->semester_id,
                'table' => $aksi,
                'json' => prepare_send(json_encode($items)),
            ];
            $data = kirimDapodik($data_sync, $text, $next, $user->sekolah->url_erapor);
            return $data;
        }
    }
}
