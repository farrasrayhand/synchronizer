<?php
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Models\Ptk;
use App\Models\RombonganBelajar;
use App\Models\PesertaDidik;
use App\Models\Semester;
use App\Models\AnggotaRombel;
use App\Models\KelasEkskul;
use App\Models\Pembelajaran;
use App\Models\Dudi;

function prepare_send($str){
    return rawurlencode(base64_encode(gzcompress(encryptor(serialize($str)))));
}
function prepare_receive($str){
    return unserialize(decryptor(gzuncompress(base64_decode(rawurldecode($str)))));
}
function encryptor($str){
    return $str;
}
function decryptor($str){
    return $str;
}
function jenis_keluar($query, $status, $sekolah_id, $semester_id){
    if($status){
        $query->whereNotIn('jenis_keluar_id', ['2', '3', '4', '5', '6', '7', '8', '9']);
        $query->where('soft_delete', 0);
        $query->where('sekolah_id', $sekolah_id);
        $query->orWhereNull('jenis_keluar_id');
        $query->where('soft_delete', 0);
        $query->where('sekolah_id', $sekolah_id);
    } else {
        $query->whereIn('jenis_keluar_id', ['2', '3', '4', '5', '6', '7', '8', '9']);
        $query->whereBetween('tanggal_keluar', periode_aktif($semester_id));
        $query->where('soft_delete', 0);
        $query->where('sekolah_id', $sekolah_id);
    }
}
function periode_aktif($semester_id){
    $find = Semester::find($semester_id);
    $data = [$find->tanggal_mulai, $find->tanggal_selesai];
    return $data;
}
function getPtk($sekolah_id, $tahun_ajaran_id){
    $data = Ptk::with([
        'ptk_terdaftar' => function($query) use ($sekolah_id, $tahun_ajaran_id){
            $query->where('sekolah_id', $sekolah_id);
            $query->where('tahun_ajaran_id', $tahun_ajaran_id);
            $query->whereNull('jenis_keluar_id');
            $query->where('soft_delete', 0);
        },
        'tugas_tambahan' => function($query){
            $query->where('soft_delete', 0);
            $query->whereNull('tst_tambahan');
        },
        'rwy_pend_formal' => function($query){
            $query->where('gelar_akademik_id', '<>', 99999);
		    $query->whereNotNull('gelar_akademik_id');
            $query->where('soft_delete', 0);
        },
        'wilayah' => function($query){
            $query->with(['parrentRecursive']);
        }
    ])->where(function($query) use ($sekolah_id, $tahun_ajaran_id){
        $query->where('soft_delete', 0);
        $query->whereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id){
            $query->where('sekolah_id', $sekolah_id);
            $query->where('tahun_ajaran_id', $tahun_ajaran_id);
            $query->whereNull('jenis_keluar_id');
            $query->where('soft_delete', 0);
        });
    })->orderBy('nama')->get();
    return $data;
}
function getRombonganBelajar($sekolah_id, $tahun_ajaran_id, $semester_id){
    $data = RombonganBelajar::with([
        'jurusan_sp' => function($query){
            $query->where('soft_delete', 0);
            $query->with([
                'jurusan' => function($query){
                    $query->whereNull('expired_date');
                },
            ]);
        },
        'kurikulum' => function($query){
            $query->whereNull('expired_date');
        },
        'wali_kelas',
    ])->where(function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->whereHas('wali_kelas', function($query) use ($sekolah_id, $tahun_ajaran_id){
            $query->where('soft_delete', 0);
            $query->whereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id){
                $query->where('sekolah_id', $sekolah_id);
                $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                $query->whereNull('jenis_keluar_id');
                $query->where('soft_delete', 0);
            });
        });
        $query->where('semester_id', $semester_id);
		$query->where('sekolah_id', $sekolah_id);
        $query->whereIn('jenis_rombel', [1, 8, 9, 16]);
        $query->where('soft_delete', 0);
    })->orderBy('nama')->get();
    return $data;
}
function getPd($status, $sekolah_id, $tahun_ajaran_id, $semester_id){
    $data = PesertaDidik::with([
        'anggota_rombel' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('soft_delete', 0);
            $query->withWhereHas('rombongan_belajar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('soft_delete', 0);
                $query->where('sekolah_id', $sekolah_id);
                $query->where('semester_id', $semester_id);
                //$query->whereIn('jenis_rombel', [1, 8, 9]);
                $query->where('jenis_rombel', 1);
                $query->whereHas('wali_kelas', function($query) use ($sekolah_id, $tahun_ajaran_id){
                    $query->where('soft_delete', 0);
                    $query->whereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id){
                        $query->where('sekolah_id', $sekolah_id);
                        $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                        $query->whereNull('jenis_keluar_id');
                        $query->where('soft_delete', 0);
                    });
                });
            });
        },
        'wilayah' => function($query){
            $query->with(['parrentRecursive']);
        },
        'registrasi_peserta_didik' => function($query) use ($status, $sekolah_id, $semester_id){
            jenis_keluar($query, $status, $sekolah_id, $semester_id);
        },
        'diterima_dikelas' => function($query) use ($status, $sekolah_id){
            $query->with(['rombongan_belajar' => function($query){
                $query->select('rombongan_belajar_id', 'nama');
                $query->where('soft_delete', 0);
            }]);
            $query->where('jenis_pendaftaran_id', 1);
            $query->where('soft_delete', 0);
            $query->whereHas('rombongan_belajar', function($query) use ($sekolah_id){
                $query->where('soft_delete', 0);
                $query->where('sekolah_id', $sekolah_id);
                $query->where('jenis_rombel', 1);
            });
            $query->orWhere('jenis_pendaftaran_id', 2);
            $query->where('soft_delete', 0);
            $query->whereHas('rombongan_belajar', function($query) use ($sekolah_id){
                $query->where('soft_delete', 0);
                $query->where('sekolah_id', $sekolah_id);
                $query->where('jenis_rombel', 1);
            });
        }
    ])->where(function($query) use ($status, $sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->whereHas('registrasi_peserta_didik', function($query) use ($status, $sekolah_id, $semester_id){
            jenis_keluar($query, $status, $sekolah_id, $semester_id);
        });
        $query->whereHas('anggota_rombel', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('soft_delete', 0);
            $query->whereHas('rombongan_belajar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('soft_delete', 0);
                $query->where('sekolah_id', $sekolah_id);
                $query->where('semester_id', $semester_id);
                //$query->whereIn('jenis_rombel', [1, 8, 9]);
                $query->where('jenis_rombel', 1);
                $query->whereHas('wali_kelas', function($query) use ($sekolah_id, $tahun_ajaran_id){
                    $query->where('soft_delete', 0);
                    $query->whereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id){
                        $query->where('sekolah_id', $sekolah_id);
                        $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                        $query->whereNull('jenis_keluar_id');
                        $query->where('soft_delete', 0);
                    });
                });
            });
        });
    })->orderBy('peserta_didik_id')->get();
    return $data;
}
function getAnggotaPilihan($sekolah_id, $tahun_ajaran_id, $semester_id){
    $data = AnggotaRombel::with(['rombongan_belajar', 'pd' => function($query){
        $query->where('soft_delete', 0);
    }])->where(function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->whereHas('rombongan_belajar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('semester_id', $semester_id);
            $query->where('sekolah_id', $sekolah_id);
            $query->where('soft_delete', 0);
            $query->where('jenis_rombel', 16);
            $query->whereHas('wali_kelas', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('soft_delete', 0);
                $query->whereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                    $query->where('sekolah_id', $sekolah_id);
                    $query->where('tahun_ajaran_id', $tahun_ajaran_id);
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
    })->get();
    return $data;
}
function getEkskul($sekolah_id, $tahun_ajaran_id, $semester_id){
    $data = KelasEkskul::with([
        'rombongan_belajar' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('soft_delete', 0);
            $query->withWhereHas('wali_kelas', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('soft_delete', 0);
                $query->whereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                    $query->where('sekolah_id', $sekolah_id);
                    $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                    $query->whereNull('jenis_keluar_id');
                    $query->where('soft_delete', 0);
                });
            });
            $query->with(['ruang' => function($query){
                $query->where('soft_delete', 0);
                $query->select('id_ruang', 'nm_ruang');
            }]);
        },
    ])->where(function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->where('soft_delete', 0);
        $query->whereHas('rombongan_belajar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('soft_delete', 0);
            $query->where('semester_id', $semester_id);
            $query->where('sekolah_id', $sekolah_id);
            $query->where('jenis_rombel', 51);
            $query->whereHas('wali_kelas', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('soft_delete', 0);
                $query->whereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                    $query->where('sekolah_id', $sekolah_id);
                    $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                    $query->whereNull('jenis_keluar_id');
                    $query->where('soft_delete', 0);
                });
            });
        });
    })->get();
    return $data;
}
function getPembelajaran($sekolah_id, $tahun_ajaran_id, $semester_id){
    $data = Pembelajaran::withWhereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->where('ptk.soft_delete', 0);
        $query->where('ptk_terdaftar.soft_delete', 0);
        $query->whereNull('jenis_keluar_id');
        $query->where('sekolah_id', $sekolah_id);
        $query->where('tahun_ajaran_id', $tahun_ajaran_id);
        $query->with([
            'wilayah' => function($query){
                $query->with(['parrentRecursive']);
            },
            'ptk_terdaftar' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('sekolah_id', $sekolah_id);
                $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                $query->whereNull('jenis_keluar_id');
                $query->where('soft_delete', 0);
            },
            'tugas_tambahan' => function($query){
                    $query->where('soft_delete', 0);
                    $query->whereNull('tst_tambahan');
            },
            'rwy_pend_formal' => function($query){
                $query->where('gelar_akademik_id', '<>', 99999);
                $query->whereNotNull('gelar_akademik_id');
                $query->where('soft_delete', 0);
            },
        ]);
    })->withWhereHas('guru', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->whereNull('jenis_keluar_id');
        $query->where('sekolah_id', $sekolah_id);
        $query->where('tahun_ajaran_id', $tahun_ajaran_id);
        $query->where('soft_delete', 0);
    })->with([
        'rombongan_belajar',
        'mata_pelajaran',
        'sub_mapel' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('soft_delete', 0);
            $query->withWhereHas('guru', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->whereNull('jenis_keluar_id');
                $query->where('sekolah_id', $sekolah_id);
                $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                $query->where('soft_delete', 0);
            });
            $query->with([
                'rombongan_belajar',
                'ptk_terdaftar' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                    $query->where('ptk.soft_delete', 0);
                    $query->where('ptk_terdaftar.soft_delete', 0);
                    $query->whereNull('jenis_keluar_id');
                    $query->where('sekolah_id', $sekolah_id);
                    $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                    $query->with([
                        'wilayah' => function($query){
                            $query->with(['parrentRecursive']);
                        },
                        'ptk_terdaftar' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                            $query->where('sekolah_id', $sekolah_id);
                            $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                            $query->whereNull('jenis_keluar_id');
                            $query->where('soft_delete', 0);
                        },
                        'tugas_tambahan' => function($query){
                            $query->where('soft_delete', 0);
                            $query->whereNull('tst_tambahan');
                        },
                        'rwy_pend_formal' => function($query){
                            $query->where('gelar_akademik_id', '<>', 99999);
                            $query->whereNotNull('gelar_akademik_id');
                            $query->where('soft_delete', 0);
                        },
                    ]);
                },
                'mata_pelajaran' => function($query){
                    $query->whereNull('expired_date');
                },
                'sub_mapel' => function($query){
                    $query->where('soft_delete', 0);
                },
            ]);
        },
    ])->where(function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->where('soft_delete', 0);
        $query->whereNull('induk_pembelajaran_id');
        $query->whereHas('rombongan_belajar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('soft_delete', 0);
            $query->where('semester_id', $semester_id);
            $query->where('sekolah_id', $sekolah_id);
            $query->whereIn('jenis_rombel', [1, 8, 9, 16]);
            $query->whereHas('wali_kelas', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('soft_delete', 0);
                $query->whereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                    $query->where('sekolah_id', $sekolah_id);
                    $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                    $query->whereNull('jenis_keluar_id');
                    $query->where('soft_delete', 0);
                });
            });
        });
    })->orderBy('pembelajaran_id')->get();
    return $data;
}
function getAnggotaEkskul($sekolah_id, $tahun_ajaran_id, $semester_id){
    $data = AnggotaRombel::with(['rombongan_belajar', 'pd' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->where('soft_delete', 0);
        $query->whereHas('registrasi_peserta_didik', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('sekolah_id', $sekolah_id);
            $query->where('soft_delete', 0);
            $query->whereNull('jenis_keluar_id');
        });
        $query->with([
            'registrasi_peserta_didik' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('sekolah_id', $sekolah_id);
                $query->where('soft_delete', 0);
                $query->whereNull('jenis_keluar_id');
            },
            'wilayah' => function($query){
                $query->with(['parrentRecursive']);
            },
            'diterima_dikelas' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->with(['rombongan_belajar' => function($query){
                    $query->select('rombongan_belajar_id', 'nama');
                    $query->where('soft_delete', 0);
                }]);
                $query->where('jenis_pendaftaran_id', 1);
                $query->where('soft_delete', 0);
                $query->whereHas('rombongan_belajar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                    $query->where('soft_delete', 0);
                    $query->where('sekolah_id', $sekolah_id);
                    $query->where('jenis_rombel', 1);
                });
                $query->orWhere('jenis_pendaftaran_id', 2);
                $query->where('soft_delete', 0);
                $query->whereHas('rombongan_belajar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                    $query->where('soft_delete', 0);
                    $query->where('sekolah_id', $sekolah_id);
                    $query->where('jenis_rombel', 1);
                });
            }
        ]);
    }])->where(function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->where('soft_delete', 0);
        $query->whereHas('rombongan_belajar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->whereHas('kelas_ekskul', function($query){
                $query->where('soft_delete', 0);    
            });
            $query->where('soft_delete', 0);
            $query->where('semester_id', $semester_id);
            $query->where('sekolah_id', $sekolah_id);
            $query->where('jenis_rombel', 51);
            $query->whereHas('wali_kelas', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('soft_delete', 0);
                $query->whereHas('ptk_terdaftar', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                    $query->where('sekolah_id', $sekolah_id);
                    $query->where('tahun_ajaran_id', $tahun_ajaran_id);
                    $query->whereNull('jenis_keluar_id');
                    $query->where('soft_delete', 0);
                });
            });
        });
        $query->whereHas('pd', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('soft_delete', 0);
            $query->whereHas('registrasi_peserta_didik', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('sekolah_id', $sekolah_id);
                $query->where('soft_delete', 0);
                $query->whereNull('jenis_keluar_id');
            });
        });
    })->get();
    return $data;
}
function getDudi($sekolah_id, $tahun_ajaran_id, $semester_id){
    $data = Dudi::withWhereHas('mou', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
        $query->where('soft_delete', 0);
        $query->where('sekolah_id', $sekolah_id);
        $query->with(['akt_pd' => function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
            $query->where('soft_delete', 0);
            $query->withWhereHas('anggota_akt_pd', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                $query->where('soft_delete', 0);
                $query->withWhereHas('registrasi_peserta_didik', function($query) use ($sekolah_id, $tahun_ajaran_id, $semester_id){
                    $query->where('sekolah_id', $sekolah_id);
                    $query->where('soft_delete', 0);
                    $query->whereNull('jenis_keluar_id');
                });
            });
            $query->with([
                'bimbing_pd' => function($query){
                    $query->where('soft_delete', 0);
                },
            ]);
        }]);
    })->orderBy('dudi_id')->get();
    return $data;
}
function kirimDapodik($data_sync, $text, $next, $url_erapor = NULL){
    $url_erapor = $url_erapor ?? request()->url_erapor;
    try {
        $response = Http::withOptions([
            'verify' => false,
        ])->withHeaders([
            'x-api-key' => $data_sync['sekolah_id'],
            'x-api-npsn' => $data_sync['npsn'],
        ])->retry(3, 100)->post($url_erapor.'/api/sinkronisasi/synchronizer', $data_sync);
        $result = $response->json();
        if($response->successful()){
            $data = [
                'icon' => 'tabler-check',
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => $text .' berhasil dikirim',
                'result' => $result,
                'next' => $next,
                'notif' => [
                    'icon' => 'tabler-check',
                    'color' => 'success',
                    'title' => 'Berhasil!',
                    'text' => $text .' berhasil dikirim',
                ]
            ];
        } else {
            $data = [
                'color' => 'error',
                'icon' => 'tabler-xbox-x',
                'title' => 'Gagal!',
                'text' => $result->message,
                'errors' => $result->errors,
                'next' => FALSE,
                'notif' => [
                    'color' => 'error',
                    'icon' => 'tabler-xbox-x',
                    'title' => 'Gagal!',
                    'text' => 'Data Dapodik berhasil dikirim',
                ]
            ];
        }
    } catch (RequestException $e) {
        if ($e->response->status() === 401) {
            $data = [
                'color' => 'error',
                'icon' => 'tabler-xbox-x',
                'title' => 'Gagal!',
                'text' => 'Sekolah tidak ditemukan',
                'notif' => [
                    'color' => 'error',
                    'icon' => 'tabler-xbox-x',
                    'title' => 'Gagal!',
                    'text' => 'Sekolah tidak ditemukan',
                ]
            ];
        } else {
            $data = [
                'color' => 'error',
                'icon' => 'tabler-xbox-x',
                'title' => 'Gagal!',
                'text' => $e->getMessage(),
                'notif' => [
                    'color' => 'error',
                    'icon' => 'tabler-xbox-x',
                    'title' => 'Gagal!',
                    'text' => $e->getMessage(),
                ]
            ];
        }
    }
    return $data;
}
