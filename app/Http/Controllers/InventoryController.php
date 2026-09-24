<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryLoan;
use App\Models\InventoryMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $items = Inventory::query()
            ->withCount('loans')
            ->with(['ambalan', 'loans.member'])
            ->orderBy('nama_barang')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Inventory/Index', ['items' => $items]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'ambalan_id' => ['required', 'exists:ambalans,id'],
            'kode_barang' => ['required', 'unique:inventories,kode_barang', 'max:100'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:Aset,Stok'],
            'satuan' => ['required', 'string', 'max:50'],
            'jumlah' => ['required', 'integer', 'min:0'],
            'kondisi' => ['required', 'in:Baik,Rusak'],
            'status_pinjam' => ['required', 'in:Tersedia,Dipinjam'],
        ]);
        $item = Inventory::create($data);
        InventoryMovement::create([
            'inventory_id' => $item->id,
            'jenis' => 'Saldo Awal',
            'jumlah' => $data['jumlah'],
            'actor_id' => $request->user()->id,
            'catatan' => 'Pendataan awal',
        ]);

        return redirect()->route('inventory.index')->with('success', 'Inventaris berhasil ditambahkan.');
    }

    public function update(Request $request, Inventory $inventory): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:Aset,Stok'],
            'satuan' => ['required', 'string', 'max:50'],
            'kondisi' => ['required', 'in:Baik,Rusak'],
            'status_pinjam' => ['required', 'in:Tersedia,Dipinjam'],
        ]);
        $inventory->update($data);

        return redirect()->route('inventory.index')->with('success', 'Inventaris berhasil diperbarui.');
    }

    public function destroy(Request $request, Inventory $inventory): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $inventory->delete();

        return redirect()->route('inventory.index')->with('success', 'Inventaris berhasil diarsipkan.');
    }

    public function loan(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'inventory_id' => ['required', 'exists:inventories,id'],
            'member_id' => ['nullable', 'exists:members,id'],
            'peminjam_nama' => ['required', 'string', 'max:255'],
            'tgl_pinjam' => ['required', 'date'],
            'tgl_kembali' => ['nullable', 'date', 'after_or_equal:tgl_pinjam'],
            'kondisi' => ['required', 'in:Baik,Rusak'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
        $item = Inventory::findOrFail($data['inventory_id']);
        $loan = InventoryLoan::create([
            ...$data,
            'status' => 'Dipinjam',
            'approved_by' => $request->user()->id,
        ]);
        $item->update(['status_pinjam' => 'Dipinjam']);
        InventoryMovement::create([
            'inventory_id' => $item->id,
            'jenis' => 'Peminjaman',
            'jumlah' => -1,
            'referensi' => InventoryLoan::class.':'.$loan->id,
            'actor_id' => $request->user()->id,
            'catatan' => $data['catatan'],
        ]);

        return redirect()->route('inventory.index')->with('success', 'Peminjaman inventaris berhasil dicatat.');
    }

    public function returnLoan(Request $request, InventoryLoan $inventoryLoan): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'kondisi' => ['required', 'in:Baik,Rusak'],
            'tgl_kembali' => ['required', 'date', 'after_or_equal:tgl_pinjam'],
        ]);
        $inventoryLoan->update([
            ...$data,
            'status' => 'Dikembalikan',
        ]);
        $inventoryLoan->inventory->update(['status_pinjam' => 'Tersedia', 'kondisi' => $data['kondisi']]);
        InventoryMovement::create([
            'inventory_id' => $inventoryLoan->inventory_id,
            'jenis' => 'Pengembalian',
            'jumlah' => 1,
            'referensi' => InventoryLoan::class.':'.$inventoryLoan->id,
            'actor_id' => $request->user()->id,
            'catatan' => 'Barang dikembalikan',
        ]);

        return redirect()->route('inventory.index')->with('success', 'Barang berhasil dikembalikan.');
    }

    public function adjustment(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'inventory_id' => ['required', 'exists:inventories,id'],
            'jumlah' => ['required', 'integer', 'min:-999999', 'max:999999'],
            'catatan' => ['required', 'string', 'max:1000'],
        ]);
        abort_if($data['jumlah'] === 0, 422);
        $item = Inventory::findOrFail($data['inventory_id']);
        $item->increment('jumlah', $data['jumlah']);
        InventoryMovement::create([
            'inventory_id' => $item->id,
            'jenis' => 'Penyesuaian',
            'jumlah' => $data['jumlah'],
            'actor_id' => $request->user()->id,
            'catatan' => $data['catatan'],
        ]);

        return redirect()->route('inventory.index')->with('success', 'Stok inventaris berhasil disesuaikan.');
    }
}
