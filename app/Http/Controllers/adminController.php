<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ProductionTData1;
use App\Models\ProductionTData2;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;
use App\Models\KodeLaravel;
use Illuminate\Support\Facades\Log;
use App\Models\UserSap;
use App\Models\workcenter;

use App\Models\MappingTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;

class adminController extends Controller
{
    public function index(Request $request, $kode)
    {
        // 1. Identifikasi User SAP yang login
        $user = Auth::user();
        if (!$user) {
             return redirect()->route('login');
        }
        $sapId = str_replace('@kmi.local', '', $user->email);
        $userSap = UserSap::where('user_sap', $sapId)->first();

        // 2. Identifikasi Kode Laravel (Bagian)
        $kodeLaravel = KodeLaravel::where('laravel_code', $kode)->first();

        if (!$userSap || !$kodeLaravel) {
            // Setup fallback jika user/kode tidak valid, atau return error page
            // Untuk saat ini kita set query kosong agar tidak error
            $allWcQuery = DB::table('workcenters')->whereRaw('1 = 0');
        } else {
             // 3. Ambil ID Workcenter dari MappingTable
             if (strtolower($sapId) === 'auto_email') {
                 $validWorkcenterIds = MappingTable::where('kode_laravel_id', $kodeLaravel->id)
                    ->pluck('workcenter_id');
             } else {
                 $validWorkcenterIds = MappingTable::where('user_sap_id', $userSap->id)
                    ->where('kode_laravel_id', $kodeLaravel->id)
                    ->pluck('workcenter_id');
             }

             // 4. Query Workcenter berdasarkan ID yang valid dari mapping
             $allWcQuery = DB::table('workcenters')
                ->select('kode_wc', 'description')
                ->whereIn('id', $validWorkcenterIds);
        }

        $statsPerWc = DB::table(DB::raw("({$allWcQuery->toSql()}) as master_wc"))
            ->mergeBindings($allWcQuery)
            ->leftJoin('production_t_data1 as trans_data', 'master_wc.kode_wc', '=', 'trans_data.ARBPL')
            ->selectRaw("
                master_wc.kode_wc AS wc_label,
                master_wc.description AS wc_description,
                COUNT(DISTINCT trans_data.AUFNR) AS pro_count,
                SUM(trans_data.CPCTYX) AS total_capacity
            ")
            ->groupBy('master_wc.kode_wc', 'master_wc.description')
            ->orderBy('master_wc.kode_wc', 'asc')
            ->get();

        Log::info('Stats Per WC Debug:', $statsPerWc->take(5)->toArray());

        $labels = $statsPerWc->pluck('wc_label')->all();
        $descriptions = $statsPerWc->pluck('wc_description')->all();
        $datasetPro = $statsPerWc->pluck('pro_count')->all();
        $datasetCapacity = $statsPerWc->pluck('total_capacity')->all();

        $targetUrls = collect($labels)->map(function ($wcLabel) use ($kode) {
            return route('wc.details', ['kode' => $kode, 'wc' => $wcLabel]);
        })->all();

        $datasets = [
            [
                'label' => 'PRO Count',
                'data' => $datasetPro,
                'descriptions' => $descriptions,
                'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                'borderColor' => 'rgba(37, 99, 235, 1)',
                'borderWidth' => 1,
                'borderRadius' => 4,
                'satuan' => 'PRO'
            ],
            [
                'label' => 'Capacity Count',
                'data' => $datasetCapacity,
                'descriptions' => $descriptions,
                'backgroundColor' => 'rgba(249, 115, 22, 0.6)',
                'borderColor' => 'rgba(234, 88, 12, 1)',
                'borderWidth' => 1,
                'borderRadius' => 4,
                'satuan' => 'Jam'
            ],
        ];

        $statsByStatus = DB::table('production_t_data1')
            ->where('WERKSX', $kode)
            ->whereRaw("NULLIF(TRIM(AUFNR), '') IS NOT NULL")
            ->select('STATS', DB::raw('COUNT(DISTINCT AUFNR) as pro_count_by_status'))
            ->groupBy('STATS')
            ->orderBy('STATS', 'asc')
            ->get();

        $doughnutChartLabels = $statsByStatus->pluck('STATS')->values()->all();
        $doughnutChartDataset = $statsByStatus->pluck('pro_count_by_status')->values()->all();

        $doughnutChartDatasets = [
            [
                'label' => 'PRO Count by Status',
                'data' => $doughnutChartDataset,
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)', 'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)'
                ],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]
        ];
        
        $topWcByCapacity = DB::table('production_t_data1 as t1')
            ->join('workcenters as wc', 't1.ARBPL', '=', 'wc.kode_wc')
            ->select(
                't1.ARBPL',
                'wc.description',
                DB::raw('COALESCE(SUM(t1.CPCTYX), 0) as total_capacity')
            )
            ->where('t1.WERKSX', $kode)
            ->whereNotNull('t1.ARBPL')
            ->groupBy('t1.ARBPL', 'wc.description')
            ->orderByDesc('total_capacity')
            ->limit(5)
            ->get();

        $lolipopChartLabels = $topWcByCapacity->pluck('ARBPL')->all();
        $lolipopChartData = $topWcByCapacity->pluck('total_capacity')->all();
        $lolipopChartDescriptions = $topWcByCapacity->pluck('description')->all();

        $lolipopChartDatasets = [
            [
                'label' => 'Distribusi Kapasitas',
                'data' => $lolipopChartData,
                'descriptions' => $lolipopChartDescriptions,
                'satuan' => 'Jam',
                'backgroundColor' => [
                    'rgba(255, 166, 158, 0.8)', 'rgba(174, 217, 224, 0.8)', 'rgba(204, 204, 255, 0.8)',
                    'rgba(255, 225, 179, 0.8)', 'rgba(181, 234, 215, 0.8)',
                ],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]
        ];
        
        $nama_bagian = KodeLaravel::where('laravel_code', $kode)->value('description');
        $sub_kategori = $nama_bagian; // Set sub_kategori to description as requested
        $kategori = KodeLaravel::where('laravel_code', $kode)->value('plant');
        
        // A. PENANGANAN AJAX LOAD MORE (Untuk Infinite Scroll)
        if ($request->ajax() && $request->has('load_more')) {
            $section = $request->input('load_more');

            if ($section === 'reservasi') {
                $searchReservasi = $request->input('search_reservasi');
                $TData4 = ProductionTData4::where('WERKSX', $kode)
                    ->when($searchReservasi, function ($query, $term) {
                        return $query->where(function($q) use ($term) {
                            $q->where('RSNUM', 'like', "%{$term}%")
                              ->orWhere('MATNR', 'like', "%{$term}%")
                              ->orWhere('MAKTX', 'like', "%{$term}%");
                        });
                    })
                    ->paginate(50, ['*'], 'page_reservasi')
                    ->withQueryString();
                
                // Transform string MAKTX
                $TData4->getCollection()->transform(function ($item) {
                    $item->MAKTX = trim(preg_replace('/\s+/', ' ', $item->MAKTX ?? ''));
                    return $item;
                });

                return view('Admin.partials.rows_reservasi', ['TData4' => $TData4])->render();
            }

            if ($section === 'pro') {
                $today = Carbon::today();
                $searchPro = $request->input('search_pro');
                $ongoingProData = ProductionTData3::where('WERKSX', $kode)
                    ->whereDate('GSTRP', $today)
                    ->where('STATS', 'REL')
                    ->when($searchPro, function ($query, $term) {
                        return $query->where(function($q) use ($term) {
                             $q->where('AUFNR', 'like', "%{$term}%")
                              ->orWhere('KDAUF', 'like', "%{$term}%")
                              ->orWhere('MATNR', 'like', "%{$term}%")
                              ->orWhere('MAKTX', 'like', "%{$term}%");
                        });
                    })
                    ->latest('AUFNR')
                    ->paginate(50, ['*'], 'page_pro')
                    ->withQueryString();

                return view('Admin.partials.rows_ongoing_pro', ['ongoingProData' => $ongoingProData])->render();
            }

            if ($section === 'total_pro') {
                $searchTotalPro = $request->input('search_total_pro');
                $searchDateTotalPro = $request->input('search_date_total_pro');
                $searchStatusTotalPro = $request->input('search_status_total_pro'); // Capture Status Filter
                
                // Advanced Filters
                $advAufnr = $request->input('adv_aufnr');
                $advMatnr = $request->input('adv_matnr') ?? $request->input('multi_matnr'); // Fallback to legacy
                $advMaktx = $request->input('adv_maktx');
                $advArbpl = $request->input('adv_arbpl');
                $advKdauf = $request->input('adv_kdauf');
                // $advKdpos removed merged into advKdauf

                // Debug Logging
                if ($request->ajax()) {
                    Log::info("[Dashboard Search] Params:", $request->all());
                }

                $hasAdvancedFilters = $advAufnr || $advMatnr || $advMaktx || $advArbpl || $advKdauf;

                $allProData = ProductionTData3::where('WERKSX', $kode)
                    ->when($searchStatusTotalPro, function ($query, $status) {
                        return $query->where('STATS', $status);
                    })
                    ->when($searchDateTotalPro, function ($query, $date) {
                        if (str_contains($date, ' to ')) {
                            $parts = explode(' to ', $date);
                            if (count($parts) === 2) {
                                return $query->whereBetween('GSTRP', [trim($parts[0]), trim($parts[1])]);
                            }
                        }
                        return $query->whereDate('GSTRP', $date);
                    })
                    ->when($searchTotalPro, function ($query, $term) {
                        return $query->where(function($q) use ($term) {
                            // Check for specific SO-Item pattern (e.g. 1020003662-60)
                            if (str_contains($term, '-')) {
                                $parts = explode('-', $term);
                                if (count($parts) >= 2) {
                                    $soPart = trim($parts[0]);
                                    $itemPart = trim($parts[1]);
                                    
                                    // Try strict search if looks like valid SO/Item
                                    $q->where(function($sub) use ($soPart, $itemPart) {
                                        // Basic match logic
                                        $sub->where('KDAUF', 'like', "%{$soPart}%")
                                            ->where('KDPOS', 'like', "%{$itemPart}%");
                                    });
                                    return; // Return early
                                }
                            }

                            $q->where('AUFNR', 'like', "%{$term}%")
                              ->orWhere('KDAUF', 'like', "%{$term}%")
                              ->orWhere('MAKTX', 'like', "%{$term}%")
                              ->orWhere('DISPO', 'like', "%{$term}%")
                              ->orWhere('MATNR', 'like', "%{$term}%")
                              ->orWhere('STATS', 'like', "%{$term}%");
                        });
                    })
                    // --- ADVANCED FILTERS START (AND LOGIC) ---
                    ->when($advAufnr, function($query, $term) {
                        $items = array_filter(array_map('trim', explode(',', $term)));
                        $paddedItems = [];
                        foreach ($items as $item) {
                            if (is_numeric($item)) {
                                $paddedItems[] = str_pad($item, 12, '0', STR_PAD_LEFT);
                            }
                        }

                        if (!empty($items)) {
                            $query->where(function($sub) use ($items, $paddedItems) {
                                $sub->whereIn('AUFNR', $items);
                                if (!empty($paddedItems)) {
                                    $sub->orWhereIn('AUFNR', $paddedItems);
                                }
                            });
                        }
                    })
                    ->when($advMatnr, function($query, $term) {
                        $items = array_filter(array_map('trim', explode(',', $term)));
                        $paddedItems = [];
                        foreach ($items as $item) {
                            if (is_numeric($item)) {
                                $paddedItems[] = str_pad($item, 18, '0', STR_PAD_LEFT);
                            }
                        }
                        
                        if (!empty($items)) {
                            $query->where(function($sub) use ($items, $paddedItems) {
                                $sub->whereIn('MATNR', $items);
                                if (!empty($paddedItems)) {
                                    $sub->orWhereIn('MATNR', $paddedItems);
                                }
                            });
                        }
                    })
                    ->when($advMaktx, function($query, $term) {
                        $items = array_filter(array_map('trim', explode(',', $term)));
                        if (!empty($items)) {
                            $query->where(function($sub) use ($items) {
                                foreach ($items as $item) {
                                    $sub->orWhere('MAKTX', 'like', "%{$item}%");
                                }
                            });
                        }
                    })
                    ->when($advArbpl, function($query, $term) {
                        $items = array_filter(array_map('trim', explode(',', $term)));
                        if (!empty($items)) {
                            $query->whereIn('ARBPL', $items);
                        }
                    })
                    ->when($advKdauf, function($query, $term) {
                        $items = array_filter(array_map('trim', explode(',', $term)));
                        if (!empty($items)) {
                            $query->where(function($sub) use ($items) {
                                foreach ($items as $item) {
                                    if (str_contains($item, '-')) {
                                        $parts = explode('-', $item);
                                        if (count($parts) >= 2) {
                                            $soPart = trim($parts[0]);
                                            $itemPart = trim($parts[1]);
                                            $sub->orWhere(function($strict) use ($soPart, $itemPart) {
                                                $strict->where('KDAUF', 'like', "%{$soPart}%")
                                                       ->where('KDPOS', 'like', "%{$itemPart}%");
                                            });
                                            continue;
                                        }
                                    }
                                    // Default KDAUF search (padded or like)
                                    // Use LIKE to be safe with partial matches or explicit padding logic if strict
                                    // Assuming numeric input implies KDAUF
                                    if (is_numeric($item)) {
                                         // Try exact match with padding if simple number
                                         $padded = str_pad($item, 10, '0', STR_PAD_LEFT);
                                         $sub->orWhere('KDAUF', $item)->orWhere('KDAUF', $padded);
                                    } else {
                                        $sub->orWhere('KDAUF', 'like', "%{$item}%");
                                    }
                                }
                            });
                        }
                    })
                    // advKdpos block removed
                    // --- ADVANCED FILTERS END ---
                    ->latest('AUFNR')
                    ->paginate(50, ['*'], 'page_total_pro')
                    ->withQueryString();

                return view('Admin.partials.rows_total_pro', ['allProData' => $allProData])->render();
            }

            if ($section === 'so') {
                $searchSo = $request->input('search_so');
                $searchDateSo = $request->input('search_date_so');
                
                $salesOrderData = ProductionTData2::where('WERKSX', $kode)
                    ->when($searchDateSo, function ($query, $date) {
                        if (str_contains($date, ' to ')) {
                            $parts = explode(' to ', $date);
                            if (count($parts) === 2) {
                                return $query->whereBetween('EDATU', [trim($parts[0]), trim($parts[1])]);
                            }
                        }
                        return $query->whereDate('EDATU', $date);
                    })

                    ->when($searchSo, function ($query, $term) {
                         return $query->where(function($q) use ($term) {
                            // Check for specific SO-Item pattern (e.g. 1020003767-90)
                            if (str_contains($term, '-')) {
                                $parts = explode('-', $term);
                                if (count($parts) >= 2) {
                                    $soPart = trim($parts[0]);
                                    $itemPart = trim($parts[1]);
                                    
                                    // Try strict search if looks like valid SO/Item
                                    $q->where(function($sub) use ($soPart, $itemPart) {
                                        // Basic match
                                        $sub->where('KDAUF', 'like', "%{$soPart}%")
                                            ->where('KDPOS', 'like', "%{$itemPart}%");
                                    });
                                    return; // Return early to avoid mixing with general search
                                }
                            }

                            // General Search
                            $q->where('KDAUF', 'like', "%{$term}%")
                              ->orWhere('MATFG', 'like', "%{$term}%")
                              ->orWhere('MAKFG', 'like', "%{$term}%")
                              ->orWhere('NAME1', 'like', "%{$term}%") // Buyer Name
                              ->orWhere('BSTNK', 'like', "%{$term}%"); // PO Number
                         });
                    })
                    ->latest('KDAUF')
                    ->paginate(50, ['*'], 'page_so')
                    ->withQueryString();

                return view('Admin.partials.rows_sales_order', ['salesOrderData' => $salesOrderData])->render();
            }
            
            return '';
        }
        
        $TData1 = ProductionTData1::where('WERKSX', $kode)->count();
        $TData2 = ProductionTData2::where('WERKSX', $kode)->count();
        $TData3 = ProductionTData3::where('WERKSX', $kode)->count();
        
        $searchReservasi = $request->input('search_reservasi');
        $TData4 = ProductionTData4::where('WERKSX', $kode)
            ->when($searchReservasi, function ($query, $term) {
                return $query->where(function($q) use ($term) {
                    $q->where('RSNUM', 'like', "%{$term}%")
                      ->orWhere('MATNR', 'like', "%{$term}%")
                      ->orWhere('MAKTX', 'like', "%{$term}%");
                });
            })
            ->paginate(10, ['*'], 'page_reservasi')
            ->withQueryString();

        $TData4->getCollection()->transform(function ($item) {
            $item->MAKTX = trim(preg_replace('/\s+/', ' ', $item->MAKTX ?? ''));
            return $item;
        });
        
        $outstandingReservasi = ProductionTData4::where('WERKSX', $kode)
                                      ->whereColumn('KALAB', '<', 'BDMNG')
                                      ->count();

        $today = Carbon::today();
        $ongoingPRO = ProductionTData3::where('WERKSX', $kode)
            ->whereDate('GSTRP', $today)
            ->where('STATS', 'REL')
            ->count();

        // 2. Ongoing PRO - Page Name: page_pro
        $searchPro = $request->input('search_pro');
        $ongoingProData = ProductionTData3::where('WERKSX', $kode)
            ->whereDate('GSTRP', $today)
            ->where('STATS', 'REL')
            ->when($searchPro, function ($query, $term) {
                return $query->where(function($q) use ($term) {
                    $q->where('AUFNR', 'like', "%{$term}%")
                      ->orWhere('KDAUF', 'like', "%{$term}%")
                      ->orWhere('MATNR', 'like', "%{$term}%")
                      ->orWhere('MAKTX', 'like', "%{$term}%");
                });
            })
            ->latest('AUFNR')
            ->paginate(10, ['*'], 'page_pro')
            ->withQueryString();

        // 3. All PRO (Total PRO) - Page Name: page_total_pro
        $searchTotalPro = $request->input('search_total_pro');
        
        // Advanced Filters
        $advAufnr = $request->input('adv_aufnr');
        $advMatnr = $request->input('adv_matnr') ?? $request->input('multi_matnr');
        $advMaktx = $request->input('adv_maktx');
        $advArbpl = $request->input('adv_arbpl');
        $advKdauf = $request->input('adv_kdauf');
        // $advKdpos removed

        $searchDateTotalPro = $request->input('search_date_total_pro');
        $searchStatusTotalPro = $request->input('search_status_total_pro');

        $allProData = ProductionTData3::where('WERKSX', $kode)
            ->when($searchStatusTotalPro, function ($query, $status) {
                return $query->where('STATS', $status);
            })
            ->when($searchDateTotalPro, function ($query, $date) {
                if (str_contains($date, ' to ')) {
                    $parts = explode(' to ', $date);
                    if (count($parts) === 2) {
                        return $query->whereBetween('GSTRP', [trim($parts[0]), trim($parts[1])]);
                    }
                }
                return $query->whereDate('GSTRP', $date);
            })
            ->when($searchTotalPro, function ($query, $term) {
                return $query->where(function($q) use ($term) {
                    // Check for specific SO-Item pattern (e.g. 1020003662-60)
                    if (str_contains($term, '-')) {
                        $parts = explode('-', $term);
                        if (count($parts) >= 2) {
                            $soPart = trim($parts[0]);
                            $itemPart = trim($parts[1]);
                            
                            $q->where(function($sub) use ($soPart, $itemPart) {
                                $sub->where('KDAUF', 'like', "%{$soPart}%")
                                    ->where('KDPOS', 'like', "%{$itemPart}%");
                            });
                            return; 
                        }
                    }

                    $q->where('AUFNR', 'like', "%{$term}%")
                      ->orWhere('KDAUF', 'like', "%{$term}%")
                      ->orWhere('MAKTX', 'like', "%{$term}%")
                      ->orWhere('DISPO', 'like', "%{$term}%")
                      ->orWhere('MATNR', 'like', "%{$term}%")
                      ->orWhere('STATS', 'like', "%{$term}%");
                });
            })
            // --- ADVANCED FILTERS START ---
            ->when($advAufnr, function($q, $val) {
                $items = array_filter(array_map('trim', explode(',', $val)));
                $paddedItems = [];
                foreach ($items as $item) {
                    if (is_numeric($item)) {
                        $paddedItems[] = str_pad($item, 12, '0', STR_PAD_LEFT);
                    }
                }

                if (!empty($items)) {
                    $q->where(function($sub) use ($items, $paddedItems) {
                        $sub->whereIn('AUFNR', $items);
                        if (!empty($paddedItems)) {
                            $sub->orWhereIn('AUFNR', $paddedItems);
                        }
                    });
                }
            })
            ->when($advMatnr, function($q, $val) {
                $items = array_filter(array_map('trim', explode(',', $val)));
                $paddedItems = [];
                foreach ($items as $item) {
                    // Default SAP MATNR is 18 chars, kept consistent with AJAX
                    if (is_numeric($item)) {
                        $paddedItems[] = str_pad($item, 18, '0', STR_PAD_LEFT);
                    }
                }
                
                if (!empty($items)) {
                    $q->where(function($sub) use ($items, $paddedItems) {
                        $sub->whereIn('MATNR', $items);
                        if (!empty($paddedItems)) {
                            $sub->orWhereIn('MATNR', $paddedItems);
                        }
                    });
                }
            })
            ->when($advMaktx, function($q, $val) {
                $items = array_filter(array_map('trim', explode(',', $val)));
                if (!empty($items)) {
                    $q->where(function($sub) use ($items) {
                        foreach ($items as $item) {
                            $sub->orWhere('MAKTX', 'like', "%{$item}%");
                        }
                    });
                }
            })
            ->when($advArbpl, function($q, $val) {
                $items = array_filter(array_map('trim', explode(',', $val)));
                if (!empty($items)) $q->whereIn('ARBPL', $items);
            })
            ->when($advKdauf, function($q, $val) {
                $items = array_filter(array_map('trim', explode(',', $val)));
                if (!empty($items)) {
                    $q->where(function($sub) use ($items) {
                        foreach ($items as $item) {
                            if (str_contains($item, '-')) {
                                $parts = explode('-', $item);
                                if (count($parts) >= 2) {
                                    $soPart = trim($parts[0]);
                                    $itemPart = trim($parts[1]);
                                    $sub->orWhere(function($strict) use ($soPart, $itemPart) {
                                        $strict->where('KDAUF', 'like', "%{$soPart}%")
                                               ->where('KDPOS', 'like', "%{$itemPart}%");
                                    });
                                    continue;
                                }
                            }
                            if (is_numeric($item)) {
                                 $padded = str_pad($item, 10, '0', STR_PAD_LEFT);
                                 $sub->orWhere('KDAUF', $item)->orWhere('KDAUF', $padded);
                            } else {
                                $sub->orWhere('KDAUF', 'like', "%{$item}%");
                            }
                        }
                    });
                }
            })
            // advKdpos block removed
            // --- ADVANCED FILTERS END ---
            ->latest('AUFNR')
            ->paginate(10, ['*'], 'page_total_pro')
            ->withQueryString();

        // 4. Sales Order - Page Name: page_so
        $searchSo = $request->input('search_so');
        $searchDateSo = $request->input('search_date_so'); // New Date Filter

        $salesOrderData = ProductionTData2::where('WERKSX', $kode)
            ->when($searchDateSo, function ($query, $date) {
                if (str_contains($date, ' to ')) {
                    $parts = explode(' to ', $date);
                    if (count($parts) === 2) {
                        return $query->whereBetween('EDATU', [trim($parts[0]), trim($parts[1])]);
                    }
                }
                return $query->whereDate('EDATU', $date);
            })
            ->when($searchSo, function ($query, $term) {
                 return $query->where(function($q) use ($term) {
                    // Check for specific SO-Item pattern (e.g. 1020003767-90)
                    if (str_contains($term, '-')) {
                        $parts = explode('-', $term);
                        if (count($parts) >= 2) {
                            $soPart = trim($parts[0]);
                            $itemPart = trim($parts[1]);
                            
                            // Try strict search if looks like valid SO/Item
                            $q->where(function($sub) use ($soPart, $itemPart) {
                                // Basic match
                                $sub->where('KDAUF', 'like', "%{$soPart}%")
                                    ->where('KDPOS', 'like', "%{$itemPart}%");
                            });
                            return; // Return early to avoid mixing with general search
                        }
                    }

                    // General Search
                    $q->where('KDAUF', 'like', "%{$term}%")
                      ->orWhere('MATFG', 'like', "%{$term}%")
                      ->orWhere('MAKFG', 'like', "%{$term}%")
                      ->orWhere('NAME1', 'like', "%{$term}%") // Buyer Name
                      ->orWhere('BSTNK', 'like', "%{$term}%"); // PO Number
                 });
            })
            ->latest('KDAUF')
            ->paginate(10, ['*'], 'page_so')
            ->withQueryString();
        
        // 5. Fetch Unique Statuses for Filter
        $uniqueStatuses = ProductionTData3::where('WERKSX', $kode)
            ->select('STATS')
            ->distinct()
            ->pluck('STATS')
             ->filter() // Remove empty values
            ->sort()
            ->values();

        return view('Admin.dashboard', [
            'TData1' => $TData1, 
            'TData2' => $TData2, 
            'TData3' => $TData3, 
            'TData4' => $TData4,
            'outstandingReservasi' => $outstandingReservasi,
            'ongoingPRO' => $ongoingPRO,
            'ongoingProData' => $ongoingProData,
            'salesOrderData' => $salesOrderData, 
            'allProData' => $allProData, 
            
            'labels' => $labels, 
            'datasets' => $datasets,
            'targetUrls' => $targetUrls,
            
            'doughnutChartLabels' => $doughnutChartLabels,
            'doughnutChartDatasets' => $doughnutChartDatasets,
            
            'lolipopChartLabels' => $lolipopChartLabels,
            'lolipopChartDatasets' => $lolipopChartDatasets,
            
            'kode' => $kode,
            'kategori' => $kategori,
            'nama_bagian' => $nama_bagian,
            'sub_kategori' => $sub_kategori,
            
            // Pass search terms back to view to keep input filled
            'searchReservasi' => $searchReservasi,
            'searchPro' => $searchPro,
            'searchTotalPro' => $searchTotalPro,
            'searchDateTotalPro' => $searchDateTotalPro,
            'searchSo' => $searchSo,
            'searchDateSo' => $searchDateSo,
            
            // Advanced Params
            'advAufnr' => $advAufnr,
            'advMatnr' => $advMatnr,
            'advMaktx' => $advMaktx,
            'advArbpl' => $advArbpl,
            'advKdauf' => $advKdauf,
            // 'advKdpos' removed

            // Filter Options
            'uniqueStatuses' => $uniqueStatuses,
        ]);  
    }


    public function AdminDashboard()
    {
        $plants = collect();
        $allUsers = collect();

        if (Auth::check()) {
            $user = Auth::user();
            $sapId = str_replace('@kmi.local', '', $user->email);
            // Ensure case-insensitive comparison
            $sapIdNormalized = strtolower($sapId);
            
            $sapUser = UserSap::where('user_sap', $sapId)->first();

            if ($sapIdNormalized === 'auto_email') {
                $mappings = MappingTable::with('kodeLaravel')->get(); 
            } elseif ($sapUser) {
                $mappings = MappingTable::where('user_sap_id', $sapUser->id)
                    ->with('kodeLaravel')
                    ->get();
            } else {
                $mappings = collect();
            }

            $plants = $mappings->map(function($mapping) {
                if ($mapping->kodeLaravel) {
                    return (object) [
                        'kode' => $mapping->kodeLaravel->laravel_code,
                        'nama_bagian' => $mapping->kodeLaravel->description,
                        'kategori' => $mapping->kodeLaravel->plant,
                    ];
                }
                return null;
            })->filter()->unique('kode');

            // Grouping Logic
            $groupedPlants = $plants->groupBy(function ($plant) {
                $cat = trim($plant->kategori);
                if ($cat == '1001' || $cat == '1201') {
                    return $cat;
                }
                // Ambil digit pertama lalu tambah '000', misal 1050 -> 1000
                if (strlen($cat) >= 1 && is_numeric($cat)) {
                     return substr($cat, 0, 1) . '000';
                }
                return 'Lainnya';
            })->sortKeys();
        }
        
        return view('dashboard', [
            'groupedPlants' => $groupedPlants ?? collect(), // Use groupedPlants
            'allUsers' => $allUsers
        ]);
    }

    public function getProDetails($status, Request $request)
    {
        $kode_plant = $request->input('kode');
        $nama_bagian = $request->input('nama_bagian');
        $kategori = $request->input('kategori');

        $proDetails = DB::table('production_t_data3')
            ->select(
                'KDAUF as so_number', 
                'KDPOS as so_item', 
                'AUFNR as pro_number', 
                'MATNR as material_code', 
                'MAKTX as description',
                'PWWRK as plant',
                'DISPO as mrp_controller',
                'PSMNG as order_quantity',
                'WEMNG as gr_quantity',
                DB::raw('(PSMNG - WEMNG) AS outs_gr_quantity'),
                'GSTRP as start_date', 
                'GLTRP as end_date',
                'STATS as stats' 
            )
            ->where('WERKSX', $kode_plant) 
            ->where('STATS', $status)
            ->get();

        $htmlTable = view('components.pro_table_detail', compact('proDetails'))->render();

        return response()->json([
            'success' => true,
            'status' => $status,
            'count' => $proDetails->count(),
            'htmlTable' => $htmlTable
        ]);
    }

    public function handleMultiProSearch(Request $request) 
    {
        Log::info("[POST] Menerima request pencarian multi-pro...");

        try {
            $validated = $request->validate([
                'werks_code'        => 'required|string',
                'bagian_name'       => 'required|string',
                'categories_name'   => 'required|string',
                'pro_numbers'       => 'required|string',
            ]);

            $proNumbersArray = json_decode($validated['pro_numbers'], true);
            if (empty($proNumbersArray)) {
                return back()->withErrors(['pro_numbers' => 'Tidak ada nomor PRO yang dimasukkan.']);
            }
            
            Log::info("[POST] Validasi sukses. Redirect ke route GET '...hasil'.");

            return Redirect::route('manufaktur.pro.search.hasil', [
                'werks_code' => $validated['werks_code'],
                'bagian_name' => $validated['bagian_name'],
                'categories_name' => $validated['categories_name'],
                'pro_numbers' => $validated['pro_numbers'],
            ]);

        } catch (\Exception $e) {
            Log::error('[POST] Gagal validasi atau redirect: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function showMultiProResult(Request $request, ProTransaction $proApi) // [BARU] Inject ProController
    {
        Log::info("==================================================");
        Log::info("[GET] Menampilkan halaman hasil pencarian PRO...");
        Log::info("==================================================");

        try {
            $werksCode = $request->query('werks_code');
            $bagianName = $request->query('bagian_name');
            $categoriesName = $request->query('categories_name');
            $proNumbersJson = $request->query('pro_numbers');

            $proNumbersArray = json_decode($proNumbersJson, true);

            if (empty($proNumbersArray) || empty($werksCode)) {
                Log::warn("[GET] Parameter tidak lengkap, kembali ke dashboard.");
                return redirect()->route('manufaktur.dashboard.show');
            }
            
            $sapUser = session('username');
            $sapPass = session('password');
            
            if (empty($sapUser) || empty($sapPass)) {
                Log::error("[GET] Kredensial SAP tidak ditemukan di session.");
                return back()->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
            }

            Log::info("[GET] Memanggil ProController@get_data_pro...");
            $sapData = $proApi->get_data_pro(
                $werksCode, 
                $proNumbersArray, 
                $sapUser, 
                $sapPass
            );

            $workCenters = WorkCenter::where('plant', $werksCode)->orderBy('kode_wc')->get();

            return view('Admin.pro-transaction', [
                'WERKS'          => $werksCode,
                'bagian'         => $bagianName,
                'categories'     => $categoriesName,
                'workCenters'    => $workCenters,
                
                'proDetailsList' => $sapData['proDetailsList'],
                
                'proNumbersSearched' => $proNumbersArray,
                'notFoundProNumbers' => $sapData['notFoundProNumbers'],
            ]);

        } catch (\Exception $e) {
            Log::error('[GET] Gagal menampilkan PRO live: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

}
