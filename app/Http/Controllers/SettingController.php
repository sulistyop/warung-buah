<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'komisi_persen' => 'required|numeric|min:0|max:100',
            'nama_toko'     => 'required|string|max:255',
            'alamat_toko'   => 'nullable|string|max:500',
        ]);

        Setting::set('komisi_persen', $request->komisi_persen);
        Setting::set('nama_toko', $request->nama_toko);
        Setting::set('alamat_toko', $request->alamat_toko);

        return back()->with('success', 'Pengaturan berhasil disimpan!');
    }
}
