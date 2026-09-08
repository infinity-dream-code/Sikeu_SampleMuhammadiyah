(function (window, $) {
    'use strict';

    function bootDataTagihanTable() {
        if (window.__dataTagihanTableBooted) {
            return;
        }

        const boot = window.DATA_TAGIHAN_BOOT || {};
        const dataUrl = boot.dataUrl || '';

        if (!dataUrl) {
            console.error('Data Tagihan: dataUrl kosong');
            return;
        }

        if (typeof window.getDT !== 'function') {
            console.error('Data Tagihan: getDT tidak ditemukan');
            if (typeof window.errorAlert === 'function') {
                window.errorAlert('Script tabel gagal dimuat. Tekan Ctrl+F5 untuk muat ulang halaman.');
            }
            return;
        }

        // 🔥 CEK APAKAH TABEL SUDAH ADA
        if ($.fn.dataTable.isDataTable('#main_table')) {
            console.log('DataTagihan: Tabel sudah ada, hancurkan dulu');
            $('#main_table').DataTable().destroy();
            $('#main_table tbody').empty();
        }

        window.__dataTagihanTableBooted = true;

        var dataColumns = [
            { 
                data: 'detail_group', 
                name: '+', 
                orderable: false, 
                className: 'text-center',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return '<button type="button" class="btn btn-sm btn-primary btn-detail-group">+</button>';
                    }
                    return data;
                }
            },
            { data: 'NOCUST', name: 'NIS' },
            { data: 'NUM2ND', name: 'NO DAFT' },
            { data: 'NOVA', name: 'NO VA' },
            { data: 'NMCUST', name: 'NAMA' },
            { data: 'CODE02', name: 'Unit' },
            { data: 'DESC02', name: 'Kelas' },
            { data: 'DESC03', name: 'Kelompok' },
            { data: 'BILLAC', name: 'Periode' },
            { data: 'JUMLAH_TAGIHAN', name: 'Jml Item', className: 'text-end' },
            { data: 'BILLAM_TOTAL', name: 'Jumlah Tagihan', className: 'text-end' },
            { data: 'BILLPAID', name: 'Jumlah Terbayar', className: 'text-end' },
            { data: 'SISA', name: 'Sisa Tagihan', className: 'text-end' }
        ];

        var dtOptions = {
            tableId: 'main_table',
            formId: 'filter-form',
            columnUrl: null,
            dataUrl: dataUrl,
            prefetchedColumns: dataColumns,
            dataColumns: dataColumns,
            destroy: true,
            retrieve: false,
            thead: true,
            tfoot: false,
            scrollX: true,
            order: [[8, 'desc']],
            paging: true,
            searching: true,
            fixedHeader: false,
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
        };

        window.dtOptions = dtOptions;
        window.getDT(dtOptions);

        $('#main_table').on('draw.dt', function () {
            if (typeof window.closeAllTransLogRows === 'function') {
                window.closeAllTransLogRows();
            }
        });

        var filterForm = $('#filter-form');
        filterForm.off('submit').on('submit', function (e) {
            e.preventDefault();
            if (typeof window.dataReFilter === 'function') {
                window.dataReFilter('main_table');
            }
        });
        filterForm.off('reset').on('reset', function () {
            setTimeout(function () {
                if (typeof window.dataReFilter === 'function') {
                    window.dataReFilter('main_table');
                }
                $('[data-control="select2"]', '#filter-form').trigger('change');
            }, 0);
        });
    }

    function scheduleBoot() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(scheduleBoot, 50);
            return;
        }
        $(bootDataTagihanTable);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleBoot);
    } else {
        scheduleBoot();
    }
})(window, window.jQuery);