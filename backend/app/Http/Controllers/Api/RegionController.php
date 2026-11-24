<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class RegionController extends Controller
{
    protected function getOganConfig()
    {
        $baseUrl = rtrim(env('OGAN_API_URL', 'https://api-oganlopian.pratamatechsolution.co.id/api/'), '/');
        $version = env('OGAN_API_VERSION', 'v1');
        $apiKey = env('OGAN_API_KEY');

        return [
            'baseUrl' => "{$baseUrl}/{$version}",
            'apiKey' => $apiKey,
            'regency' => '3214'
        ];
    }

    /**
     * Mengambil daftar kecamatan di Kabupaten Purwakarta (Regency 3214)
     * Sumber: Ogan Lopian Region API
     */
    public function getDistricts(Request $request)
    {
        try {
            $regency = $request->get('regency', '3214');
            $cacheKey = "ogan_districts_{$regency}";

            $districts = Cache::remember($cacheKey, 86400, function () use ($regency) {
                $cfg = $this->getOganConfig();
                $url = "{$cfg['baseUrl']}/regions/districts/{$regency}";

                try {
                    $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Accept' => 'application/json',
                    ])->timeout(10)->get($url, [
                        'key' => $cfg['apiKey']
                    ]);

                    if ($response->successful()) {
                        $json = $response->json();
                        if (!empty($json['data'])) {
                            return collect($json['data'])->map(function ($d) {
                                $rawName = ucwords(strtolower(trim($d['name'])));
                                return [
                                    'id' => (int)$d['id'],
                                    'regency_id' => (int)($d['regency_id'] ?? 3214),
                                    'name' => $rawName,
                                    'label' => 'Kecamatan ' . $rawName,
                                ];
                            })->sortBy('name')->values()->all();
                        }
                    }
                } catch (\Exception $ex) {
                    // Fail silently to fallback
                }

                return null;
            });

            // Fallback 17 Kecamatan resmi Purwakarta jika koneksi eksternal terkendala
            if (!$districts || count($districts) === 0) {
                $districts = [
                    ['id' => 3214101, 'name' => 'Babakancikao', 'label' => 'Kecamatan Babakancikao'],
                    ['id' => 3214070, 'name' => 'Bojong', 'label' => 'Kecamatan Bojong'],
                    ['id' => 3214112, 'name' => 'Bungursari', 'label' => 'Kecamatan Bungursari'],
                    ['id' => 3214110, 'name' => 'Campaka', 'label' => 'Kecamatan Campaka'],
                    ['id' => 3214111, 'name' => 'Cibatu', 'label' => 'Kecamatan Cibatu'],
                    ['id' => 3214060, 'name' => 'Darangdan', 'label' => 'Kecamatan Darangdan'],
                    ['id' => 3214010, 'name' => 'Jatiluhur', 'label' => 'Kecamatan Jatiluhur'],
                    ['id' => 3214081, 'name' => 'Kiarapedes', 'label' => 'Kecamatan Kiarapedes'],
                    ['id' => 3214020, 'name' => 'Maniis', 'label' => 'Kecamatan Maniis'],
                    ['id' => 3214090, 'name' => 'Pasawahan', 'label' => 'Kecamatan Pasawahan'],
                    ['id' => 3214040, 'name' => 'Plered', 'label' => 'Kecamatan Plered'],
                    ['id' => 3214091, 'name' => 'Pondok Salam', 'label' => 'Kecamatan Pondok Salam'],
                    ['id' => 3214100, 'name' => 'Purwakarta', 'label' => 'Kecamatan Purwakarta'],
                    ['id' => 3214011, 'name' => 'Sukasari', 'label' => 'Kecamatan Sukasari'],
                    ['id' => 3214050, 'name' => 'Sukatani', 'label' => 'Kecamatan Sukatani'],
                    ['id' => 3214030, 'name' => 'Tegal Waru', 'label' => 'Kecamatan Tegal Waru'],
                    ['id' => 3214080, 'name' => 'Wanayasa', 'label' => 'Kecamatan Wanayasa'],
                ];
            }

            return response()->json([
                'status' => 'success',
                'source' => 'api-oganlopian',
                'regency' => $regency,
                'total' => count($districts),
                'data' => $districts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat kecamatan dari API Ogan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengambil daftar kelurahan/desa di kecamatan tertentu
     * Sumber: Ogan Lopian Region API (/regions/villages/{districtId})
     */
    public function getVillages(Request $request, $districtId)
    {
        try {
            if (!is_numeric($districtId)) {
                $districtId = $this->resolveDistrictIdByName($districtId);
            }

            $cacheKey = "ogan_villages_{$districtId}";

            $villages = Cache::remember($cacheKey, 86400, function () use ($districtId) {
                $cfg = $this->getOganConfig();
                $url = "{$cfg['baseUrl']}/regions/villages/{$districtId}";

                try {
                    $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Accept' => 'application/json',
                    ])->timeout(10)->get($url, [
                        'key' => $cfg['apiKey']
                    ]);

                    if ($response->successful()) {
                        $json = $response->json();
                        if (!empty($json['data'])) {
                            return collect($json['data'])->map(function ($v) {
                                $rawName = ucwords(strtolower(trim($v['name'])));
                                $isCity = ((int)($v['district_id'] ?? 0) === 3214100);
                                $prefix = $isCity ? 'Kelurahan' : 'Desa';
                                return [
                                    'id' => (int)$v['id'],
                                    'district_id' => (int)($v['district_id'] ?? 0),
                                    'name' => $rawName,
                                    'label' => "{$prefix} {$rawName}",
                                ];
                            })->sortBy('name')->values()->all();
                        }
                    }
                } catch (\Exception $ex) {
                    // Fail silently to fallback
                }

                return null;
            });

            if (!$villages || count($villages) === 0) {
                // Fallback default jika koneksi gagal
                $villages = $this->getFallbackVillages($districtId);
            }

            return response()->json([
                'status' => 'success',
                'source' => 'api-oganlopian',
                'district_id' => (int)$districtId,
                'total' => count($villages),
                'data' => $villages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat kelurahan/desa: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function resolveDistrictIdByName($name)
    {
        $map = [
            'babakancikao' => 3214101,
            'bojong' => 3214070,
            'bungursari' => 3214112,
            'campaka' => 3214110,
            'cibatu' => 3214111,
            'darangdan' => 3214060,
            'jatiluhur' => 3214010,
            'kiarapedes' => 3214081,
            'maniis' => 3214020,
            'pasawahan' => 3214090,
            'plered' => 3214040,
            'pondoksalam' => 3214091,
            'pondok salam' => 3214091,
            'purwakarta' => 3214100,
            'sukasari' => 3214011,
            'sukatani' => 3214050,
            'tegalwaru' => 3214030,
            'tegal waru' => 3214030,
            'wanayasa' => 3214080,
        ];

        $key = strtolower(trim(preg_replace('/^kecamatan\s*/i', '', $name)));
        return $map[$key] ?? 3214100;
    }

    protected function getFallbackVillages($districtId)
    {
        $map = [
            3214100 => ['Nagri Kaler', 'Nagri Kidul', 'Nagri Tengah', 'Cipaisan', 'Ciseureuh', 'Sindangkasih', 'Purwamekar', 'Tegalmunjul', 'Munjuljaya'],
            3214101 => ['Babakancikao', 'Cicadas', 'Cilangkap', 'Ciwareng', 'Kadumekar', 'Maracang', 'Hegarmanah'],
            3214070 => ['Bojong Barat', 'Bojong Timur', 'Cibingbin', 'Cihanjawar', 'Cikeris', 'Karyamekar', 'Pasirmunjul', 'Pangkalan', 'Sindangpanon', 'Sukamanah'],
            3214112 => ['Bungursari', 'Cibening', 'Cibungur', 'Cikopo', 'Cinangka', 'Ciwangi', 'Dangdeur', 'Karangmukti', 'Wanakerta'],
            3214110 => ['Campaka', 'Campakasari', 'Cijaya', 'Cikumpay', 'Cimahi', 'Cisaat', 'Kertamukti'],
            3214111 => ['Cibatu', 'Cibukamanah', 'Cikadu', 'Cirangkong', 'Ciparungsari', 'Karyamekar', 'Wanawali'],
            3214060 => ['Darangdan', 'Cilingga', 'Depok', 'Gununghejo', 'Linggamukti', 'Mekarsari', 'Nangewer', 'Neglasari', 'Pasirangin', 'Sadarkarya', 'Sawit'],
            3214010 => ['Bunder', 'Cibinong', 'Cikaobandung', 'Cilegong', 'Cisalada', 'Jatiluhur', 'Jatimekar', 'Kembangkuning', 'Parakanlima'],
            3214081 => ['Cibeber', 'Ciracas', 'Gardu', 'Kiarapedes', 'Margaluyu', 'Mekarjaya', 'Parakan Garokgek', 'Pusakamulya'],
            3214020 => ['Ciramahilir', 'Cijambu', 'Citamiang', 'Gunungkarung', 'Pasirjambu', 'Sinargalih', 'Sukamukti', 'Tegaldatar'],
            3214090 => ['Cidahu', 'Ciherang', 'Cihuni', 'Kertajaya', 'Lebak Anyar', 'Pasawahan', 'Pasawahan Anyar', 'Pasawahan Kidul', 'Sawah Kulon', 'Warungkadu'],
            3214040 => ['Anjun', 'Babakansari', 'Cibogogirang', 'Citeko', 'Gandamekar', 'Gandasoli', 'Linggarsari', 'Liunggunung', 'Palinggihan', 'Pamoyanan', 'Plered', 'Rawasari', 'Sempur'],
            3214091 => ['Bungurjaya', 'Galudra', 'Gurudug', 'Pondokbungur', 'Salamjaya', 'Salem', 'Situ', 'Sukajadi', 'Tanjungsari'],
            3214011 => ['Ciririp', 'Kertamanah', 'Kutamanah', 'Parungkokosan', 'Sukasari'],
            3214050 => ['Sukatani', 'Cibodas', 'Cijantung', 'Cilalawi', 'Cipicung', 'Malangnengah', 'Panyindangan', 'Pasirmunjul', 'Sukamaju', 'Tajursindang'],
            3214030 => ['Cadasmekar', 'Cadassari', 'Cisarua', 'Citalang', 'Galumpit', 'Karoya', 'Pasirjengkol', 'Sukahaji', 'Sukamulya', 'Tegalsari', 'Warungjeruk'],
            3214080 => ['Babakan', 'Ciawi', 'Cibuntu', 'Legokhuni', 'Nagrog', 'Nangerang', 'Raharja', 'Sakambang', 'Simpang', 'Sukadami', 'Wanasari', 'Wanayasa'],
        ];

        $list = $map[(int)$districtId] ?? ['Nagri Kaler', 'Nagri Kidul'];
        $prefix = ((int)$districtId === 3214100) ? 'Kelurahan' : 'Desa';
        return array_map(function ($name) use ($prefix, $districtId) {
            return [
                'id' => 0,
                'district_id' => (int)$districtId,
                'name' => $name,
                'label' => "{$prefix} {$name}",
            ];
        }, $list);
    }
}
