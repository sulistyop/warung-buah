<?php

namespace App\Http\Controllers\Api;

use App\Exports\LaporanPenjualanExport;
use App\Exports\LaporanRekapSupplierExport;
use App\Exports\LaporanPiutangExport;
use App\Exports\LaporanKasLaciExport;
use App\Exports\LaporanStokMasukExport;
use App\Exports\LaporanPelangganTerbaikExport;
use App\Models\Transaksi;
use App\Models\Rekap;
use App\Models\KasLaci;
use App\Models\BarangDatang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    // ─── 1. Laporan Penjualan ────────────────────────────────────────────────

    public function penjualan(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'status_bayar'   => 'nullable|in:lunas,tempo,cicil',
        ]);

        $query = Transaksi::with('user')->orderByDesc('created_at');
        $this->applyDateFilter($query, $request, 'created_at');
        if ($request->filled('status_bayar')) {
            $query->where('status_bayar', $request->status_bayar);
        }

        $transaksi = $query->get();

        $summary = [
            'total_transaksi'  => $transaksi->count(),
            'total_omset'      => $transaksi->sum('total_tagihan'),
            'total_dibayar'    => $transaksi->sum('total_dibayar'),
            'total_piutang'    => $transaksi->sum('sisa_tagihan'),
            'jumlah_lunas'     => $transaksi->where('status_bayar', 'lunas')->count(),
            'jumlah_tempo'     => $transaksi->where('status_bayar', 'tempo')->count(),
            'jumlah_cicil'     => $transaksi->where('status_bayar', 'cicil')->count(),
        ];

        // Grouping per tanggal
        $perHari = $transaksi->groupBy(fn($t) => $t->created_at->format('Y-m-d'))
            ->map(fn($group, $tgl) => [
                'tanggal'      => $tgl,
                'jumlah'       => $group->count(),
                'total_omset'  => $group->sum('total_tagihan'),
                'total_dibayar'=> $group->sum('total_dibayar'),
            ])->values();

        return $this->success([
            'summary'   => $summary,
            'per_hari'  => $perHari,
            'data'      => $transaksi,
        ]);
    }

    public function exportPenjualan(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'status_bayar'   => 'nullable|in:lunas,tempo,cicil',
        ]);

        $filename = 'laporan-penjualan-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanPenjualanExport(
                $request->tanggal_dari,
                $request->tanggal_sampai,
                $request->status_bayar,
            ),
            $filename
        );
    }

    // ─── 2. Laporan Rekap Supplier ───────────────────────────────────────────

    public function rekapSupplier(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'supplier_id'    => 'nullable|integer',
            'status'         => 'nullable|in:draft,final',
        ]);

        $query = Rekap::with(['supplier', 'dibuatOleh'])->orderBy('tanggal');
        $this->applyDateFilter($query, $request, 'tanggal');
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rekap = $query->get();

        $summary = [
            'total_rekap'       => $rekap->count(),
            'total_peti'        => $rekap->sum('total_peti'),
            'total_kotor'       => $rekap->sum('total_kotor'),
            'total_komisi'      => $rekap->sum('total_komisi'),
            'total_kuli'        => $rekap->sum('total_kuli'),
            'total_ongkos'      => $rekap->sum('total_ongkos'),
            'total_busuk'       => $rekap->sum('total_busuk'),
            'total_pendapatan_bersih' => $rekap->sum('pendapatan_bersih'),
            'total_sisa'        => $rekap->sum('sisa'),
            'jumlah_draft'      => $rekap->where('status', 'draft')->count(),
            'jumlah_final'      => $rekap->where('status', 'final')->count(),
        ];

        // Group per supplier
        $perSupplier = $rekap->groupBy('supplier_id')
            ->map(fn($group) => [
                'supplier'          => $group->first()->supplier->nama_supplier ?? '-',
                'jumlah_rekap'      => $group->count(),
                'total_peti'        => $group->sum('total_peti'),
                'total_sisa'        => $group->sum('sisa'),
            ])->values();

        return $this->success([
            'summary'      => $summary,
            'per_supplier' => $perSupplier,
            'data'         => $rekap,
        ]);
    }

    public function exportRekapSupplier(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'supplier_id'    => 'nullable|integer',
            'status'         => 'nullable|in:draft,final',
        ]);

        $filename = 'laporan-rekap-supplier-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanRekapSupplierExport(
                $request->tanggal_dari,
                $request->tanggal_sampai,
                $request->supplier_id ? (int) $request->supplier_id : null,
                $request->status,
            ),
            $filename
        );
    }

    // ─── 3. Laporan Piutang ──────────────────────────────────────────────────

    public function piutang(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $query = Transaksi::whereIn('status_bayar', ['tempo', 'cicil'])
            ->where('sisa_tagihan', '>', 0)
            ->orderBy('tanggal_jatuh_tempo');

        $this->applyDateFilter($query, $request, 'created_at');

        $today = Carbon::today();
        $transaksi = $query->get();

        $agingBrackets = ['0-30' => 0, '31-60' => 0, '61-90' => 0, '>90' => 0];
        foreach ($transaksi as $t) {
            $umur = $today->diffInDays($t->created_at->startOfDay(), false);
            if ($umur <= 30) {
                $agingBrackets['0-30'] += $t->sisa_tagihan;
            } elseif ($umur <= 60) {
                $agingBrackets['31-60'] += $t->sisa_tagihan;
            } elseif ($umur <= 90) {
                $agingBrackets['61-90'] += $t->sisa_tagihan;
            } else {
                $agingBrackets['>90'] += $t->sisa_tagihan;
            }
        }

        $summary = [
            'total_piutang'     => $transaksi->sum('sisa_tagihan'),
            'jumlah_pelanggan'  => $transaksi->pluck('nama_pelanggan')->unique()->count(),
            'jumlah_transaksi'  => $transaksi->count(),
            'overdue'           => $transaksi->filter(fn($t) => $t->isJatuhTempo())->sum('sisa_tagihan'),
            'aging'             => $agingBrackets,
        ];

        $data = $transaksi->map(function ($t) use ($today) {
            $umur = $today->diffInDays($t->created_at->startOfDay(), false);
            return array_merge($t->toArray(), [
                'umur_hari'   => $umur,
                'is_overdue'  => $t->isJatuhTempo(),
            ]);
        });

        return $this->success([
            'summary' => $summary,
            'data'    => $data,
        ]);
    }

    public function exportPiutang(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $filename = 'laporan-piutang-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanPiutangExport($request->tanggal_dari, $request->tanggal_sampai),
            $filename
        );
    }

    // ─── 4. Laporan Kas Laci ─────────────────────────────────────────────────

    public function kasLaci(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $query = KasLaci::with('dibuatOleh')->orderBy('tanggal')->orderBy('id');
        $this->applyDateFilter($query, $request, 'tanggal');

        $entries = $query->get();

        $summary = [
            'total_masuk'   => $entries->where('jenis', 'masuk')->sum('nominal'),
            'total_keluar'  => $entries->where('jenis', 'keluar')->sum('nominal'),
            'saldo_periode' => $entries->where('jenis', 'masuk')->sum('nominal') - $entries->where('jenis', 'keluar')->sum('nominal'),
            'total_entri'   => $entries->count(),
        ];

        // Per hari
        $perHari = $entries->groupBy(fn($k) => $k->tanggal)
            ->map(fn($group, $tgl) => [
                'tanggal'      => $tgl,
                'total_masuk'  => $group->where('jenis', 'masuk')->sum('nominal'),
                'total_keluar' => $group->where('jenis', 'keluar')->sum('nominal'),
            ])->values();

        return $this->success([
            'summary'  => $summary,
            'per_hari' => $perHari,
            'data'     => $entries,
        ]);
    }

    public function exportKasLaci(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $filename = 'laporan-kas-laci-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanKasLaciExport($request->tanggal_dari, $request->tanggal_sampai),
            $filename
        );
    }

    // ─── 5. Laporan Stok Masuk ───────────────────────────────────────────────

    public function stokMasuk(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'supplier_id'    => 'nullable|integer',
        ]);

        $query = BarangDatang::with(['supplier', 'details'])->orderBy('tanggal');
        $this->applyDateFilter($query, $request, 'tanggal');
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $barangDatang = $query->get();

        $allDetails = $barangDatang->flatMap->details;
        $summary = [
            'total_bd'         => $barangDatang->count(),
            'total_produk'     => $allDetails->count(),
            'total_stok_masuk' => $allDetails->sum('jumlah'),
            'total_terjual'    => $allDetails->sum('stok_terjual'),
            'total_sisa'       => $allDetails->sum('stok_sisa'),
        ];

        return $this->success([
            'summary' => $summary,
            'data'    => $barangDatang,
        ]);
    }

    public function exportStokMasuk(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'supplier_id'    => 'nullable|integer',
        ]);

        $filename = 'laporan-stok-masuk-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanStokMasukExport(
                $request->tanggal_dari,
                $request->tanggal_sampai,
                $request->supplier_id ? (int) $request->supplier_id : null,
            ),
            $filename
        );
    }

    // ─── 6. Laporan Pelanggan Terbaik ────────────────────────────────────────

    public function pelangganTerbaik(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'limit'          => 'nullable|integer|min:5|max:100',
        ]);

        $limit = $request->input('limit', 20);

        $query = Transaksi::select(
            'nama_pelanggan',
            DB::raw('COUNT(*) as total_transaksi'),
            DB::raw('SUM(total_tagihan) as total_omset'),
            DB::raw('SUM(total_dibayar) as total_dibayar'),
            DB::raw('SUM(sisa_tagihan) as total_piutang'),
            DB::raw('MAX(created_at) as transaksi_terakhir')
        )
        ->groupBy('nama_pelanggan')
        ->orderByDesc('total_omset')
        ->limit($limit);

        $this->applyDateFilter($query, $request, 'created_at');

        $data = $query->get()->map(function ($row, $i) {
            return array_merge($row->toArray(), ['ranking' => $i + 1]);
        });

        $summary = [
            'total_pelanggan'  => $data->count(),
            'total_omset'      => $data->sum('total_omset'),
            'top_pelanggan'    => $data->first()?->nama_pelanggan ?? '-',
        ];

        return $this->success([
            'summary' => $summary,
            'data'    => $data,
        ]);
    }

    public function exportPelangganTerbaik(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $filename = 'laporan-pelanggan-terbaik-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanPelangganTerbaikExport($request->tanggal_dari, $request->tanggal_sampai),
            $filename
        );
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function applyDateFilter($query, Request $request, string $column): void
    {
        if ($request->filled('tanggal_dari')) {
            $query->whereDate($column, '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate($column, '<=', $request->tanggal_sampai);
        }
    }
}
