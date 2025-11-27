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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Artisan;

class DapodikController extends Controller
{
    private function get_pengguna(){
        $data = Pengguna::whereHas('role', function($query){
            $query->where('peran_id', 10);
            $query->where('sekolah_id', request()->sekolah_id);
        })->first();
        return $data?->pengguna_id;
    }
    private function get_sekolah()
    {
        return Sekolah::on('dapodik')->withWhereHas('pengguna', function ($query) {
            $query->whereHas('role', function($query){
                $query->where('peran_id', 10);
            });
        })->get();
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
    public function index()
    {
        $sekolah = [];
        $error = null;
        $user = auth()->user();
        try {
            $sekolah = $this->get_sekolah();
            $user->sekolah = Sekolah::on('dapodik')->find($user->sekolah_id);
        } catch (\Throwable $th) {
            //sdd($th->getMessage());
            $error = Str::of($th->getMessage())->contains('fe_sendauth');
        }
        $jumlah = 0;
        if($error){
            $table_sync = [];
        } else {
            $table_sync = [
                [
                    'data' => 'PTK',
                    'aksi' => 'ptk',
                    'count' => Ptk::where(function($query) use ($user){
                        $query->where('soft_delete', 0);
                        $query->whereHas('ptk_terdaftar', function($query) use ($user){
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->whereNull('jenis_keluar_id');
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
                        $query->whereHas('wali_kelas', function($query) use ($user){
                            $query->where('soft_delete', 0);
                            $query->whereHas('ptk_terdaftar', function($query) use ($user){
                                $query->where('sekolah_id', $user->sekolah_id);
                                $query->whereNull('jenis_keluar_id');
                                $query->where('soft_delete', 0);
                                $query->whereHas('tahun_ajaran', function($query){
                                    $query->where('soft_delete', 0);
                                    $query->where('periode_aktif', 1);
                                });
                            });
                        });
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
                            $query->whereNotIn('jenis_keluar_id', ['2', '3', '4', '5', '6', '7', '8', '9']);
                            $query->where('soft_delete', 0);
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->orWhereNull('jenis_keluar_id');
                            $query->where('soft_delete', 0);
                            $query->where('sekolah_id', $user->sekolah_id);
                        });
                        $query->whereHas('anggota_rombel', function($query) use ($user){
                            $query->where('soft_delete', 0);
                            $query->whereHas('rombongan_belajar', function($query) use ($user){
                                $query->where('soft_delete', 0);
                                $query->where('sekolah_id', $user->sekolah_id);
                                $query->whereHas('semester', function($query){
                                    $query->where('periode_aktif', 1);
                                });
                                //$query->whereIn('jenis_rombel', [1, 8, 9]);
                                $query->where('jenis_rombel', 1);
                                $query->whereHas('wali_kelas', function($query) use ($user){
                                    $query->where('soft_delete', 0);
                                    $query->whereHas('ptk_terdaftar', function($query) use ($user){
                                        $query->where('sekolah_id', $user->sekolah_id);
                                        $query->whereHas('tahun_ajaran', function($query){
                                            $query->where('periode_aktif', 1);
                                        });
                                        $query->whereNull('jenis_keluar_id');
                                        $query->where('soft_delete', 0);
                                    });
                                });
                            });
                        });
                    })->count(),
                ],
                [
                    'data' => 'Peserta Didik Keluar',
                    'aksi' => 'pd_keluar',
                    'count' => PesertaDidik::where(function($query) use ($user){
                        $query->whereHas('registrasi_peserta_didik', function($query) use ($user){
                            $query->whereIn('jenis_keluar_id', ['2', '3', '4', '5', '6', '7', '8', '9']);
                            $query->whereBetween('tanggal_keluar', $this->periode_aktif());
                            $query->where('soft_delete', 0);
                            $query->where('sekolah_id', $user->sekolah_id);
                        });
                        $query->whereHas('anggota_rombel', function($query) use ($user){
                            $query->where('soft_delete', 0);
                            $query->whereHas('rombongan_belajar', function($query) use ($user){
                                $query->where('soft_delete', 0);
                                $query->where('sekolah_id', $user->sekolah_id);
                                $query->whereHas('semester', function($query){
                                    $query->where('periode_aktif', 1);
                                });
                                $query->where('jenis_rombel', 1);
                                $query->whereHas('wali_kelas', function($query) use ($user){
                                    $query->where('soft_delete', 0);
                                    $query->whereHas('ptk_terdaftar', function($query) use ($user){
                                        $query->where('sekolah_id', $user->sekolah_id);
                                        $query->whereHas('tahun_ajaran', function($query){
                                            $query->where('periode_aktif', 1);
                                        });
                                        $query->whereNull('jenis_keluar_id');
                                        $query->where('soft_delete', 0);
                                    });
                                });
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
                            $query->whereHas('wali_kelas', function($query) use ($user){
                                $query->where('soft_delete', 0);
                                $query->whereHas('ptk_terdaftar', function($query) use ($user){
                                    $query->where('sekolah_id', $user->sekolah_id);
                                    $query->whereHas('tahun_ajaran', function($query){
                                        $query->where('periode_aktif', 1);
                                    });
                                    $query->whereNull('jenis_keluar_id');
                                    $query->where('soft_delete', 0);
                                });
                            });
                        });
                        $query->whereHas('peserta_didik', function($query){
                            $query->where('peserta_didik.soft_delete', 0);
                            $query->where('registrasi_peserta_didik.soft_delete', 0);
                            $query->whereNull('jenis_keluar_id');
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
                            $query->whereHas('wali_kelas', function($query) use ($user){
                                $query->where('soft_delete', 0);
                                $query->whereHas('ptk_terdaftar', function($query) use ($user){
                                    $query->where('sekolah_id', $user->sekolah_id);
                                    $query->whereHas('tahun_ajaran', function($query){
                                        $query->where('periode_aktif', 1);
                                    });
                                    $query->whereNull('jenis_keluar_id');
                                    $query->where('soft_delete', 0);
                                });
                            });
                        });
                        $query->whereHas('ptk_terdaftar', function($query) use ($user){
                            $query->where('ptk.soft_delete', 0);
                            $query->where('ptk_terdaftar.soft_delete', 0);
                            $query->whereNull('jenis_keluar_id');
                            $query->where('sekolah_id', $user->sekolah_id);
                            $query->whereHas('ptk_terdaftar', function($query) use ($user){
                                $query->where('sekolah_id', $user->sekolah_id);
                                $query->whereHas('tahun_ajaran', function($query){
                                    $query->where('periode_aktif', 1);
                                });
                                $query->whereNull('jenis_keluar_id');
                                $query->where('soft_delete', 0);
                            });
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
                            $query->whereHas('wali_kelas', function($query) use ($user){
                                $query->where('soft_delete', 0);
                                $query->whereHas('ptk_terdaftar', function($query) use ($user){
                                    $query->where('sekolah_id', $user->sekolah_id);
                                    $query->whereHas('tahun_ajaran', function($query){
                                        $query->where('periode_aktif', 1);
                                    });
                                    $query->whereNull('jenis_keluar_id');
                                    $query->where('soft_delete', 0);
                                });
                            });
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
                            $query->whereHas('wali_kelas', function($query) use ($user){
                                $query->where('soft_delete', 0);
                                $query->whereHas('ptk_terdaftar', function($query) use ($user){
                                    $query->where('sekolah_id', $user->sekolah_id);
                                    $query->whereHas('tahun_ajaran', function($query){
                                        $query->where('periode_aktif', 1);
                                    });
                                    $query->whereNull('jenis_keluar_id');
                                    $query->where('soft_delete', 0);
                                });
                            });
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
                            /*$query->whereHas('akt_pd', function($query){
                                $query->where('soft_delete', 0);
                                $query->whereHas('anggota_akt_pd', function($query){
                                    $query->where('soft_delete', 0);
                                    $query->whereHas('registrasi_peserta_didik', function($query){
                                        $query->where('sekolah_id', request()->sekolah_id);
                                        $query->where('soft_delete', 0);
                                        $query->whereNull('jenis_keluar_id');
                                    });
                                });
                            });*/
                        });
                    })->count(),
                ],
            ];
            foreach($table_sync as $sync){
                $jumlah += $sync['count'];
            }
        }
        $data = [
            'sekolah' => $sekolah,
            'user' => $user,
            'jumlah' => $jumlah,
            'table_sync' => $table_sync,
            'error' => $error,
        ];
        return response()->json($data);
    }
    private function periode_aktif(){
        $find = Semester::where('periode_aktif', 1)->first();
        $data = [$find->tanggal_mulai, $find->tanggal_selesai];
        return $data;
    }
    public function sekolah(Request $request)
    {
        $user = auth()->user();
        $JenisPendaftaran = [];
        $JenisPtk = [];
        $wilayah = [];
        if ($request->isMethod('post')) {
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
        } else {
            $semester = NULL;
            try {
                $wilayah = Wilayah::where('id_level_wilayah', 1)->orderBy('kode_wilayah')->get();
                $JenisPendaftaran = JenisPendaftaran::where('daftar_sekolah', 1)->whereNull('expired_date')->orderBy('jenis_pendaftaran_id')->get();
                $JenisPtk = JenisPtk::whereNull('expired_date')->orderBy('jenis_ptk_id')->get();
                $semester = Semester::where('periode_aktif', 1)->first();
            } catch (\Throwable $th) {
                //throw $th;
            }
            $data = [
                'sekolah' => Sekolah::find($user->sekolah_id),
                'jenis_pendaftaran' => $JenisPendaftaran,
                'jenis_ptk' => $JenisPtk,
                'semester' => $semester,
                'wilayah' => $wilayah,
                'jam_sinkron' => $this->jam_sinkron(),
            ];
        }
        return response()->json($data);
    }
    public function reset(){
        Artisan::call('migrate:refresh --seed');
        return redirect(url('/'));
    }
    public function kirim_data(){
        $text = 'Data Dapodik';
        if(request()->data){
            if(request()->data == 'ptk'){
                $text = 'Data PTK';
            }
            if(request()->data == 'rombel'){
                $text = 'Data Rombongan Belajar';
            }
            if(request()->data == 'pd_aktif'){
                $text = 'Data Peserta Didik Aktif';
            }
            if(request()->data == 'pd_keluar'){
                $text = 'Data Peserta Didik Keluar';
            }
            if(request()->data == 'anggota_matpil'){
                $text = 'Data Anggota Rombel Matpel Pilihan';
            }
            if(request()->data == 'pembelajaran'){
                $text = 'Data Pembelajaran';
            }
            if(request()->data == 'ekskul'){
                $text = 'Data Ekstrakurikuler';
            }
            if(request()->data == 'anggota_ekskul'){
                $text = 'Data Anggota Ekstrakurikuler';
            }
            if(request()->data == 'dudi'){
                $text = 'Data DUDI';
            }
        }
        $data = [
            'icon' => 'tabler-check',
            'color' => 'success',
            'title' => 'Berhasil!',
            'text' => $text .' berhasil dikirim',
        ];
        return response()->json($data);
    }
}
