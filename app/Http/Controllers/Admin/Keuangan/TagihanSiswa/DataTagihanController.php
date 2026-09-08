<?php

namespace App\Http\Controllers\Admin\Keuangan\TagihanSiswa;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use App\Models\sccttran;
use App\Models\User;
use App\Models\ValidationMessage;
use App\Support\CacheHandler;
use App\Support\FilterHandler;
use App\Support\TagihanPaymentReversal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DataTagihanController extends Controller
{
    public ?string $sekolah = null;
    private string $title = "Keuangan";
    private string $mainTitle = 'Tagihan Siswa';
    private string $dataTitle = 'Data Tagihan Siswa';
    private string $cacheKey = 'data_tagihan';
    private array $allowedFilters = [
        'tanggal-pembuatan' => 'scctbill.FTGLTagihan',
        'periode' => 'scctbill.BILLAC',
        'post' => 'scctbill.BILLNM',
        'kelas' => 'scctcust.DESC02',
        'sekolah' => 'scctcust.CODE01',
        'angkatan' => 'scctcust.DESC04',
        'siswa' => 'scctcust.nmcust',
        'custid' => 'scctcust.CUSTID',
    ];

    public function __construct()
    {
        $key = Str::slug($this->cacheKey) . '_cache_version';
        Cache::add($key, 1);
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $user = Auth::user();
                $this->sekolah = $user->sekolah ?? $user->unit ?? null;
            }
            return $next($request);
        });
    }

    private function applyUnitScope($query, string $table = 'scctcust'): void
    {
        \App\Support\SchoolScope::apply($query, $table, $this->sekolah);
    }

    private function cacheScopeSuffix(): string
    {
        return blank($this->sekolah) ? 'all-units' : 'unit-' . Str::slug((string) $this->sekolah);
    }

    private function applyBelumLunasScope($query, string $billTable = 'scctbill'): void
    {
        $sisaExpr = "CAST(COALESCE({$billTable}.PAYMENTLEFT, {$billTable}.BILLAM - COALESCE({$billTable}.BILLPAID, 0), 0) AS SIGNED)";

        $query->where(function ($q) use ($billTable, $sisaExpr) {
            $q->where("{$billTable}.PAIDST", 0)
                ->orWhereNull("{$billTable}.PAIDST")
                ->orWhereRaw("{$sisaExpr} > 0");
        })->where("{$billTable}.FSTSBolehBayar", 1);
    }

    private function isBlankPaidDate(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $normalized = trim((string) $value);

        if ($normalized === '' || $normalized === '-' || $normalized === '0') {
            return true;
        }

        return preg_match('/^0{4}-0{2}-0{2}/', $normalized) === 1;
    }

    private function resolvePaidDateDisplay(mixed $paidDtRaw, int $billPaid, ?string $aa = null, array $lastPaymentDates = []): ?string
    {
        if ($billPaid <= 0) {
            return null;
        }

        if ($this->isBlankPaidDate($paidDtRaw) && $aa !== null && isset($lastPaymentDates[$aa])) {
            $paidDtRaw = $lastPaymentDates[$aa];
        }

        if ($this->isBlankPaidDate($paidDtRaw)) {
            return null;
        }

        try {
            $parsed = Carbon::parse($paidDtRaw);
            if ($parsed->year < 1971) {
                return null;
            }

            return $parsed->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    public function getColumn()
    {
        return [
            [
                'data' => 'detail_group',
                'name' => '+',
                'orderable' => false,
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center exclude-selection',
                'excludeFromSelection' => true,
                'button' => 'action',
                'buttonText' => '+',
                'buttonClass' => 'btn btn-sm btn-primary btn-detail-group',
                'buttonLink' => '#',
                'noCaption' => false,
                'exportable' => false,
                'duplicate' => false,
            ],
            ['data' => null, 'name' => 'no', 'columnType' => 'row', 'exportable' => true],
            ['data' => 'NOCUST', 'name' => 'NIS', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'NUM2ND', 'name' => 'NO DAFT', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'NOVA', 'name' => 'NO VA', 'exportable' => true],
            ['data' => 'NMCUST', 'name' => 'NAMA', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'CODE02', 'name' => 'Unit', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'DESC02', 'name' => 'Kelas', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'DESC03', 'name' => 'Kelompok', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'BILLAC', 'name' => 'Periode', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'JUMLAH_TAGIHAN', 'name' => 'Jml Item', 'orderable' => true, 'className' => 'text-end', 'exportable' => true],
            ['data' => 'BILLAM_TOTAL', 'name' => 'Jumlah Tagihan', 'orderable' => true, 'columnType' => 'currency', 'className' => 'text-end', 'exportable' => true],
            ['data' => 'BILLPAID', 'name' => 'Jumlah Terbayar', 'orderable' => true, 'columnType' => 'currency', 'className' => 'text-end', 'exportable' => true],
            ['data' => 'SISA', 'name' => 'Sisa Tagihan', 'orderable' => true, 'columnType' => 'currency', 'className' => 'text-end', 'exportable' => true],
        ];
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['columnsUrl'] = $this->columnsUrl();
        $data['datasUrl'] = $this->datasUrl();
        $data['billsUrl'] = route('admin.keuangan.tagihan-siswa.data-tagihan.get-bills-for-group');
        $data['tableColumns'] = $this->getColumn();
        $data['post'] = mst_tagihan::select(['tagihan'])->get();
        $data['thn_aka'] = mst_thn_aka::select(['thn_aka'])
            ->where('thn_aka', '!=', null)
            ->orderBy('thn_aka', 'desc')->get();
        $data['periode'] = scctbill::query()
            ->whereNotNull('BILLAC')
            ->where('BILLAC', '!=', '')
            ->distinct()
            ->orderBy('BILLAC', 'desc')
            ->pluck('BILLAC');
        $data['sekolah'] = mst_sekolah::when($this->sekolah, function ($query) {
            $query->where(function ($q) {
                $q->where("CODE01", $this->sekolah)
                    ->orWhere("DESC01", $this->sekolah);
            });
        })->get();
        $data['kelas'] = mst_kelas::dropdownQuery($this->sekolah)
            ->orderBy('unit')
            ->orderByRaw("CASE WHEN jenjang REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, jenjang")
            ->orderByRaw("CASE WHEN kelas REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, kelas")
            ->get();
        $data['tanda_tangan'] = User::getTandaTanganBase64();

        return view('admin.keuangan.tagihan_siswa.data_tagihan', $data);
    }

    private function columnsUrl(): string
    {
        return route('admin.keuangan.tagihan-siswa.data-tagihan.get-column');
    }

    private function datasUrl(): string
    {
        return route('admin.keuangan.tagihan-siswa.data-tagihan.get-data');
    }

    public function ubahUrutan($id, Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'urutan_tagihan' => ['required', 'in:naik,turun'],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) {
            if ($validator->errors()->has('tagihan.nominal_bayar.*') || $validator->errors()->has('tagihan.post.*')) {
                return response()->json(['message' => 'Silahkan cek tagihan yang anda pilih,<br> pastikan telah mengisi nominal pembayaran'], 422);
            }

            return response()->json(['message' => $validator->errors()->first(), 'error' => $validator->errors()], 422);
        }

        $tagihan = scctbill::where('AA', $id)
            ->where('FSTSBolehBayar', '=', 1)
            ->where('PAIDST', '=', 0)
            ->first();
        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        $custId = (string) $tagihan->CUSTID;
        $aa = (string) $tagihan->AA;
        $currentUrut = (int) ($tagihan->FUrutan ?? 0);

        if ($currentUrut <= 0) {
            return response()->json([
                'message' => 'Tagihan dengan urutan 0 tidak dapat dinaikkan atau diturunkan.',
            ], 422);
        }

        if ($request->urutan_tagihan === 'naik' && $currentUrut <= 1) {
            return response()->json(['message' => 'Urutan sudah paling atas.'], 422);
        }

        if ($request->urutan_tagihan === 'turun') {
            $maxUrut = (int) scctbill::where('CUSTID', $custId)->max('FUrutan');
            if ($currentUrut >= $maxUrut) {
                return response()->json(['message' => 'Urutan sudah paling bawah.'], 422);
            }
        }

        try {
            DB::connection('DATA_MYSQL')->beginTransaction();

            if ($request->urutan_tagihan === 'naik') {
                DB::connection('DATA_MYSQL')->select('CALL UpdateUrutUP(?, ?)', [$custId, $aa]);
            } else {
                DB::connection('DATA_MYSQL')->select('CALL UpdateUrutDOWN(?, ?)', [$custId, $aa]);
            }

            Cache::increment(Str::slug($this->cacheKey) . '_cache_version');
            DB::connection('DATA_MYSQL')->commit();

            $label = $request->urutan_tagihan === 'naik' ? 'dinaikkan' : 'diturunkan';

            return response()->json([
                'message' => "Urutan tagihan berhasil {$label}.",
            ], 200);
        } catch (\Throwable $e) {
            DB::connection('DATA_MYSQL')->rollBack();

            return response()->json([
                'message' => 'Gagal mengubah urutan tagihan: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function hapusTagihan($id, Request $request)
    {
        $custId = $request->input('user_id') ?? $request->input('custid');

        $tagihan = scctbill::where('AA', $id)
            ->where('FSTSBolehBayar', 1)
            ->when($custId, fn($q) => $q->where('CUSTID', $custId))
            ->first();

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        if (!$this->canHapusTagihan($tagihan)) {
            return response()->json([
                'message' => 'Tagihan tidak dapat dihapus. Syarat: PAIDST = 0, INSTALLMENT = 0, dan belum pernah ada pembayaran.',
            ], 422);
        }

        try {
            DB::connection('DATA_MYSQL')->beginTransaction();
            $tagihan->update(['FSTSBolehBayar' => 0]);
            DB::connection('DATA_MYSQL')->commit();

            Cache::increment(Str::slug($this->cacheKey) . '_cache_version');

            return response()->json(['message' => 'Tagihan berhasil dihapus!'], 200);
        } catch (\Throwable $e) {
            DB::connection('DATA_MYSQL')->rollBack();

            return response()->json([
                'message' => 'Gagal menghapus tagihan!',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    private function canHapusTagihan(scctbill $tagihan): bool
    {
        if ((int) ($tagihan->PAIDST ?? 0) !== 0) {
            return false;
        }

        if ((int) ($tagihan->INSTALLMENT ?? 0) !== 0) {
            return false;
        }

        if ((int) ($tagihan->BILLPAID ?? 0) !== 0) {
            return false;
        }

        return !app(TagihanPaymentReversal::class)->hasBillPayments($tagihan);
    }

    public function destroy($id, Request $request)
    {
        $custId = $request->input('user_id') ?? $request->input('custid');

        $tagihan = scctbill::where('AA', $id)
            ->where('FSTSBolehBayar', '=', 1)
            ->where('PAIDST', '=', 0)
            ->when($custId, fn($q) => $q->where('CUSTID', $custId))
            ->first();

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        $siswa = scctcust::where('CUSTID', $tagihan->CUSTID)->first();
        if (!$siswa) {
            return response()->json(['message' => 'Siswa tidak ditemukan!'], 422);
        }

        try {
            $reversal = app(TagihanPaymentReversal::class);

            if (!$reversal->hasBillPayments($tagihan)) {
                return response()->json([
                    'message' => 'Tagihan belum ada cicilan/pembayaran. Untuk menghapus tagihan gunakan menu Hapus Tagihan.',
                ], 422);
            }

            $reversal->reverseLastPayment($tagihan, $request);

            Cache::increment(Str::slug($this->cacheKey) . '_cache_version');

            return response()->json(['message' => 'Reversal berhasil!'], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal memproses tagihan!',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function cetak(Request $request)
    {
        ini_set('max_execution_time', 300);

        try {
            $filterQuery = $this->resolveTagihanFilterQuery($request);
            $selectedBillNames = $this->resolveSelectedBillNames($request);

            $mstTagihanQuery = mst_tagihan::query()
                ->select('urut', 'tagihan', 'kode')
                ->orderBy('urut');

            if (!empty($selectedBillNames)) {
                $mstTagihanQuery->where(function ($q) use ($selectedBillNames) {
                    foreach ($selectedBillNames as $name) {
                        $sanitized = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], (string) $name);
                        $q->orWhere('tagihan', 'like', '%' . $sanitized . '%');
                    }
                });
            }

            $mstTagihan = $mstTagihanQuery->get();
            if ($mstTagihan->isEmpty()) {
                return response()->json(['message' => 'Data Kosong'], 422);
            }

            $records = scctbill::query()
                ->join('scctcust', 'scctcust.CUSTID', '=', 'scctbill.CUSTID')
                ->select([
                    'scctcust.nmcust',
                    'scctcust.nocust',
                    'scctbill.AA',
                    'scctbill.CUSTID',
                    'scctbill.BILLNM',
                    'scctbill.BILLAM',
                    'scctbill.BILLPAID',
                    'scctbill.PAYMENTLEFT',
                    'scctbill.PAIDST',
                    'scctbill.PAIDDT',
                    'scctbill.BTA',
                    'scctbill.FIDBANK',
                    'scctbill.FUrutan',
                    'scctbill.TRANSNO',
                    'scctcust.CODE02',
                    'scctcust.DESC02',
                ])
                ->whereIn('scctbill.BILLNM', $mstTagihan->pluck('tagihan'))
                ->where(function ($q) {
                    $this->applyBelumLunasScope($q);
                })
                ->whereRaw('CAST(COALESCE(scctcust.STCUST, 0) AS SIGNED) = 1')
                ->when($filterQuery, function ($query) use ($filterQuery) {
                    $filterQuery($query);
                });

            $this->applyUnitScope($records);

            $records = $records
                ->orderBy('scctbill.CUSTID', 'desc')
                ->orderBy('scctbill.BILLAC', 'desc')
                ->orderBy('scctbill.PAIDDT', 'desc')
                ->get();

            $lastPaymentDates = $this->getLastPaymentDatesForBills($records);

            $records = $records->map(function ($row) use ($lastPaymentDates) {
                $aa = (string) ($row->AA ?? '');
                $billPaid = (int) ($row->BILLPAID ?? 0);
                $billAm = (int) ($row->BILLAM ?? 0);

                $row->PAIDDT = $this->resolvePaidDateDisplay($row->PAIDDT, $billPaid, $aa, $lastPaymentDates);

                if ($row->PAYMENTLEFT === null || $row->PAYMENTLEFT === '') {
                    $row->PAYMENTLEFT = max(0, $billAm - $billPaid);
                }

                $row->BILLPAID = $billPaid;

                return $row;
            });

            $groupedByBill = $records->groupBy('BILLNM');

            $posts = $mstTagihan->map(function ($item) use ($groupedByBill) {
                $item->tagihans = ($groupedByBill->get($item->tagihan) ?? collect())
                    ->map(fn($row) => $row->toArray())
                    ->values()
                    ->all();

                return $item;
            });

            if ($posts->every(fn($post) => empty($post->tagihans))) {
                return response()->json(['message' => 'Data Kosong'], 422);
            }

            $pdf = Pdf::loadView('cetak.data-tagihan', ['posts' => $posts])->setPaper('a4', 'landscape');

            return $pdf->download('rekap-tagihan.pdf');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Tidak dapat mencetak rekap', 'error' => $e->getMessage(), 'e' => $e], 422);
        }
    }

    public function cetakKartuSiswa(Request $request)
    {
        $filter = $request;
        if (!$filter['custid']) return response()->json(['error' => 'Siswa tidak ditemukan']);

        $siswa = scctcust::where('custid', $filter['custid'])->first();
        if (!$siswa) return response()->json(['error' => 'Siswa tidak ditemukan']);

        try {
            $query = scctbill::join('scctcust', 'scctcust.CUSTID', '=', 'scctbill.CUSTID')
                ->select($this->billSelectColumns())
                ->selectRaw('CAST(COALESCE(scctbill.FUrutan, 0) AS SIGNED) AS FUrutan')
                ->where('scctbill.CUSTID', $filter['custid']);

            $this->applyBelumLunasScope($query);
            $query->whereRaw('CAST(COALESCE(scctcust.STCUST, 0) AS SIGNED) = 1');

            $this->applyUnitScope($query);

            $rows = $query
                ->orderBy('scctbill.BILLAC', 'desc')
                ->orderByRaw('CAST(COALESCE(scctbill.FUrutan, 0) AS SIGNED) ASC')
                ->get();

            $lastPaymentDates = $this->getLastPaymentDatesForBills($rows);

            $tagihans = $rows->map(fn($item) => $this->mapBillRow($item, $lastPaymentDates))->values()->all();

            if (!$tagihans) return response()->json(['message' => 'Tagihan Tidak Ditemukan'], 422);
            return response()->json(['tagihans' => $tagihans, 'siswa' => $siswa], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Tagihan Tidak Ditemukan', 'error' => $e->getMessage()], 422);
        }
    }

    private function billSelectColumns(): array
    {
        return array_unique([
            'scctcust.nocust as NOCUST',
            'scctcust.nmcust as NMCUST',
            'scctcust.NUM2ND',
            'scctcust.CODE02',
            'scctcust.DESC02',
            'scctcust.DESC03',
            'scctbill.AA',
            'scctbill.BILLNM',
            'scctbill.BILLAC',
            'scctbill.BILLAM',
            'scctbill.BILLPAID',
            'scctbill.PAYMENTLEFT',
            'scctbill.PAIDST',
            'scctbill.PAIDDT',
            'scctbill.INSTALLMENT',
            'scctbill.TRANSNO as BILL_TRANSNO',
            'scctbill.BTA',
            'scctbill.FIDBANK',
            'scctbill.CUSTID',
        ]);
    }

    public function getData(Request $request)
    {
        try {
            return $this->buildGetDataResponse($request);
        } catch (\Throwable $e) {
            \Log::error('DataTagihan Error: ' . $e->getMessage());
            \Log::error('DataTagihan Error Trace: ' . $e->getTraceAsString());

            return response()->json([
                'draw' => intval($request->get('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Gagal memuat data tagihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function buildGetDataResponse(Request $request)
    {
        \Log::info('=== START buildGetDataResponse ===');

        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length");

        $columnIndex_arr = $request->get('order', []);
        $columnName_arr = $request->get('columns', []);
        $order_arr = $request->get('order', []);
        $search_arr = $request->get('search', []);
        $searchValue = $search_arr['value'] ?? '';

        \Log::info('Search Value:', ['search' => $searchValue]);
        \Log::info('Filter Input:', ['filter' => $request->input('filter', [])]);

        $columnName = 'scctbill.BILLAC';
        $columnSortOrder = 'desc';
        $userOrdered = false;
        $nonSortableData = ['detail_group', 'NOVA'];

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'] ?? null;
            $requestedData = ($columnIndex !== null && isset($columnName_arr[$columnIndex]['data']))
                ? $columnName_arr[$columnIndex]['data']
                : null;

            if (
                $requestedData
                && !in_array($requestedData, $nonSortableData, true)
                && $requestedData !== 'no'
            ) {
                $userOrdered = true;
                $columnName = $requestedData;
                $columnSortOrder = strtolower($order_arr[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            }
        }

        $sortableColumns = [
            'BILLAC' => 'scctbill.BILLAC',
            'JUMLAH_TAGIHAN' => 'JUMLAH_TAGIHAN',
            'BILLAM_TOTAL' => 'BILLAM_TOTAL',
            'BILLPAID' => 'BILLPAID',
            'SISA' => 'SISA',
            'NOCUST' => 'scctcust.nocust',
            'NUM2ND' => 'scctcust.NUM2ND',
            'NMCUST' => 'scctcust.nmcust',
            'CODE02' => 'scctcust.CODE02',
            'DESC02' => 'scctcust.DESC02',
            'DESC03' => 'scctcust.DESC03',
        ];
        if (isset($sortableColumns[$columnName])) {
            $columnName = $sortableColumns[$columnName];
        }

        $filterQuery = $this->resolveTagihanFilterQuery($request);
        $filter = $request->input('filter', []);

        $whereAny = [
            'scctcust.nmcust',
            'scctcust.nocust',
            'scctcust.NUM2ND',
            'scctcust.DESC02',
            'scctcust.DESC03',
            'scctbill.BILLAC',
        ];

        $groupBy = [
            'scctcust.CUSTID',
            'scctbill.BILLAC',
            'scctcust.nocust',
            'scctcust.NUM2ND',
            'scctcust.nmcust',
            'scctcust.CODE02',
            'scctcust.DESC02',
            'scctcust.DESC03',
        ];

        $query = scctbill::join('scctcust', 'scctcust.CUSTID', '=', 'scctbill.CUSTID')
            ->select([
                'scctcust.CUSTID',
                'scctcust.nocust as NOCUST',
                'scctcust.NUM2ND',
                'scctcust.nmcust as NMCUST',
                'scctcust.CODE02',
                'scctcust.DESC02',
                'scctcust.DESC03',
                'scctbill.BILLAC',
            ])
            ->selectRaw('COUNT(*) as JUMLAH_TAGIHAN')
            ->selectRaw('SUM(CAST(COALESCE(scctbill.BILLAM, 0) AS DECIMAL(18,2))) as BILLAM_TOTAL')
            ->selectRaw('SUM(CAST(COALESCE(scctbill.BILLPAID, 0) AS DECIMAL(18,2))) as BILLPAID')
            ->selectRaw('SUM(CAST(COALESCE(scctbill.PAYMENTLEFT, scctbill.BILLAM - COALESCE(scctbill.BILLPAID, 0), 0) AS DECIMAL(18,2))) as SISA');

        \Log::info('Before applyBelumLunasScope');

        $this->applyBelumLunasScope($query);

        \Log::info('After applyBelumLunasScope');

        $query
            ->whereRaw('CAST(COALESCE(scctcust.STCUST, 0) AS SIGNED) = 1')
            ->when(!blank($searchValue), function ($query) use ($whereAny, $searchValue) {
                $query->where(function ($q) use ($whereAny, $searchValue) {
                    $sanitizeSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $searchValue);
                    foreach ($whereAny as $column) {
                        $q->orWhere($column, 'like', '%' . $sanitizeSearch . '%');
                    }
                });
            })
            ->when($filterQuery, function ($query) use ($filterQuery) {
                $filterQuery($query);
            })
            ->groupBy($groupBy);

        \Log::info('Before applyUnitScope, sekolah: ' . ($this->sekolah ?? 'null'));

        $this->applyUnitScope($query);

        \Log::info('After applyUnitScope');

        \Log::info('SQL QUERY:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $totalRecords = $this->total();

        \Log::info('Total Records: ' . $totalRecords);

        $cacheFilter = array_merge($filter, ['_scope' => $this->cacheScopeSuffix()]);

        $totalRecordswithFilter = Cache::remember(
            CacheHandler::cacheKey($this->cacheKey, 'total_records_with_filter', $cacheFilter, $searchValue),
            now()->addMinutes(10),
            fn() => (clone $query)->count()
        );

        \Log::info('Total Records with Filter: ' . $totalRecordswithFilter);

        $cacheKey = CacheHandler::cacheKey($this->cacheKey, 'sum_tagihan', $cacheFilter, $searchValue);

        $totalTagihan = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn() => (clone $query)->sum(DB::raw('CAST(COALESCE(scctbill.PAYMENTLEFT, scctbill.BILLAM - COALESCE(scctbill.BILLPAID, 0), 0) AS DECIMAL(18,2))'))
        );

        $rowperpage = $rowperpage == "poll" ? $totalRecords : $rowperpage;
        $recordsQuery = clone $query;

        if ($userOrdered) {
            $dir = $columnSortOrder === 'desc' ? 'DESC' : 'ASC';
            $recordsQuery->orderBy(DB::raw($columnName), $dir);
            $recordsQuery
                ->orderBy('scctcust.nocust', 'asc');
        } else {
            $recordsQuery
                ->orderBy('scctbill.BILLAC', 'desc')
                ->orderBy('scctcust.nocust', 'asc');
        }

        $rows = $recordsQuery
            ->skip($start)
            ->take($rowperpage)
            ->get();

        \Log::info('Rows count from DB: ' . $rows->count());
        \Log::info('Rows data sample:', ['first_row' => $rows->first() ? $rows->first()->toArray() : null]);

        $records = $rows
            ->map(function ($item) {
                $row = $item->toArray();
                $nocust = $row['NOCUST'] ?? null;

                $result = [
                    'CUSTID' => $row['CUSTID'] ?? null,
                    'NOCUST' => ($nocust && $nocust !== '-') ? $nocust : null,
                    'NUM2ND' => ($row['NUM2ND'] ?? null) && $row['NUM2ND'] !== '-' ? $row['NUM2ND'] : null,
                    'NOVA' => ($nocust && $nocust !== '-') ? scctcust::showVA($nocust) : null,
                    'NMCUST' => $row['NMCUST'] ?? null,
                    'CODE02' => $row['CODE02'] ?? null,
                    'DESC02' => $row['DESC02'] ?? null,
                    'DESC03' => $row['DESC03'] ?? null,
                    'BILLAC' => $row['BILLAC'] ?? null,
                    'JUMLAH_TAGIHAN' => (int) ($row['JUMLAH_TAGIHAN'] ?? 0),
                    'BILLAM_TOTAL' => $row['BILLAM_TOTAL'] ?? 0,
                    'BILLPAID' => $row['BILLPAID'] ?? 0,
                    'SISA' => $row['SISA'] ?? 0,
                    'detail_group' => '', // ← UBAH KE EMPTY STRING
                ];

                \Log::info('Mapped row:', ['row' => $result]);

                return $result;
            })
            ->values()
            ->all();

        \Log::info('Final records count: ' . count($records));
        \Log::info('Final records sample:', ['sample' => count($records) > 0 ? $records[0] : null]);

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecordswithFilter ?? 0,
            "data" => $records ?? [],
            'totals' => [
                'tagihan' => ['location' => 13, 'value' => $totalTagihan, 'columnType' => 'currency'],
            ]
        );

        \Log::info('Response data count: ' . count($response['data']));
        \Log::info('=== END buildGetDataResponse ===');

        return response()->json($response);
    }

    private function mapBillRow($item, array $lastPaymentDates): array
    {
        $row = $item instanceof \Illuminate\Database\Eloquent\Model ? $item->toArray() : (array) $item;
        $get = static fn(string $key) => $row[$key] ?? $row[strtolower($key)] ?? null;

        $aa = (string) ($get('AA') ?? '');
        $billPaid = (int) ($get('BILLPAID') ?? 0);
        $paidDtRaw = $get('PAIDDT');
        $paidDtDisplay = $this->resolvePaidDateDisplay($paidDtRaw, $billPaid, $aa, $lastPaymentDates);
        $furutan = $get('FUrutan');
        $canHapus = $item instanceof scctbill ? $this->canHapusTagihan($item) : false;

        return [
            'AA' => $get('AA'),
            'item_id' => $get('AA'),
            'CUSTID' => $get('CUSTID'),
            'BILLNM' => $get('BILLNM'),
            'BILLAC' => $get('BILLAC'),
            'BILLAM_TOTAL' => $get('BILLAM'),
            'BILLAM' => $get('PAYMENTLEFT'),
            'BILLPAID' => $billPaid,
            'PAYMENTLEFT' => $get('PAYMENTLEFT'),
            'PAIDST' => $get('PAIDST'),
            'INSTALLMENT' => (int) ($get('INSTALLMENT') ?? 0),
            'PAIDDT' => $paidDtDisplay,
            'PAIDDT_ISO' => $paidDtDisplay,
            'FIDBANK' => $get('FIDBANK'),
            'BTA' => $get('BTA'),
            'BILL_TRANSNO' => $get('BILL_TRANSNO') ?? $get('TRANSNO'),
            'FUrutan' => ($furutan === null || $furutan === '') ? '0' : (string) (int) $furutan,
            'delete' => $billPaid > 0,
            'hapus' => $canHapus,
        ];
    }

    public function getBillsForGroup(Request $request)
    {
        $custId = $request->input('custid');
        $billac = $request->input('billac');

        if (blank($custId) || blank($billac)) {
            return response()->json(['message' => 'Parameter tidak lengkap'], 422);
        }

        $query = scctbill::join('scctcust', 'scctcust.CUSTID', '=', 'scctbill.CUSTID')
            ->select($this->billSelectColumns())
            ->selectRaw('CAST(COALESCE(scctbill.FUrutan, 0) AS SIGNED) AS FUrutan')
            ->where('scctbill.CUSTID', $custId)
            ->where('scctbill.BILLAC', $billac);

        $this->applyBelumLunasScope($query);
        $query
            ->whereRaw('CAST(COALESCE(scctcust.STCUST, 0) AS SIGNED) = 1');

        $this->applyUnitScope($query);

        $rows = $query
            ->orderByRaw('CAST(COALESCE(scctbill.FUrutan, 0) AS SIGNED) ASC')
            ->orderBy('scctbill.BILLNM', 'asc')
            ->get();

        $lastPaymentDates = $this->getLastPaymentDatesForBills($rows);

        $records = $rows->map(fn($item) => $this->mapBillRow($item, $lastPaymentDates))->values()->all();

        return response()->json(['data' => $records], 200);
    }

    private function getLastPaymentDatesForBills(iterable $rows): array
    {
        $dates = [];
        $pending = [];

        foreach ($rows as $item) {
            $row = $item instanceof \Illuminate\Database\Eloquent\Model ? $item->toArray() : (array) $item;
            $get = static fn(string $key) => $row[$key] ?? $row[strtolower($key)] ?? null;

            $aa = (string) ($get('AA') ?? '');
            if ($aa === '') {
                continue;
            }

            $billPaid = (int) ($get('BILLPAID') ?? 0);
            if ($billPaid <= 0) {
                continue;
            }

            $pending[$aa] = [
                'custid' => $get('CUSTID'),
                'transno' => $get('BILL_TRANSNO') ?? $get('TRANSNO'),
                'billnm' => $get('BILLNM'),
            ];
        }

        if ($pending === []) {
            return [];
        }

        $paymentScope = function ($query) {
            $query->where(function ($q) {
                $q->whereRaw('CAST(COALESCE(KREDIT, 0) AS SIGNED) > 0')
                    ->orWhereRaw('CAST(COALESCE(DEBET, 0) AS SIGNED) > 0');
            });
        };

        $byBillId = sccttran::query()
            ->whereIn('BILLID', array_keys($pending))
            ->where($paymentScope)
            ->selectRaw('BILLID, MAX(TRXDATE) as last_trx')
            ->groupBy('BILLID')
            ->pluck('last_trx', 'BILLID');

        foreach ($byBillId as $billId => $trxDate) {
            if ($trxDate) {
                $dates[(string) $billId] = $trxDate;
            }
        }

        $transnoMap = [];
        foreach ($pending as $aa => $meta) {
            if (isset($dates[$aa])) {
                continue;
            }
            $transno = trim((string) ($meta['transno'] ?? ''));
            if ($transno !== '' && $transno !== '-') {
                $transnoMap[$transno][] = $aa;
            }
        }

        if ($transnoMap !== []) {
            $byTransno = sccttran::query()
                ->whereIn('TRANSNO', array_keys($transnoMap))
                ->where($paymentScope)
                ->selectRaw('TRANSNO, MAX(TRXDATE) as last_trx')
                ->groupBy('TRANSNO')
                ->pluck('last_trx', 'TRANSNO');

            foreach ($byTransno as $transno => $trxDate) {
                if (!$trxDate) {
                    continue;
                }
                foreach ($transnoMap[$transno] ?? [] as $aa) {
                    $dates[$aa] ??= $trxDate;
                }
            }
        }

        foreach ($pending as $aa => $meta) {
            if (isset($dates[$aa])) {
                continue;
            }

            $custId = $meta['custid'] ?? null;
            $billNm = trim((string) ($meta['billnm'] ?? ''));
            if (blank($custId) || $billNm === '') {
                continue;
            }

            $trxDate = sccttran::query()
                ->where('CUSTID', $custId)
                ->whereRaw('UPPER(TRIM(BILLTARGET)) = UPPER(TRIM(?))', [$billNm])
                ->where($paymentScope)
                ->max('TRXDATE');

            if ($trxDate) {
                $dates[$aa] = $trxDate;
            }
        }

        return $dates;
    }

    private function getTransactionLogsForBill($custId, $aa, $billTransNo = null, $billName = null): array
    {
        if (blank($aa)) {
            return [];
        }

        try {
            $primaryLogs = sccttran::query()
                ->where('BILLID', $aa)
                ->orderBy('TRXDATE', 'desc')
                ->get(['TRXDATE', 'METODE', 'DEBET', 'KREDIT', 'FIDBANK', 'NOREFF', 'TRANSNO']);

            $logsCollection = $primaryLogs;
            if ($logsCollection->isEmpty()) {
                $logsCollection = sccttran::query()
                    ->where(function ($q) use ($billTransNo, $billName) {
                        if (!blank($billTransNo) && (string) $billTransNo !== '-') {
                            $q->orWhere('TRANSNO', $billTransNo);
                        }
                        if (!blank($billName)) {
                            $q->orWhereRaw('UPPER(TRIM(BILLTARGET)) = UPPER(TRIM(?))', [$billName]);
                        }
                    })
                    ->when(!blank($custId), function ($q) use ($custId) {
                        $q->where('CUSTID', $custId);
                    })
                    ->orderBy('TRXDATE', 'desc')
                    ->get(['TRXDATE', 'METODE', 'DEBET', 'KREDIT', 'FIDBANK', 'NOREFF', 'TRANSNO']);
            }

            return $logsCollection
                ->map(function ($trx) {
                    return [
                        'trxdate' => $trx->TRXDATE
                            ? Carbon::parse($trx->TRXDATE)->format('d-m-Y H:i:s')
                            : null,
                        'metode' => $trx->METODE,
                        'debet' => (int) ($trx->DEBET ?? 0),
                        'kredit' => (int) ($trx->KREDIT ?? 0),
                        'fidbank' => $trx->FIDBANK,
                        'noreff' => $trx->NOREFF,
                        'transno' => $trx->TRANSNO,
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getTransLog($id, Request $request)
    {
        $custId = $request->input('custid');
        $billTransNo = $request->input('bill_transno');
        $billName = $request->input('billnm');

        if (blank($custId)) {
            $bill = scctbill::query()
                ->where('AA', $id)
                ->select(['CUSTID', 'TRANSNO', 'BILLNM'])
                ->first();
            if ($bill) {
                $custId = $bill->CUSTID;
                $billTransNo = $billTransNo ?: $bill->TRANSNO;
                $billName = $billName ?: $bill->BILLNM;
            }
        }

        $logs = $this->getTransactionLogsForBill($custId, $id, $billTransNo, $billName);

        if (empty($logs)) {
            $logs = sccttran::query()
                ->where('BILLID', $id)
                ->orderBy('TRXDATE', 'desc')
                ->get(['TRXDATE', 'METODE', 'DEBET', 'KREDIT', 'FIDBANK', 'NOREFF', 'TRANSNO'])
                ->map(function ($trx) {
                    return [
                        'trxdate' => $trx->TRXDATE
                            ? Carbon::parse($trx->TRXDATE)->format('d-m-Y H:i:s')
                            : null,
                        'metode' => $trx->METODE,
                        'debet' => (int) ($trx->DEBET ?? 0),
                        'kredit' => (int) ($trx->KREDIT ?? 0),
                        'fidbank' => $trx->FIDBANK,
                        'noreff' => $trx->NOREFF,
                        'transno' => $trx->TRANSNO,
                    ];
                })
                ->values()
                ->all();
        }

        return response()->json(['logs' => $logs], 200);
    }

    public function total(): int
    {
        $scopeKey = $this->cacheScopeSuffix();

        return Cache::remember(
            "{$this->cacheKey}:total_all_data:{$scopeKey}",
            now()->addMinutes(10),
            function () {
                $query = scctbill::join('scctcust', 'scctcust.CUSTID', '=', 'scctbill.CUSTID')
                    ->groupBy('scctcust.CUSTID', 'scctbill.BILLAC');

                $this->applyBelumLunasScope($query);
                $query
                    ->whereRaw('CAST(COALESCE(scctcust.STCUST, 0) AS SIGNED) = 1');

                $this->applyUnitScope($query);

                return $query->count();
            }
        );
    }

    private function resolveSelectedBillNames(Request $request): array
    {
        $rawPosts = $request->input('filter.post', []);
        if (!is_array($rawPosts) && !is_null($rawPosts) && $rawPosts !== '') {
            $rawPosts = [$rawPosts];
        }

        if (!is_array($rawPosts)) {
            return [];
        }

        return array_values(array_filter(
            $rawPosts,
            fn($item) => !is_null($item) && $item !== '' && strtolower((string) $item) !== 'all'
        ));
    }

    private function resolveTagihanFilterQuery(Request $request): ?\Closure
    {
        $filters = [];

        $filter = FilterHandler::resolveFilters($request->input('filter'), $this->allowedFilters);
        if (!is_array($filter)) {
            $filter = [];
        }

        $postValues = $this->resolveSelectedBillNames($request);
        if (!empty($postValues)) {
            $filter['scctbill.BILLNM'] = $postValues;
        }

        if (!$filter) {
            return null;
        }

        foreach ($filter as $key => $val) {
            switch ($key) {
                case 'scctbill.FTGLTagihan':
                    if (preg_match('/^\d{2}-\d{2}-\d{4} [-\/~] \d{2}-\d{2}-\d{4}$/', $val)) {
                        $val = preg_replace('/[-\/~]/', '-', $val);

                        list($startDate, $endDate) = explode(' - ', $val);
                        $startDate = Carbon::createFromFormat('d-m-Y', $startDate)->startOfDay();
                        $endDate = Carbon::createFromFormat('d-m-Y', $endDate)->endOfDay();
                        if ($startDate && $endDate) {
                            ($key) && $filters[] = [$key, $startDate, $endDate, 'whereBetween'];
                        }
                    }
                    break;
                case 'scctcust.nmcust':
                    $rawVal = trim((string) $val);
                    if ($rawVal === '') {
                        break;
                    }
                    if (is_numeric($rawVal)) {
                        $filters[] = ['whereRaw', '(scctcust.nocust LIKE ? OR scctcust.NUM2ND LIKE ? OR CAST(scctcust.CUSTID AS CHAR) LIKE ?)', ['%' . $rawVal . '%', '%' . $rawVal . '%', '%' . $rawVal . '%']];
                    } else {
                        $sanitized = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $rawVal);
                        $filters[] = ['whereRaw', '(scctcust.nmcust LIKE ? OR scctcust.nocust LIKE ?)', ['%' . $sanitized . '%', '%' . $sanitized . '%']];
                    }
                    break;
                case 'scctbill.BILLNM':
                    if (is_array($val)) {
                        $billNames = array_values(array_filter($val, fn($item) => !is_null($item) && $item !== '' && strtolower((string) $item) !== 'all'));
                        if (!empty($billNames)) {
                            $filters[] = ['scctbill.BILLNM', 'in', $billNames];
                        }
                    } else {
                        $name = trim((string) $val);
                        if ($name !== '') {
                            $filters[] = ['scctbill.BILLNM', '=', $name];
                        }
                    }
                    break;
                case 'scctbill.BILLAC':
                    $filters[] = ['scctbill.BILLAC', '=', trim((string) $val)];
                    break;
                case 'scctcust.DESC02':
                    $delimiter = str_contains((string) $val, '~~') ? '~~' : '~~';
                    $parts = explode($delimiter, (string) $val);
                    if (count($parts) == 3) {
                        if (!$this->sekolah) {
                            $filters[] = ['scctcust.CODE02', '=', $parts[0]];
                        }
                        $filters[] = ['scctcust.DESC02', '=', $parts[1]];
                        $filters[] = ['scctcust.DESC03', '=', $parts[2]];
                    }
                    break;
                case 'scctcust.CODE02':
                case 'scctcust.CODE01':
                    $unit = trim((string) $val);
                    if ($unit !== '') {
                        $filters[] = ['scctcust.CODE01', '=', $unit];
                    }
                    break;
                case 'scctcust.DESC04':
                    $filters[] = ['scctcust.DESC04', '=', $val];
                    break;
                default:
                    ($key) && $filters[] = [$key, '=', $val];
                    break;
            }
        }

        if (empty($filters)) {
            return null;
        }

        return function ($query) use ($filters) {
            foreach ($filters as $filter) {
                if (($filter[0] ?? null) === 'whereRaw') {
                    $query->whereRaw($filter[1], $filter[2] ?? []);
                    continue;
                }
                if (count($filter) === 3 && ($filter[1] ?? null) === 'like_any' && is_array($filter[2] ?? null)) {
                    $query->where(function ($q) use ($filter) {
                        foreach ($filter[2] as $name) {
                            $sanitized = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], (string) $name);
                            $q->orWhere($filter[0], 'like', '%' . $sanitized . '%');
                        }
                    });
                    continue;
                }
                if (count($filter) === 3 && ($filter[1] ?? null) === 'unit_any') {
                    continue;
                }
                if (count($filter) === 3) {
                    if (($filter[1] ?? null) === 'in' && is_array($filter[2] ?? null)) {
                        $query->whereIn($filter[0], $filter[2]);
                    } else {
                        $query->where($filter[0], $filter[1], $filter[2]);
                    }
                } elseif (count($filter) === 4) {
                    if ($filter[3] == 'whereBetween') {
                        $query->whereBetween($filter[0], [$filter[1], $filter[2]]);
                    } else {
                        $query->{$filter[3]}($filter[0], $filter[1], $filter[2]);
                    }
                }
            }
        };
    }
}
