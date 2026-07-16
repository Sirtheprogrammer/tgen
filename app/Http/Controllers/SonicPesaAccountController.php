<?php

namespace App\Http\Controllers;

use App\Models\SonicPesaAccount;
use Illuminate\Http\Request;

class SonicPesaAccountController extends Controller
{
    /**
     * Display SonicPesa sub-accounts.
     * GET /sonicpesa-accounts
     */
    public function index()
    {
        $accounts = SonicPesaAccount::withCount(['transactions as completed_transactions_count' => function ($query) {
            $query->where('payment_status', 'COMPLETED');
        }])->withSum(['transactions as total_revenue' => function ($query) {
            $query->where('payment_status', 'COMPLETED');
        }], 'amount')->get();

        return view('dashboard.sonicpesa-accounts.index', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a new SonicPesa sub-account.
     * POST /sonicpesa-accounts
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'required|string|min:10',
            'base_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        SonicPesaAccount::create($validated);

        return redirect()->back()
            ->with('success', 'SonicPesa sub-account created successfully.');
    }

    /**
     * Update a SonicPesa sub-account.
     * POST /sonicpesa-accounts/{account}/update
     */
    public function update(Request $request, SonicPesaAccount $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'required|string|min:10',
            'base_url' => 'nullable|url',
        ]);

        $account->update($validated);

        return redirect()->back()
            ->with('success', 'SonicPesa sub-account updated successfully.');
    }

    /**
     * Toggle SonicPesa sub-account active status.
     * POST /sonicpesa-accounts/{account}/toggle
     */
    public function toggle(SonicPesaAccount $account)
    {
        $account->update(['is_active' => ! $account->is_active]);

        $status = $account->is_active ? 'enabled' : 'disabled';

        return redirect()->back()
            ->with('success', $account->name.' has been '.$status.'.');
    }

    /**
     * Delete a SonicPesa sub-account.
     * DELETE /sonicpesa-accounts/{account}
     */
    public function destroy(SonicPesaAccount $account)
    {
        $account->delete();

        return redirect()->back()
            ->with('success', 'SonicPesa sub-account deleted successfully.');
    }
}
