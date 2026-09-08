/**
 * Inisialisasi tabel Data Tagihan — script terpisah agar tidak terganggu error JS lain di halaman.
 */
(function (window, $) {
    'use strict';

    function bootDataTagihanTable() {
        if (window.__dataTagihanTableBooted) {
            return;
        }

        const boot = window.DATA_TAGIHAN_BOOT || {};
        const columnUrl = boot.columnUrl || '';
        const dataUrl = boot.dataUrl || '';
        const prefetchedColumns = Array.isArray(boot.prefetchedColumns) ? boot.prefetchedColumns : [];

        if (!dataUrl) {
            console.error('Data Tagihan: dataUrl kosong', boot);
            return;
        }

        if (typeof window.getDT !== 'function') {
            console.error('Data Tagihan: getDT tidak ditemukan');
            if (typeof window.errorAlert === 'function') {
                window.errorAlert('Script tabel gagal dimuat. Tekan Ctrl+F5 untuk muat ulang halaman.');
            }
            return;
        }

        window.__dataTagihanTableBooted = true;

        // 🔥 FIX: Gunakan dataColumns dari prefetchedColumns
        const dataColumns = prefetchedColumns.length > 0 ? prefetchedColumns : [
            { data: 'detail_group', name: '+' },
            { data: 'NOCUST', name: 'NIS' },
            { data: 'NUM2ND', name: 'NO DAFT' },
            { data: 'NOVA', name: 'NO VA' },
            { data: 'NMCUST', name: 'NAMA' },
            { data: 'CODE02', name: 'Unit' },
            { data: 'DESC02', name: 'Kelas' },
            { data: 'DESC03', name: 'Kelompok' },
            { data: 'BILLAC', name: 'Periode' },
            { data: 'JUMLAH_TAGIHAN', name: 'Jml Item' },
            { data: 'BILLAM_TOTAL', name: 'Jumlah Tagihan' },
            { data: 'BILLPAID', name: 'Jumlah Terbayar' },
            { data: 'SISA', name: 'Sisa Tagihan' },
        ];

        const dtOptions = {
            tableId: 'main_table',
            formId: 'filter-form',
            columnUrl: columnUrl,
            dataUrl: dataUrl,
            prefetchedColumns: prefetchedColumns,
            dataColumns: dataColumns,  // ← PASTIKAN INI TERISI
            thead: true,
            tfoot: true,
            scrollX: true,
            order: [[8, 'desc']],  // ← FIX: indeks 8 = Periode (BILLAC)
            paging: true,
            searching: true,
            fixedHeader: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 75, 100],
            select: true,
            rowId: 'CUSTID',  // ← FIX: pakai CUSTID
            buttons: ['excel', 'pdf', 'print'],
            excelCurrencyTotal: true,
            pdfOrientation: 'landscape',
            pdfPageSize: 'A3',
            pdfMargins: [10, 14, 10, 14],
            pdfFontSize: 6,
            pdfHeaderFontSize: 7,
        };

        console.log('dtOptions:', dtOptions);  // Debug

        window.dtOptions = window.dtOptions || dtOptions;
        window.getDT(dtOptions);

        $('#main_table').on('init.dt draw.dt select.dt deselect.dt', function () {
            if (typeof window.ensureUrutanToolbarButtons === 'function') {
                window.ensureUrutanToolbarButtons();
            }
            if (typeof window.syncTagihanCheckboxSelection === 'function') {
                window.syncTagihanCheckboxSelection();
            }
            if (typeof window.updateUrutanToolbarState === 'function') {
                window.updateUrutanToolbarState();
            }
        });
        $('#main_table').on('draw.dt', function () {
            if (typeof window.closeAllTransLogRows === 'function') {
                window.closeAllTransLogRows();
            }
        });

        const filterForm = $('#filter-form');
        filterForm.on('submit', function (e) {
            e.preventDefault();
            if (typeof window.dataReFilter === 'function') {
                window.dataReFilter('main_table');
            }
        });
        filterForm.on('reset', function () {
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