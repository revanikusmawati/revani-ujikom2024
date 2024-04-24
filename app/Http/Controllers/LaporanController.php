<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index()
    {
        return view('laporan.form');
    }

    public function harian(Request $request)
    {
        $tanggal = $request->tanggal;
        $role = $request->role;

        $penjualan = Penjualan::leftJoin('users', 'users.id', '=', 'penjualans.user_id')
            ->leftJoin('pelanggans', 'pelanggans.id', '=', 'penjualans.pelanggan_id')
            ->whereDate('penjualans.tanggal', $tanggal)
            ->where('penjualans.status', '=', 'selesai')
            ->where('users.role', $role)
            ->where('status', 'selesai')
            ->select('penjualans.*', 'pelanggans.nama as nama_pelanggan', 'users.nama as nama_kasir')
            ->orderBy('penjualans.id')
            ->get();

            if ($role) {
                $penjualan->where('users.role', $role);
            }

        return view('laporan.harian', [
            'penjualan' => $penjualan,
            'tanggal' => $tanggal,
            'role' => $role,
        ]);
    }

    public function bulanan(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $role = $request->role; 

        $penjualan = Penjualan::leftJoin('users', 'users.id', '=', 'penjualans.user_id') 
            ->leftJoin('pelanggans', 'pelanggans.id', '=', 'penjualans.pelanggan_id') 
            ->select(
                DB::raw('COUNT(penjualans.id) as jumlah_transaksi'),
                DB::raw('SUM(penjualans.total) as jumlah_total'),
                DB::raw('DATE_FORMAT(penjualans.tanggal, "%d/%m/%Y") as tgl'),
                'users.nama as nama_kasir' 
            )
            ->whereMonth('penjualans.tanggal', $bulan) 
            ->whereYear('penjualans.tanggal', $tahun) 
            ->where('status', 'selesai')
            ->where('penjualans.status', '=', 'selesai'); 

        if ($role) {
            $penjualan->where('users.role', $role);
        }

        $penjualan = $penjualan->groupBy('tgl', 'nama_kasir')->get();

        $nama_bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $bulan_nama = isset($nama_bulan[$bulan]) ? $nama_bulan[$bulan] : null;
        if (!$bulan_nama) {
            return response()->json(['error' => 'Bulan tidak valid'], 400);
        }

        return view('laporan.bulanan', ['penjualan' => $penjualan, 'bulan' => $bulan_nama]);
    }
}