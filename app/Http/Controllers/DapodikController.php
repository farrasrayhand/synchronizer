<?php

namespace App\Http\Controllers;

use App\Models\AnggotaRombel;
use App\Models\JenisPtk;
use App\Models\Pengguna;
use App\Models\PesertaDidik;
use App\Models\Ptk;
use App\Models\PtkTerdaftar;
use App\Models\RegistrasiPesertaDidik;
use App\Models\RombonganBelajar;
use App\Models\Sekolah;
use App\Models\Semester;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\Pembelajaran;
use App\Models\KelasEkskul;
use App\Models\Dudi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Storage;
use Artisan;

class DapodikController extends Controller
{
    public function index()
    {
        $sekolah = [];
        $error = null;
        $user = auth()->user();
        $jumlah = 0;
        $table_sync = [];

        try {
            $sekolah = $this->get_sekolah();
            $user->sekolah = Sekolah::on('dapodik')->with(['pengguna' => function($query){
                $query->whereHas('role', function($query){
                    $query->where('peran_id', 10);
                });
            }])->find($user->sekolah_id);
            $user->erapor = Sekolah::find($user->sekolah_id);
            $user->semester = Semester::where('periode_aktif', 1)->first();

            $table_sync = [
                [
                    'data' => 'PTK',
                    'aksi' => 'ptk',
                    'count' => Ptk::where(function($query) use ($user){
                        $query->where('soft_delete', 0);
                        $query->whereHas('ptk_terdaftar', function($query) use ($user){
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->where('soft_delete', 0);
                            $query->whereHas('tahun_ajaran', function($query){
                                $query->where('soft_delete', 0);
                                $query->where('periode_aktif', 1);
                            });
                        });
                    })->count(),
                ],
                [
                    'data' => 'Rombongan Belajar',
                    'aksi' => 'rombel',
                    'count' => RombonganBelajar::where(function($query) use ($user){
                        $query->whereHas('semester', function($query){
                            $query->where('soft_delete', 0);
                            $query->where('periode_aktif', 1);
                        });
                        $query->where('sekolah_id', $user->sekolah_id);
                        $query->whereIn('jenis_rombel', [1, 8, 9, 16]);
                        $query->where('soft_delete', 0);
                    })->count(),
                ],
                [
                    'data' => 'Peserta Didik Aktif',
                    'aksi' => 'pd_aktif',
                    'count' => PesertaDidik::where(function($query) use ($user){
                        $query->whereHas('registrasi_peserta_didik', function($query) use ($user){
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->where('soft_delete', 0);
                            $query->where(function($q){
                                $q->whereNull('jenis_keluar_id')
                                  ->orWhereNotIn('jenis_keluar_id', ['1', '2', '3', '4', '5', '6', '7', '8', '9']);
                            });
                        });
                        $query->whereHas('anggota_rombel', function($query) use ($user){
                            $query->where('soft_delete', 0);
                            $query->whereHas('rombongan_belajar', function($query) use ($user){
                                $query->where('soft_delete', 0);
                                $query->where('sekolah_id', $user->sekolah_id);
                                $query->whereHas('semester', function($query){
                                    $query->where('periode_aktif', 1);
                                });
                                $query->whereIn('jenis_rombel', [1, 8, 9, 16]);
                            });
                        });
                    })->count(),
                ],
                [
                    'data' => 'Peserta Didik Keluar',
                    'aksi' => 'pd_keluar',
                    'count' => PesertaDidik::where(function($query) use ($user){
                        $query->whereHas('registrasi_peserta_didik', function($query) use ($user){
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->where('soft_delete', 0);
                            $query->whereIn('jenis_keluar_id', ['1', '2', '3', '4', '5', '6', '7', '8', '9']);
                            $query->whereBetween('tanggal_keluar', $this->periode_aktif());
                        });
                        $query->whereHas('anggota_rombel', function($query) use ($user){
                            $query->where('soft_delete', 0);
                            $query->whereHas('rombongan_belajar', function($query) use ($user){
                                $query->where('soft_delete', 0);
                                $query->where('sekolah_id', $user->sekolah_id);
                                $query->whereHas('semester', function($query){
                                    $query->where('periode_aktif', 1);
                                });
                                $query->whereIn('jenis_rombel', [1, 8, 9, 16]);
                            });
                        });
                    })->count(),
                ],
                [
                    'data' => 'Anggota Rombel Matpel Pilihan',
                    'aksi' => 'anggota_matpil',
                    'count' => AnggotaRombel::where(function($query) use ($user){
                        $query->whereHas('rombongan_belajar', function($query) use ($user){
                            $query->whereHas('semester', function($query){
                                $query->where('periode_aktif', 1);
                            });
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->where('soft_delete', 0);
                            $query->where('jenis_rombel', 16);
                        });
                        $query->whereHas('peserta_didik', function($query) use ($user){
                            $query->where('peserta_didik.soft_delete', 0);
                            $query->whereHas('registrasi_peserta_didik', function($q) use ($user){
                                $q->where('sekolah_id', $user->sekolah_id);
                                $q->where('soft_delete', 0);
                                $q->whereNull('jenis_keluar_id');
                            });
                        });
                        $query->where('soft_delete', 0);
                    })->count(),
                ],
                [
                    'data' => 'Pembelajaran',
                    'aksi' => 'pembelajaran',
                    'count' => Pembelajaran::where(function($query) use ($user){
                        $query->where('soft_delete', 0);
                        $query->whereHas('rombongan_belajar', function($query) use ($user){
                            $query->where('soft_delete', 0);
                            $query->whereHas('semester', function($query){
                                $query->where('periode_aktif', 1);
                            });
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->whereIn('jenis_rombel', [1, 8, 9, 16]);
                        });
                        $query->whereHas('ptk_terdaftar', function($query) use ($user){
                            $query->where('ptk.soft_delete', 0);
                            $query->where('ptk_terdaftar.soft_delete', 0);
                            $query->where('sekolah_id', $user->sekolah_id);
                        });
                    })->count(),
                ],
                [
                    'data' => 'Ekstrakurikuler',
                    'aksi' => 'ekskul',
                    'count' => KelasEkskul::where(function($query) use ($user){
                        $query->where('soft_delete', 0);
                        $query->whereHas('rombongan_belajar', function($query) use ($user){
                            $query->where('soft_delete', 0);
                            $query->whereHas('semester', function($query){
                                $query->where('periode_aktif', 1);
                            });
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->where('jenis_rombel', 51);
                        });
                    })->count(),
                ],
                [
                    'data' => 'Anggota Ekstrakurikuler',
                    'aksi' => 'anggota_ekskul',
                    'count' => AnggotaRombel::where(function($query) use ($user){
                        $query->where('soft_delete', 0);
                        $query->whereHas('rombongan_belajar', function($query) use ($user){
                            $query->whereHas('kelas_ekskul', function($query){
                                $query->where('soft_delete', 0);    
                            });
                            $query->where('soft_delete', 0);
                            $query->whereHas('semester', function($query){
                                $query->where('periode_aktif', 1);
                            });
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->where('jenis_rombel', 51);
                        });
                        $query->whereHas('pd', function($query) use ($user){
                            $query->where('soft_delete', 0);
                            $query->whereHas('registrasi_peserta_didik', function($query) use ($user){
                                $query->where('sekolah_id', $user->sekolah_id);
                                $query->where('soft_delete', 0);
                                $query->whereNull('jenis_keluar_id');
                            });
                        });
                    })->count(),
                ],
                [
                    'data' => 'Relasi Dunia Usaha & Industri',
                    'aksi' => 'dudi',
                    'count' => Dudi::where(function($query) use ($user){
                        $query->where('soft_delete', 0);
                        $query->whereHas('mou', function($query) use ($user){
                            $query->where('soft_delete', 0);
                            $query->where('sekolah_id', $user->sekolah_id);
                        });
                    })->count(),
                ],
            ];

            foreach($table_sync as $sync){
                $jumlah += $sync['count'];
            }
        } catch (\Throwable $th) {
            $error = $th->getMessage();
        }

        $data = [
            'versiApp' => 'v2.0.0',
            'sekolah' => $sekolah,
            'user' => $user,
            'jumlah' => $jumlah,
            'table_sync' => $table_sync,
            'error' => $error,
            'cek_sekolah' => ($user->sekolah_id && $user->erapor && $user->erapor->url_erapor) ? cekSekolah([
                'sekolah_id' => $user->sekolah_id,
                'npsn' => $user->sekolah->npsn,
                'table' => 'cek_sekolah',
                'json' => prepare_send(json_encode(['sekolah_id' => $user->sekolah_id])),
            ], $user->erapor->url_erapor) : NULL,
        ];
        return response()->json($data);
    }

    private function get_sekolah()
    {
        return Sekolah::on('dapodik')->with([
            'wilayah.parrentRecursive',
        ])->get();
    }
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        Auth::guard('web')->logout();
        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
    
    private function periode_aktif(){
        $find = Semester::where('periode_aktif', 1)->first();
        $data = [$find->tanggal_mulai, $find->tanggal_selesai];
        return $data;
    }
    public function sekolah(Request $request)
    {
        $user = auth()->user();
        request()->validate(
            [
                'sekolah_id' => 'required',
            ],
            [
                'sekolah_id.required' => 'Sekolah tidak boleh kosong',
            ]
        );
        $dapodik = Sekolah::on('dapodik')->find(request()->sekolah_id);
        if ($dapodik) {
            $sekolah = Sekolah::updateOrCreate(
                [
                    'sekolah_id' => $request->sekolah_id,
                ],
                [
                    'npsn' => $dapodik->npsn,
                    'nama' => $dapodik->nama,
                ]
            );
            $user->sekolah_id = request()->sekolah_id;
            $user->pengguna_id = (request()->pengguna_id) ? request()->pengguna_id : $this->get_pengguna();
            $user->save();
        }
        $data = [
            'sekolah' => $sekolah,
            'npsn' => $dapodik->npsn,
        ];
        return response()->json($data);
    }
    public function reset(){
        Artisan::call('migrate:refresh --seed');
        return redirect(url('/'));
    }
    public function kirim_data(){
        $text = 'Data Dapodik';
        $sekolah_id = request()->sekolah_id;
        $items = [];
        $next = FALSE;
        if(request()->aksi){
            $data_sync = [
                'sekolah_id' => $sekolah_id,
                'npsn' => request()->npsn,
                'tahun_ajaran_id' => request()->tahun_ajaran_id,
                'semester_id' => request()->semester_id,
                'table' => request()->aksi,
            ];
            if(request()->aksi == 'url'){
                $update = Sekolah::where('sekolah_id', request()->sekolah_id)->update(['url_erapor' => request()->url_erapor]);
                if($update){
                    $data = [
                        'icon' => 'tabler-check',
                        'color' => 'success',
                        'title' => 'Berhasil!',
                        'text' => 'URL e-Rapor SMK v8 berhasil disimpan',
                    ];    
                } else {
                    $data = [
                        'color' => 'error',
                        'icon' => 'tabler-xbox-x',
                        'title' => 'Gagal!',
                        'text' => 'Sekolah tidak ditemukan',
                    ];
                }
                return response()->json($data);
            }
            if(request()->aksi == 'ptk'){
                $data_sync['text'] = request()->count.' Data PTK';
                $data_sync['next'] = 'rombel';
                $data = getPtk(request()->sekolah_id, request()->tahun_ajaran_id, $data_sync);
            }
            if(request()->aksi == 'rombel'){
                $data_sync['text'] = request()->count.' Data Rombongan Belajar';
                $data_sync['next'] = 'pd_aktif';
                $data = getRombonganBelajar(request()->sekolah_id, request()->tahun_ajaran_id, request()->semester_id, $data_sync);
            }
            if(request()->aksi == 'pd_aktif'){
                $data_sync['text'] = request()->count.' Data Peserta Didik Aktif';
                $data_sync['next'] = 'pd_keluar';
                $data = getPd(1, request()->sekolah_id, request()->tahun_ajaran_id, request()->semester_id, $data_sync);
            }
            if(request()->aksi == 'pd_keluar'){
                $data_sync['text'] = request()->count.' Data Peserta Didik Keluar';
                $data_sync['next'] = 'anggota_matpil';
                $data = getPd(0, request()->sekolah_id, request()->tahun_ajaran_id, request()->semester_id, $data_sync);
            }
            if(request()->aksi == 'anggota_matpil'){
                $data_sync['text'] = request()->count.' Data Anggota Rombel Matpel Pilihan';
                $data_sync['next'] = 'pembelajaran';
                $data = getAnggotaPilihan(request()->sekolah_id, request()->tahun_ajaran_id, request()->semester_id, $data_sync);
            }
            if(request()->aksi == 'pembelajaran'){
                $data_sync['text'] = request()->count.' Data Pembelajaran';
                $data_sync['next'] = 'ekskul';
                $data = getPembelajaran(request()->sekolah_id, request()->tahun_ajaran_id, request()->semester_id, $data_sync);
            }
            if(request()->aksi == 'ekskul'){
                $data_sync['text'] = request()->count.' Data Ekstrakurikuler';
                $data_sync['next'] = 'anggota_ekskul';
                $data = getEkskul(request()->sekolah_id, request()->tahun_ajaran_id, request()->semester_id, $data_sync);
            }
            if(request()->aksi == 'anggota_ekskul'){
                $data_sync['text'] = request()->count.' Data Anggota Ekstrakurikuler';
                $data_sync['next'] = 'dudi';
                $data = getAnggotaEkskul(request()->sekolah_id, request()->tahun_ajaran_id, request()->semester_id, $data_sync);
            }
            if(request()->aksi == 'dudi'){
                $data_sync['text'] = request()->count.' Data DUDI';
                $data_sync['next'] = FALSE;
                $data = getDudi(request()->sekolah_id, request()->tahun_ajaran_id, request()->semester_id, $data_sync);
            }
        }
        //$data = kirimDapodik($data_sync, $text, $data_sync['next']);
        return response()->json($data);
    }
    public function register(){
        return response()->json(registrasi(request()->all()));
    }
}
