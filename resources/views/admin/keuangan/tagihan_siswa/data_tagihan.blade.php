@extends('layouts.admin_new')
@section('title',$dataTitle??$mainTitle??$title??'')
@section('style')
    <link rel="stylesheet" href="{{asset('main/libs/select2/select2.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/datatables-bs5/datatables.bootstrap5.css')}}?v=20260610-row-border">
    <link rel="stylesheet" href="{{asset('main/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css')}}">
    <style>
        .trx-log-detail-row > td, .bills-detail-row > td {
            padding: 0 !important;
            background: #f5f7fb;
            border-left: 3px solid #696cff;
        }
        .bills-detail-row > td {
            border-left-color: #71dd37;
        }
        .trx-log-panel, .bills-panel {
            padding: 0.75rem 1rem 1rem;
        }
        .trx-log-panel__header, .bills-panel__header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 1rem;
            margin-bottom: 0.75rem;
        }
        .trx-log-panel__title, .bills-panel__title {
            font-weight: 600;
            color: #566a7f;
            margin-right: auto;
        }
        .trx-log-panel__title i, .bills-panel__title i {
            color: #696cff;
            margin-right: 0.25rem;
        }
        .trx-log-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            font-size: 0.78rem;
            background: #fff;
            border: 1px solid #d9dee3;
            color: #566a7f;
        }
        .trx-log-table thead th, .bills-table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
            background: #eef0ff !important;
            color: #566a7f;
        }
        .bills-table thead th {
            background: #eafbea !important;
        }
        .trx-log-table tbody td, .bills-table tbody td {
            font-size: 0.82rem;
            vertical-align: middle;
        }
        .trx-log-metode {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .trx-log-amount--debet {
            color: #ff3e1d;
            font-weight: 600;
        }
        .trx-log-amount--kredit {
            color: #71dd37;
            font-weight: 600;
        }
        .trx-log-empty, .bills-empty {
            padding: 1.25rem;
            text-align: center;
            color: #a1acb8;
            font-size: 0.9rem;
        }
        .badge-lunas {
            background: #71dd37;
            color: #fff;
        }
        .badge-belum-lunas {
            background: #ff9f43;
            color: #fff;
        }
    </style>
@endsection
@section('content')
    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">
        @if(isset($dataTitle) && isset($mainTitle) && $mainTitle != $dataTitle)
            {{$mainTitle .' - '.$dataTitle}}
        @else
            {{$mainTitle??$title??''}}
        @endif
    </h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item">
            <a href="{{route('admin.index')}}" class="text-hover-primary">Beranda</a>
        </li>
        @if(isset($title))
            <li class="breadcrumb-item">
                {{$title}}
            </li>
        @endif
        @if(isset($mainTitle))
            <li class="breadcrumb-item">
                {{$mainTitle}}
            </li>
        @endif
        @if(isset($dataTitle) && isset($mainTitle) && $mainTitle != $dataTitle)
            <li class="breadcrumb-item active">
                {{$dataTitle}}
            </li>
        @endif
    </ul>

    <div class="card">
        <div class="card-header">
            <div class="row mb-3">
                <h5 class="mb-0 me-2">{{($dataTitle??$mainTitle??$title)}}</h5>
            </div>
        </div>
        <div class="card-body">
            <div class="row px-5 mb-2">
                <ul class="list-group list-group-timeline">
                    <li class="list-group-item list-group-timeline-danger">
                        <strong>Pastikan browser anda tidak memblokir <i>POP-UP</i>!</strong>
                    </li>
                </ul>
            </div>
            <form id="filter-form">
                <fieldset class="form-fieldset">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-5">
                                <label class="form-label" for="tanggal-pembuatan">Tanggal Buat Tagihan<span
                                        class="text-warning">*</span>(tanggal-bulan-tahun - tanggal-bulan-tahun)</label>
                                <input type="text" id="tanggal-pembuatan" name="filter[tanggal-pembuatan]"
                                       placeholder="tanggal/bulan/tahun"
                                       class="form-control" autocomplete="false" inputmode="numeric"/>
                            </div>
                            <div class="mb-5">
                                <label class="form-label" for="filter_periode">
                                    Periode
                                </label>
                                <select class="form-select" id="filter_periode"
                                        name="filter[periode]"
                                        data-control="select2"
                                        data-placeholder="Pilih Periode">
                                    <option value="all">Semua</option>
                                    @isset($periode)
                                        @foreach($periode as $item)
                                            <option value="{{$item}}">{{$item}}</option>
                                        @endforeach
                                    @else
                                        <option>data kosong</option>
                                    @endisset
                                </select>
                            </div>
                            <div class="mb-5">
                                <label class="form-label" for="post">
                                    Nama Tagihan
                                </label>
                                <select class="form-select" id="post"
                                        name="filter[post][]"
                                        multiple
                                        data-control="select2"
                                        data-placeholder="Pilih Nama Tagihan">
                                    @isset($post)
                                        @foreach($post as $item)
                                            <option
                                                value="{{$item->tagihan}}">{{$item->tagihan}}</option>
                                        @endforeach
                                    @else
                                        <option>data kosong</option>
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="col mb-5">
                                <label class="form-label" for="filter[angkatan]]">
                                    Angkatan Siswa
                                </label>
                                <select class="form-select" id="filter[angkatan]"
                                        name="filter[angkatan]"
                                        data-control="select2"
                                        data-placeholder="Pilih Angkatan Siswa">
                                    <option value="all">Semua</option>
                                    @isset($thn_aka)
                                        @foreach($thn_aka as $item)
                                            <option
                                                value="{{$item->thn_aka}}">{{$item->thn_aka}}</option>
                                        @endforeach
                                    @else
                                        <option>data kosong</option>
                                    @endisset
                                </select>
                            </div>
                            <div class="col mb-5">
                                <label class="form-label" for="filter[kelas]">
                                    Kelas
                                </label>
                                <select class="form-select" id="filter[kelas]" name="filter[kelas]"
                                        data-control="select2" data-placeholder="Pilih Kelas">
                                    <option value="all">Semua</option>
                                    @isset($kelas)
                                        @foreach($kelas as $item)
                                            <option
                                                value="{{$item->unit}}~~{{$item->jenjang}}~~{{$item->kelas}}">{{$item->unit}}
                                                - {{$item->jenjang}} {{$item->kelas}}</option>
                                        @endforeach
                                    @else
                                        <option>data kosong</option>
                                    @endisset
                                </select>
                            </div>
                            <div class="col mb-5">
                                <label class="form-label" for="filter[siswa]">
                                    Siswa
                                </label>
                                <input class="form-control" id="filter[siswa]" name="filter[siswa]"
                                       placeholder="Masukkan NIS/NAMA Siswa" data-placeholder="Pilih siswa">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="d-flex justify-content-center flex-column flex-md-row justify-content-md-end gap-4">
                            <button type="button" class="btn btn-facebook" id="cetak-kartu-siswa">
                                <span class="ri-info-card-line me-2"></span>
                                Cetak Kartu Siswa
                            </button>
                            <button type="button" class="btn btn-google-plus btn-print-rekap">
                                <span class="ri-file-pdf-2-line me-2"></span>
                                Cetak Rekap
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <span class="ri-reset-left-line me-2"></span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span class="ri-search-line me-2"></span>
                                Cari
                            </button>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
        <div class="card-datatable table-responsive text-nowrap">
            <table class="table table-sm table-bordered table-hover"
                   id="main_table">
                <thead class="table-light">

                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('script')
    <form id="form-delete" class="mainForm">
        <div class="modal modal-blur fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-header ">
                        <div class="modal-title" id="delete-modal-header">
                            Reversal
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-capitalize text-center py-4">
                        <span class="ri-arrow-go-back-line ri-3x"></span>
                        <h4 id="delete-modal-title">Reversal Pembayaran?</h4>
                        <div id="delete-modal-desc">
                            Anda yakin akan melakukan reversal pembayaran terakhir?
                        </div>
                    </div>
                    <div class="modal-body py-4">
                        <fieldset class="form-fieldset">
                            <div class="mb-3 row">
                                <label for="nocust" class="col-sm-4 col-form-label form-label-sm">NIS</label>
                                <div class="col">
                                    <input type="text" readonly class="form-control  form-control-sm" id="nocust"
                                           name="nocust">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="nmcust" class="col-sm-4 col-form-label form-label-sm">Nama Siswa</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="nmcust"
                                           name="nmcust">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="billnm" class="col-sm-4 col-form-label form-label-sm">Nama Tagihan</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="billnm"
                                           name="billnm">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="billam" class="col-sm-4 col-form-label form-label-sm">Nominal</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="billam"
                                           name="billam">
                                </div>
                            </div>
                        </fieldset>
                        <input type="hidden" id="delete_id" name="item_id" value="">
                        <input type="hidden" id="user_delete_id" name="custid" value="">
                    </div>
                    <div class="modal-footer ">
                        <div class="w-100">
                            <div class="row">
                                <div class="col">
                                    <input type="reset" class="btn btn-outline-secondary w-100" value="Batal"
                                           data-bs-dismiss="modal">
                                </div>
                                <div class="col">
                                    <input type="submit" value="Reversal" id="delete-submit-btn" class="btn btn-warning w-100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form id="form-hapus" class="mainForm">
        <div class="modal modal-blur fade" id="modal-hapus" tabindex="-1" role="dialog" aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-header ">
                        <div class="modal-title">Hapus Tagihan</div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-capitalize text-center py-4">
                        <span class="ri-delete-bin-line ri-3x"></span>
                        <h4>Hapus Tagihan Siswa?</h4>
                        <div class="text-muted small">
                            Hanya tagihan yang belum pernah dibayar (PAIDST = 0, INSTALLMENT = 0).
                        </div>
                    </div>
                    <div class="modal-body py-4">
                        <fieldset class="form-fieldset">
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label form-label-sm">NIS</label>
                                <div class="col">
                                    <input type="text" readonly class="form-control form-control-sm" id="hapus_nocust"
                                           name="nocust">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label form-label-sm">Nama Siswa</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="hapus_nmcust"
                                           name="nmcust">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label form-label-sm">Nama Tagihan</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="hapus_billnm"
                                           name="billnm">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label form-label-sm">Nominal</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="hapus_billam"
                                           name="billam_total">
                                </div>
                            </div>
                        </fieldset>
                        <input type="hidden" id="hapus_id" name="item_id" value="">
                        <input type="hidden" id="user_hapus_id" name="custid" value="">
                    </div>
                    <div class="modal-footer ">
                        <div class="w-100">
                            <div class="row">
                                <div class="col">
                                    <input type="reset" class="btn btn-outline-secondary w-100" value="Batal"
                                           data-bs-dismiss="modal">
                                </div>
                                <div class="col">
                                    <input type="submit" value="Hapus" class="btn btn-danger w-100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script src="{{asset('main/libs/select2/select2.js')}}"></script>
    <script src="{{asset('main/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
    <script src="{{asset('js/va-format.js')}}?v=20260619"></script>
    <script src="{{asset('js/datatableCustom/Datatable-0-4.js')}}?v=20260724-group-view"></script>
    <script>
        window.DATA_TAGIHAN_BOOT = {
            columnUrl: @json($columnsUrl ?? null),
            dataUrl: @json($datasUrl ?? null),
            billsUrl: @json($billsUrl ?? null),
            prefetchedColumns: @json($tableColumns ?? []),
        };
    </script>
    <script>
    (function() {
        function destroyAllDataTables() {
            if (typeof $ !== 'undefined' && $.fn.dataTable) {
                $.fn.dataTable.tables({ visible: true, api: true }).each(function() {
                    try { this.destroy(); } catch(e) {}
                });
                if ($.fn.dataTable.isDataTable('#main_table')) {
                    try { $('#main_table').DataTable().destroy(); } catch(e) {}
                }
            }
            $('.dataTables_wrapper').remove();
            $('#main_table tbody').empty();
            $('#main_table thead').empty();
            $('#main_table').removeClass('dataTable');
            window.__dataTagihanTableBooted = false;
        }

        function initTable() {
            destroyAllDataTables();

            if (typeof window.DATA_TAGIHAN_BOOT === 'undefined' || !window.DATA_TAGIHAN_BOOT.dataUrl) {
                console.error('DATA_TAGIHAN_BOOT tidak ditemukan');
                return;
            }

            if (typeof window.getDT !== 'function') {
                console.error('getDT tidak ditemukan');
                return;
            }

            var cols = [
                {
                    data: 'detail_group',
                    name: '+',
                    orderable: false,
                    className: 'text-center',
                    render: function(d, t, r) {
                        if (t === 'display') {
                            return '<button type="button" class="btn btn-sm btn-primary btn-detail-group">+</button>';
                        }
                        return d;
                    }
                },
                { data: 'NOCUST', name: 'NIS' },
                { data: 'NUM2ND', name: 'NO DAFT' },
                { data: 'NOVA', name: 'NO VA' },
                { data: 'NMCUST', name: 'NAMA' },
                { data: 'CODE02', name: 'UNIT' },
                { data: 'DESC02', name: 'KELAS' },
                { data: 'DESC03', name: 'KELOMPOK' },
                { data: 'BILLAC', name: 'PERIODE' },
                { data: 'JUMLAH_TAGIHAN', name: 'JML ITEM', className: 'text-end' },
                { data: 'BILLAM_TOTAL', name: 'JUMLAH TAGIHAN', className: 'text-end' },
                { data: 'BILLPAID', name: 'JUMLAH TERBAYAR', className: 'text-end' },
                { data: 'SISA', name: 'SISA TAGIHAN', className: 'text-end' }
            ];

            var opts = {
                tableId: 'main_table',
                formId: 'filter-form',
                columnUrl: null,
                dataUrl: window.DATA_TAGIHAN_BOOT.dataUrl,
                prefetchedColumns: cols,
                dataColumns: cols,
                destroy: true,
                retrieve: false,
                thead: true,
                tfoot: false,
                scrollX: true,
                order: [[8, 'desc']],
                paging: true,
                searching: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 75, 100],
                select: true,
                rowId: 'CUSTID',
                buttons: ['excel', 'pdf', 'print'],
                excelCurrencyTotal: true,
                pdfOrientation: 'landscape',
                pdfPageSize: 'A3',
                pdfMargins: [10, 14, 10, 14],
                pdfFontSize: 6,
                pdfHeaderFontSize: 7,
                columnDefs: [{ targets: [0], orderable: false }]
            };

            window.getDT(opts);
            console.log('✅ DataTagihan Table initialized');

            var filterForm = $('#filter-form');
            filterForm.off('submit').on('submit', function(e) {
                e.preventDefault();
                if (typeof window.dataReFilter === 'function') {
                    window.dataReFilter('main_table');
                }
            });
            filterForm.off('reset').on('reset', function() {
                setTimeout(function() {
                    if (typeof window.dataReFilter === 'function') {
                        window.dataReFilter('main_table');
                    }
                    $('[data-control="select2"]', '#filter-form').trigger('change');
                }, 0);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initTable, 300);
            });
        } else {
            setTimeout(initTable, 300);
        }
    })();
    </script>
    <script src="{{asset('main/libs/moment/moment.js')}}"></script>
    <script src="{{asset('main/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js')}}"></script>

    <script type="module">
        import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.min.mjs';
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.min.mjs';
    </script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/pdfmake.min.js"
            integrity="sha512-axXaF5grZBaYl7qiM6OMHgsgVXdSLxqq0w7F4CQxuFyrcPmn0JfnqsOtYHUun80g6mRRdvJDrTCyL8LQqBOt/Q=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/vfs_fonts.min.js"
            integrity="sha512-EFlschXPq/G5zunGPRSYqazR1CMKj0cQc8v6eMrQwybxgIbhsfoO5NAMQX3xFDQIbFlViv53o7Hy+yCWw6iZxA=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script type="text/javascript">
        const select2 = $(`[data-control='select2']`);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function formatRupiah(amount) {
            const value = Number(amount);
            if (!Number.isFinite(value)) return 'Rp 0';
            return 'Rp. ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        const billsUrl = window.DATA_TAGIHAN_BOOT?.billsUrl ?? '';

        const modalDeleteElement = document.getElementById('modal-delete');
        const modalDelete = new bootstrap.Modal(document.getElementById('modal-delete'));
        const modalHapusElement = document.getElementById('modal-hapus');
        const modalHapus = new bootstrap.Modal(document.getElementById('modal-hapus'));

        modalDeleteElement.addEventListener('hide.bs.modal', function () {
            document.getElementById('form-delete').reset();
        });

        modalHapusElement.addEventListener('hide.bs.modal', function () {
            document.getElementById('form-hapus').reset();
        });

        function metodeBadge(metode) {
            const label = (metode ?? '-').toString().trim() || '-';
            const upper = label.toUpperCase();
            let cls = 'bg-label-secondary';
            if (upper.includes('CASH') || upper.includes('TELLER')) cls = 'bg-label-primary';
            else if (upper.includes('REVERSAL') || upper.includes('JURNAL')) cls = 'bg-label-warning';
            else if (upper.includes('TRANSFER') || upper.includes('VA')) cls = 'bg-label-info';
            return `<span class="badge trx-log-metode ${cls}">${label}</span>`;
        }

        function buildTransLogHtml(bill, logs) {
            const rows = logs.length
                ? logs.map((log, idx) => {
                    const debet = Number(log.debet ?? 0);
                    const kredit = Number(log.kredit ?? 0);
                    return `
                    <tr>
                        <td class="text-center text-muted">${idx + 1}</td>
                        <td class="text-nowrap">${log.trxdate ?? '-'}</td>
                        <td>${metodeBadge(log.metode)}</td>
                        <td class="text-end trx-log-amount--debet">${debet > 0 ? formatRupiah(debet) : '-'}</td>
                        <td class="text-end trx-log-amount--kredit">${kredit > 0 ? formatRupiah(kredit) : '-'}</td>
                        <td>${log.fidbank ?? '-'}</td>
                        <td class="text-nowrap">${log.transno ?? '-'}</td>
                        <td class="text-nowrap small text-muted">${log.noreff ?? '-'}</td>
                    </tr>
                `;
                }).join('')
                : '';

            const tableBody = rows || `
                <tr>
                    <td colspan="8">
                        <div class="trx-log-empty">
                            <i class="ri-file-list-3-line ri-lg d-block mb-1"></i>
                            Tidak ada log transaksi
                        </div>
                    </td>
                </tr>
            `;

            return `
                <div class="trx-log-panel">
                    <div class="trx-log-panel__header">
                        <div class="trx-log-panel__title">
                            <i class="ri-history-line"></i> Riwayat Transaksi
                        </div>
                        <span class="trx-log-chip"><strong>Tagihan:</strong> ${bill.BILLNM ?? '-'}</span>
                        <span class="trx-log-chip"><strong>AA:</strong> ${bill.AA ?? '-'}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover trx-log-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 48px;">No</th>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                    <th class="text-end">Debet</th>
                                    <th class="text-end">Kredit</th>
                                    <th>FID Bank</th>
                                    <th>Trans No</th>
                                    <th>No Ref</th>
                                </tr>
                            </thead>
                            <tbody>${tableBody}</tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        async function fetchTransLog(bill) {
            try {
                const params = new URLSearchParams({
                    custid: bill.CUSTID ?? '',
                    billnm: bill.BILLNM ?? '',
                    bill_transno: bill.BILL_TRANSNO ?? ''
                });
                const url = `{{url('admin/keuangan/tagihan-siswa/data-tagihan/get-trans-log')}}/${bill.AA}?${params.toString()}`;
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    }
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result.message || `Gagal ambil log (${response.status})`);
                }
                return Array.isArray(result.logs) ? result.logs : [];
            } catch (e) {
                errorAlert(e.message || 'Gagal ambil log transaksi');
                return [];
            }
        }

        async function toggleTransLogRow($billRow, bill, buttonEl) {
            const detailId = `trx-log-${bill.AA}`;
            const $existing = $(`#${detailId}`);
            if ($existing.length) {
                $existing.remove();
                buttonEl.textContent = '+';
                return;
            }

            $billRow.siblings('.trx-log-detail-row').remove();
            $billRow.closest('table').find('.btn-bill-trx').text('+');

            const logs = await fetchTransLog(bill);
            const colCount = $billRow.children('td').length || 1;
            const detailHtml = buildTransLogHtml(bill, logs);
            $billRow.after(
                `<tr class="trx-log-detail-row" id="${detailId}"><td colspan="${colCount}" class="p-0">${detailHtml}</td></tr>`
            );
            buttonEl.textContent = '-';
        }

        function billStatusBadge(bill) {
            const paidst = parseInt(bill.PAIDST ?? 0, 10) || 0;
            const sisa = Number(bill.BILLAM ?? 0);
            const lunas = paidst === 1 || sisa <= 0;
            return lunas
                ? '<span class="badge badge-lunas">LUNAS</span>'
                : '<span class="badge badge-belum-lunas">BELUM LUNAS</span>';
        }

        function buildBillsTableHtml(groupMeta, bills) {
            const rows = bills.length
                ? bills.map((bill) => {
                    const urut = parseInt(bill.FUrutan ?? 0, 10) || 0;
                    const billPaid = parseInt(bill.BILLPAID ?? 0, 10) || 0;
                    const canReversal = billPaid > 0;
                    const canHapus = !!bill.hapus;
                    return `
                    <tr data-aa="${bill.AA}" data-custid="${bill.CUSTID}">
                        <td>
                            <button type="button" class="btn btn-sm btn-primary btn-bill-trx">+</button>
                        </td>
                        <td>${bill.BILLNM ?? '-'}</td>
                        <td class="text-end">${formatRupiah(bill.BILLAM_TOTAL)}</td>
                        <td class="text-end">${formatRupiah(bill.BILLPAID)}</td>
                        <td class="text-end">${formatRupiah(bill.BILLAM)}</td>
                        <td>${bill.PAIDDT ?? '-'}</td>
                        <td class="text-center">${billStatusBadge(bill)}</td>
                        <td class="text-center">${urut}</td>
                        <td class="text-center text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-bill-naik" ${urut > 0 ? '' : 'disabled'}>
                                <span class="ri-arrow-up-line"></span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-bill-turun" ${urut > 0 ? '' : 'disabled'}>
                                <span class="ri-arrow-down-line"></span>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning btn-bill-reversal" data-billnm="${bill.BILLNM ?? ''}" data-billam="${bill.BILLAM_TOTAL ?? 0}" ${canReversal ? '' : 'disabled'}>
                                <span class="ri-arrow-go-back-line"></span>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-bill-hapus" data-billnm="${bill.BILLNM ?? ''}" data-billam="${bill.BILLAM_TOTAL ?? 0}" ${canHapus ? '' : 'disabled'}>
                                <span class="ri-delete-bin-line"></span>
                            </button>
                        </td>
                    </tr>
                `;
                }).join('')
                : '';

            const tableBody = rows || `
                <tr>
                    <td colspan="9">
                        <div class="bills-empty">Tidak ada tagihan pada periode ini</div>
                    </td>
                </tr>
            `;

            return `
                <div class="bills-panel">
                    <div class="bills-panel__header">
                        <div class="bills-panel__title">
                            <i class="ri-file-list-3-line"></i> Daftar Tagihan
                        </div>
                        <span class="trx-log-chip"><strong>NIS:</strong> ${groupMeta.NOCUST ?? '-'}</span>
                        <span class="trx-log-chip"><strong>Nama:</strong> ${groupMeta.NMCUST ?? '-'}</span>
                        <span class="trx-log-chip"><strong>Periode:</strong> ${groupMeta.BILLAC ?? '-'}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover bills-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 48px;"></th>
                                    <th>Nama Tagihan</th>
                                    <th class="text-end">Jumlah Tagihan</th>
                                    <th class="text-end">Terbayar</th>
                                    <th class="text-end">Sisa</th>
                                    <th>Tanggal Bayar</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Urutan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>${tableBody}</tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        async function fetchBillsForGroup(custid, billac) {
            try {
                const params = new URLSearchParams({custid, billac});
                const response = await fetch(`${billsUrl}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    }
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result.message || `Gagal ambil tagihan (${response.status})`);
                }
                return Array.isArray(result.data) ? result.data : [];
            } catch (e) {
                errorAlert(e.message || 'Gagal ambil daftar tagihan');
                return [];
            }
        }

        function closeAllBillsRows() {
            $('#main_table tbody tr.bills-detail-row').remove();
            $('#main_table tbody .btn-detail-group').each(function () {
                $(this).text('+');
            });
        }

        window.closeAllBillsRows = closeAllBillsRows;

        async function toggleBillsRow($rowEl, rowData, buttonEl) {
            const custid = rowData.CUSTID;
            const billac = rowData.BILLAC;
            if (!custid || !billac) {
                warningAlert('Data grup tagihan tidak valid.');
                return;
            }

            const detailId = `bills-${custid}-${billac}`.replace(/[^a-zA-Z0-9_-]/g, '_');
            const $existing = $(`#${detailId}`);
            if ($existing.length) {
                $existing.remove();
                buttonEl.textContent = '+';
                return;
            }

            closeAllBillsRows();

            const bills = await fetchBillsForGroup(custid, billac);
            const colCount = $rowEl.children('td').length || 1;
            const detailHtml = buildBillsTableHtml(rowData, bills);
            $rowEl.after(
                `<tr class="bills-detail-row" id="${detailId}"><td colspan="${colCount}" class="p-0">${detailHtml}</td></tr>`
            );
            buttonEl.textContent = '-';
        }

        $(document).on('click', '#main_table tbody .btn-detail-group', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $rowEl = $(this).closest('tr');
            const dtRow = window.DT['main_table'].row($rowEl);
            const rowData = dtRow.data();
            if (!rowData) {
                warningAlert('Data baris tidak ditemukan.');
                return;
            }

            await toggleBillsRow($rowEl, rowData, this);
        });

        function submitUbahUrutanBill(direction, aa, custid, $btn) {
            loadingAlert(direction === 'naik' ? 'Menaikkan urutan tagihan...' : 'Menurunkan urutan tagihan...');
            let url = '{{route('admin.keuangan.tagihan-siswa.data-tagihan.ubah-urutan',':id')}}';
            url = url.replace(':id', aa);
            const form = new FormData();
            form.append('urutan_tagihan', direction);
            form.append('custid', custid ?? '');

            fetch(new Request(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: form
            }))
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw {status: response.status, message: data.message || response.statusText};
                    }
                    return data;
                })
                .then(async data => {
                    successAlert(data.message || 'Urutan tagihan berhasil diubah.');
                    const $tr = $btn.closest('tr[data-aa]');
                    const $detailRow = $btn.closest('tr.bills-detail-row');
                    const $groupRow = $detailRow.prev('tr');
                    if ($groupRow.length) {
                        const dtRow = window.DT['main_table'].row($groupRow);
                        const rowData = dtRow.data();
                        const bills = await fetchBillsForGroup(rowData.CUSTID, rowData.BILLAC);
                        $detailRow.find('td').first().html(buildBillsTableHtml(rowData, bills));
                    }
                })
                .catch(error => {
                    errorAlert(error.message || 'Gagal mengubah urutan tagihan.');
                });
        }

        $(document).on('click', '.btn-bill-naik, .btn-bill-turun', function () {
            const $btn = $(this);
            const $tr = $btn.closest('tr[data-aa]');
            const aa = $tr.data('aa');
            const custid = $tr.data('custid');
            const direction = $btn.hasClass('btn-bill-naik') ? 'naik' : 'turun';
            submitUbahUrutanBill(direction, aa, custid, $btn);
        });

        $(document).on('click', '.btn-bill-trx', async function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $tr = $(this).closest('tr[data-aa]');
            const aa = $tr.data('aa');
            const custid = $tr.data('custid');
            const billnm = $tr.find('td').eq(1).text();
            await toggleTransLogRow($tr, {AA: aa, CUSTID: custid, BILLNM: billnm}, this);
        });

        $(document).on('click', '.btn-bill-reversal', function () {
            const $tr = $(this).closest('tr[data-aa]');
            document.getElementById('nocust').value = $tr.closest('.bills-panel').find('.trx-log-chip:eq(0)').text().replace('NIS:', '').trim();
            document.getElementById('nmcust').value = $tr.closest('.bills-panel').find('.trx-log-chip:eq(1)').text().replace('Nama:', '').trim();
            document.getElementById('billnm').value = $(this).data('billnm') ?? '';
            document.getElementById('billam').value = $(this).data('billam') ?? '';
            document.getElementById('delete_id').value = $tr.data('aa');
            document.getElementById('user_delete_id').value = $tr.data('custid');
            modalDelete.show();
        });

        $(document).on('click', '.btn-bill-hapus', function () {
            const $tr = $(this).closest('tr[data-aa]');
            document.getElementById('hapus_nocust').value = $tr.closest('.bills-panel').find('.trx-log-chip:eq(0)').text().replace('NIS:', '').trim();
            document.getElementById('hapus_nmcust').value = $tr.closest('.bills-panel').find('.trx-log-chip:eq(1)').text().replace('Nama:', '').trim();
            document.getElementById('hapus_billnm').value = $(this).data('billnm') ?? '';
            document.getElementById('hapus_billam').value = $(this).data('billam') ?? '';
            document.getElementById('hapus_id').value = $tr.data('aa');
            document.getElementById('user_hapus_id').value = $tr.data('custid');
            modalHapus.show();
        });

        document.getElementById('form-delete').addEventListener('submit', function (e) {
            e.preventDefault();
            submitForm('delete');
        });

        document.getElementById('form-hapus').addEventListener('submit', function (e) {
            e.preventDefault();
            submitForm('hapus');
        });

        function submitForm(form) {
            let request, item_id, user_id, url = null;
            switch (form) {
                case 'delete':
                    loadingAlert('Memproses data....');
                    item_id = document.getElementById('delete_id').value;
                    user_id = document.getElementById('user_delete_id').value;
                    url = '{{route('admin.keuangan.tagihan-siswa.data-tagihan.destroy',':id')}}'
                    url = url.replace(':id', item_id)

                    request = new Request(
                        url, {
                            method: "DELETE",
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            }, body: JSON.stringify({
                                user_id: user_id
                            })
                        });
                    break;
                case 'hapus':
                    loadingAlert('Menghapus tagihan....');
                    item_id = document.getElementById('hapus_id').value;
                    user_id = document.getElementById('user_hapus_id').value;
                    url = '{{route('admin.keuangan.tagihan-siswa.data-tagihan.hapus',':id')}}';
                    url = url.replace(':id', item_id);

                    request = new Request(url, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            user_id: user_id,
                        }),
                    });
                    break;
                default:
                    errorAlert('Data tidak valid!');
                    return;
            }

            fetch(request)
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw {status: response.status, message: data.message || response.statusText};
                    }
                    return data;
                })
                .then(data => {
                    if (typeof window.dataReload === 'function') {
                        window.dataReload('main_table');
                    } else if (window.DT && window.DT.main_table) {
                        window.DT.main_table.ajax.reload();
                    }
                    successAlert(data.message);
                    modalDelete.hide();
                    modalHapus.hide();
                })
                .catch(error => {
                    if (error.status === 422) {
                        errorAlert(error.message);
                    } else {
                        const errorMessages = {
                            401: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan!',
                            403: 'Anda tidak memiliki izin untuk mengakses halaman ini 😖',
                            404: 'Halaman yang dituju tidak ditemukan 🧐',
                            405: 'Metode tidak valid 🧐 <br>silahkan muat ulang halaman dan coba lagi!',
                            419: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan!',
                            429: 'Terlalu banyak permintaan akses <br>silahkan tunggu beberapa saat 🙏',
                        };
                        errorAlert(errorMessages[error.status] || "Terjadi kesalahan, silahkan coba memuat ulang halaman");
                    }
                });
        }

        document.addEventListener("DOMContentLoaded", function () {
            if (select2.length) {
                select2.each(function () {
                    let $this = $(this);
                    $this.wrap('<div class="position-relative"></div>').select2({
                        placeholder: 'Select value',
                        dropdownParent: $this.parent()
                    });
                });
            }

            let date = $('#tanggal-pembuatan');
            date.daterangepicker({
                autoUpdateInput: false,
                todayHighlight: true,
                autoclose: true,
                locale: {
                    format: 'DD-MM-YYYY',
                    separator: " - ",
                    applyLabel: "Terapkan",
                    cancelLabel: "Batal",
                    fromLabel: "Dari",
                    toLabel: "Ke",
                    customRangeLabel: "Kustom",
                    daysOfWeek: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
                    monthNames: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
                    firstDay: 0,
                },
                maxDate: moment()
            }, function (start, end) {
                let duration = end.diff(start, 'days');
                if (duration > 100) {
                    warningAlert("Maksimal 100 hari.");
                    date.data('daterangepicker').setStartDate(start);
                    date.data('daterangepicker').setEndDate(start.clone().add(6, 'days'));
                }
            });

            date.on('apply.daterangepicker hide.daterangepicker', function (ev, picker) {
                if (picker.startDate && picker.endDate) {
                    $(this).val(picker.startDate.format('DD-MM-YYYY') + ' ~ ' + picker.endDate.format('DD-MM-YYYY'));
                }
            });

            date.on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
            });

            date.on('apply.daterangepicker', function (ev, picker) {
                let duration = picker.endDate.diff(picker.startDate, 'days');
                if (duration > 6) {
                    picker.setEndDate(picker.startDate.clone().add(2, 'days'));
                }
            });

            pdfMake.fonts = {
                Times: {
                    normal: 'https://cdn.jsdelivr.net/npm/@canvas-fonts/times-new-roman@1.0.4/Times New Roman.ttf',
                    bold: 'https://cdn.jsdelivr.net/npm/@canvas-fonts/times-new-roman-bold@1.0.4/Times New Roman Bold.ttf',
                    italics: 'https://cdn.jsdelivr.net/npm/@canvas-fonts/times-new-roman-italic@1.0.4/Times New Roman Italic.ttf',
                    bolditalics: 'https://cdn.jsdelivr.net/npm/@canvas-fonts/times-new-roman-bold@1.0.4/Times New Roman Bold.ttf'
                }, Roboto: {
                    normal: 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-Regular.ttf',
                    bold: 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-Medium.ttf',
                    italics: 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-Italic.ttf',
                    bolditalics: 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-MediumItalic.ttf'
                },
            };

            const instansi = {
                nama_instansi: "{{ config('app.nama_instansi') }}",
                nama_sub_1: "{{ config('app.nama_sub_instansi_1') }}",
                nama_sub_2: "{{ config('app.nama_sub_instansi_2') }}",
                akreditasi: "{{ config('app.akreditasi') }}",
                alamat: "{{ config('app.alamat') }}",
                kontak: {
                    telepon: "{{ config('app.telepon') }}",
                    email: "{{ config('app.email') }}",
                    website: "{{ config('app.website') }}"
                }
            };
            const headerLogo = "{{ base64_encode(file_get_contents(public_path(config('app.logo')))) }}";
            const tandaTangan = @json($tanda_tangan);
            const userName = @json(Auth::user()?->name ?? Auth::user()?->users ?? '');
            const domisili = "{{ config('app.domisili') }}";
            const tanggalSekarang = "{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }}";
            const APP_VA_PREFIX = @json((string) (config('app.nova') ?: '797783'));
            const showVA = (nis) => typeof formatNoVA === 'function'
                ? formatNoVA(nis, APP_VA_PREFIX)
                : (() => {
                    const digits = String(nis ?? '').replace(/\D/g, '');
                    if (!digits) return '';
                    const padLen = 16 - APP_VA_PREFIX.length;
                    return APP_VA_PREFIX + digits.padStart(padLen, '0');
                })();

            async function generatePdf(title, bodyContent, unit_logo = false) {
                try {
                    let logo = 'data:image/jpeg;base64,' + headerLogo;

                    if (unit_logo) {
                        logo = await getLogoUnit(unit_logo);
                    }

                    const orientation = 'portrait';
                    const pageMargins = [20, 20, 20, 20];
                    const availableWidth = getContentWidth('A4', orientation, pageMargins);

                    const headerTable = {
                        alignment: 'center',
                        table: {
                            widths: [60, '*'],
                            body: [[
                                logo ? {
                                    image: logo,
                                    width: 60,
                                    alignment: 'center'
                                } : '',
                                {
                                    stack: [
                                        instansi.nama_sub_1 ? {
                                            text: instansi.nama_sub_1.toUpperCase(),
                                            style: 'headerSmall'
                                        } : '',
                                        instansi.nama_sub_2 ? {
                                            text: instansi.nama_sub_2.toUpperCase(),
                                            style: 'headerSmall'
                                        } : '',
                                        {text: instansi.nama_instansi.toUpperCase(), style: 'headerBig'},
                                        instansi.akreditasi ? {text: instansi.akreditasi, style: 'headerSmall'} : '',
                                        instansi.alamat ? {text: instansi.alamat, style: 'headerSmall'} : '',
                                        {
                                            text: `Telp: ${instansi.kontak.telepon || '-'} | Email: ${instansi.kontak.email || '-'} | Web: ${instansi.kontak.website || '-'}`,
                                            style: 'headerSmall'
                                        }
                                    ],
                                    alignment: 'center'
                                }
                            ]]
                        },
                        layout: 'noBorders'
                    };

                    const footer = {
                        columns: [
                            {text: '', width: '*'},
                            {
                                stack: [
                                    {
                                        text: `${domisili}, ${tanggalSekarang}`,
                                        margin: [0, 10, 0, 0],
                                        alignment: 'center'
                                    },
                                    tandaTangan ? {
                                        image: tandaTangan,
                                        width: 100,
                                        alignment: 'center'
                                    } : {},
                                    {text: userName, alignment: 'center'}
                                ],
                                width: 'auto'
                            }
                        ]
                    };

                    const content = [
                        headerTable,
                        {
                            margin: [0, 5, 0, 5],
                            canvas: [
                                {type: 'line', x1: 0, y1: 0, x2: availableWidth, y2: 0, lineWidth: 2},
                                {
                                    type: 'line',
                                    x1: 0,
                                    y1: 3,
                                    x2: availableWidth,
                                    y2: 3,
                                    lineWidth: 0.5,
                                    lineColor: '#888'
                                }
                            ]
                        },
                        {text: title.toUpperCase(), style: 'title', margin: [0, 5, 0, 5]},
                        ...bodyContent,
                        footer
                    ];

                    const docDefinition = {
                        info: {
                            title: String(title || 'KARTU TAGIHAN SISWA').toUpperCase(),
                            subject: 'KARTU TAGIHAN SISWA'
                        },
                        pageSize: 'A4',
                        pageOrientation: orientation,
                        pageMargins: pageMargins,
                        content: content,
                        styles: {
                            headerBig: {fontSize: 16, bold: true, alignment: 'center'},
                            headerSmall: {fontSize: 12, alignment: 'center'},
                            title: {fontSize: 14, bold: true, alignment: 'center'},
                            subTitle: {fontSize: 12, bold: true},
                            tableHeader: {bold: true, fillColor: '#ededed', alignment: 'center'},
                            small: {fontSize: 9, alignment: 'center'},
                            tableFont: {fontSize: 5}
                        },
                        defaultStyle: {font: 'Times'}
                    };

                    pdfMake.createPdf(docDefinition).open();

                    successAlert('File telah didownload <br>' +
                        '<p><span class="badge badge-dot bg-danger me-1"></span> Cek pada menu unduhan browser anda untuk memeriksa!</p>');
                } catch (e) {
                    console.error('Error generating PDF:', e);
                    errorAlert(e.message);
                }
            }

            document.getElementById('cetak-kartu-siswa').addEventListener('click', async function (e) {
                e.preventDefault();
                loadingAlert('Membuat Kartu Siswa');
                let url = '{{route('admin.keuangan.tagihan-siswa.data-tagihan.cetak-kartu-siswa')}}';
                let data = window.DT['main_table'].rows({selected: true}).data();
                if (!data[0]) {
                    warningAlert('silahkan pilih siswa!')
                    return;
                }
                const params = new URLSearchParams();
                params.append('custid', data[0].CUSTID)
                const unit = data[0].CODE02;
                const fullUrl = `${url}?${params.toString()}`;
                const request = new Request(
                    fullUrl, {
                        method: "GET",
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                try {
                    const response = await fetch(request);

                    if (!response.ok) {
                        throw await buildHttpError(response);
                    }

                    const result = await response.json();

                    if (!result?.tagihans?.length) {
                        throw createError("Data Tagihan Kosong", 422);
                    }
                    const data = await generateKartuSiswa(result);
                    await generatePdf('KARTU TAGIHAN SISWA', data, unit)
                } catch (error) {
                    if (error.status === 422) {
                        errorAlert(error.message);
                    } else {
                        const errorMessages = {
                            401: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan!',
                            403: 'Anda tidak memiliki izin untuk mengakses halaman ini 😖',
                            404: 'Halaman yang dituju tidak ditemukan 🧐',
                            405: 'Metode tidak valid 🧐 <br>silahkan muat ulang halaman dan coba lagi!',
                            419: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan!',
                            429: 'Terlalu banyak permintaan akses <br>silahkan tunggu beberapa saat 🙏',
                        };
                        errorAlert(errorMessages[error.status] || "Terjadi kesalahan, silahkan coba memuat ulang halaman");
                    }
                }
            });

            async function getLogoUnit(unit = false) {
                const fallbackLogo = 'data:image/jpeg;base64,' + "{{ base64_encode(file_get_contents(public_path(config('app.logo')))) }}";
                try {
                    if (!unit) {
                        throw 'error';
                    }
                    const cacheKey = `logo_unit_${unit}`;
                    const cachedLogo = localStorage.getItem(cacheKey);
                    if (cachedLogo) {
                        return cachedLogo;
                    }
                    const params = new URLSearchParams();
                    params.append('unit', unit);
                    const request = new Request(
                        `{{ route('admin.master-data.get-logo') }}?${params.toString()}`,
                        {
                            method: "GET",
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        }
                    );
                    const response = await fetch(request);
                    if (!response.ok) {
                        throw 'error';
                    }
                    const result = await response.json();
                    if (!result.data) {
                        throw 'error';
                    }
                    localStorage.setItem(cacheKey, result.data);
                    return result.data;
                } catch {
                    return fallbackLogo;
                }
            }

            async function generateKartuSiswa(data) {
                try {
                    const parsePaidDate = (val) => {
                        if (!val) return null;
                        const iso = new Date(val);
                        if (!Number.isNaN(iso.getTime())) return iso;
                        const m = String(val).match(/^(\d{2})-(\d{2})-(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?$/);
                        if (!m) return null;
                        return new Date(+m[3], +m[2] - 1, +m[1], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0));
                    };

                    const formatPaidDate = (val) => {
                        const dt = parsePaidDate(val);
                        if (!dt) return '-';
                        return dt.toLocaleString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    };

                    const bodyContent = [];

                    let siswa = data.siswa;
                    let nocust = siswa.NOCUST === null || siswa.NOCUST === '' || siswa.NOCUST === '-' || !siswa.NOCUST ? false : siswa.NOCUST;

                    const mainTable = [
                        [(nocust ? 'NIS ' : 'No. Pendaftaran'), ': ' + (nocust ? nocust : siswa.NUM2ND), 'Unit', ': ' + siswa.CODE02].map(h => ({
                            text: h,
                            border: [false, false, false, false]
                        })),
                        [(nocust ? 'No. VA ' : '-'), ': ' + (nocust ? showVA(nocust) : ''), 'Kelas', ': ' + siswa.DESC02 + ' '+ siswa.DESC03].map(h => ({
                            text: h,
                            border: [false, false, false, false]
                        })),
                        ['Nama ', ': ' + siswa.NMCUST,'Ayah', ': ' + (siswa.GENUS ?? '-')].map(h => ({
                            text: h,
                            border: [false, false, false, false]
                        })),
                        ['', ' ',  'Ibu', ': ' + (siswa.GENUS1 ?? '')].map(h => ({
                            text: h,
                            border: [false, false, false, false]
                        })),
                    ]

                    bodyContent.push({
                        table: {
                            widths: ['15%', '35%', '15%', '35%'],
                            body: mainTable
                        },
                        layout: {
                            fillColor: null,
                            hLineWidth: () => 0.5,
                            vLineWidth: () => 0.5
                        },
                        margin: [0, 0, 0, 5],
                        fontSize: 9
                    });

                    const tableBody = [
                        ['#', 'Tanggal Bayar', 'Periode', 'Nama Tagihan', 'Total Tagihan', 'Total Bayar', 'Sisa', 'Status']
                            .map(h => ({text: h, style: 'tableHeader'}))
                    ];

                    let totalTagihan = 0;
                    let totalBayar = 0;
                    let totalSisa = 0;
                    const sortedTagihans = [...(data.tagihans || [])].sort((a, b) => {
                        const urutA = Number(a?.FUrutan ?? 0);
                        const urutB = Number(b?.FUrutan ?? 0);
                        if (urutA !== urutB) return urutA - urutB;
                        return String(a?.BILLNM ?? '').localeCompare(String(b?.BILLNM ?? ''));
                    });

                    sortedTagihans.forEach((item, index) => {
                        const tanggalBayar = formatPaidDate(item.PAIDDT_ISO || item.PAIDDT);
                        const jumlahTagihan = Number(item.BILLAM_TOTAL ?? 0);
                        const jumlahBayar = Number(item.BILLPAID ?? 0);
                        const sisaTagihan = Number(item.PAYMENTLEFT ?? item.BILLAM ?? 0);

                        tableBody.push([
                            {text: String(index + 1), alignment: 'center', border: [true, true, true, true]},
                            {text: tanggalBayar, border: [true, true, true, true]},
                            {text: item.BILLAC || '-', border: [true, true, true, true]},
                            {text: item.BILLNM || '-', border: [true, true, true, true]},
                            {text: formatRupiah(jumlahTagihan), alignment: 'right', border: [true, true, true, true]},
                            {text: formatRupiah(jumlahBayar), alignment: 'right', border: [true, true, true, true]},
                            {text: formatRupiah(sisaTagihan), alignment: 'right', border: [true, true, true, true]},
                            {
                                text: Number(item.PAIDST) === 1 || sisaTagihan <= 0 ? 'LUNAS' : 'BELUM LUNAS',
                                alignment: 'center',
                                border: [true, true, true, true]
                            }
                        ]);

                        totalTagihan += jumlahTagihan;
                        totalBayar += jumlahBayar;
                        totalSisa += sisaTagihan;
                    });

                    tableBody.push([
                        {text: 'Total', colSpan: 4, style: 'tableHeader', border: [true, true, true, true]},
                        '',
                        '',
                        '',
                        {
                            text: formatRupiah(totalTagihan),
                            style: 'tableHeader',
                            alignment: 'right',
                            border: [true, true, true, true]
                        },
                        {
                            text: formatRupiah(totalBayar),
                            style: 'tableHeader',
                            alignment: 'right',
                            border: [true, true, true, true]
                        },
                        {
                            text: formatRupiah(totalSisa),
                            style: 'tableHeader',
                            alignment: 'right',
                            border: [true, true, true, true]
                        },
                        {
                            text: '',
                            border: [true, true, true, true]
                        }
                    ])

                    bodyContent.push({
                        table: {
                            widths: ['6%', '13%', '11%', '18%', '13%', '13%', '13%', '13%'],
                            body: tableBody
                        },
                        layout: {
                            fillColor: rowIndex => rowIndex === 0 ? '#ededed' : null,
                            hLineWidth: () => 0.5,
                            vLineWidth: () => 0.5
                        },
                        margin: [0, 0, 0, 0],
                        fontSize: 9
                    });

                    return bodyContent;
                } catch (e) {
                    console.log(e)
                }
            }

            function createError(message, status, extra = {}) {
                const err = new Error(message);
                err.status = status;
                Object.assign(err, extra);
                return err;
            }

            async function buildHttpError(response) {
                const status = response.status;
                const contentType = response.headers.get('content-type');

                let message = `Request failed with status ${status}`;
                let extra = {};

                try {
                    if (contentType?.includes('application/json')) {
                        const data = await response.json();
                        message = data.message ?? message;
                        extra = data;
                    } else {
                        const text = await response.text();
                        message = text || message;
                    }
                } catch {
                }

                return createError(message, status, extra);
            }

            function formatRupiah(amount) {
                if (!amount) return 'Rp 0';
                return 'Rp. ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            function getContentWidth(pageSize = 'A4', orientation = 'portrait', margins = [30, 30, 30, 30]) {
                const sizes = {
                    A4: [595.28, 841.89],
                    A3: [841.89, 1190.55],
                    LETTER: [612, 792],
                    LEGAL: [612, 1008]
                };
                const key = String(pageSize).toUpperCase();
                const size = sizes[key] || sizes.A4;

                const pageW = orientation === 'landscape' ? size[1] : size[0];
                const [ml, , mr] = margins;
                return pageW - ml - mr;
            }
        });
    </script>

    {!! ($modalLink??'') !!}
@endsection