<?php

namespace App\Http\Controllers;

use App\Models\MobilipaAccount;
use Illuminate\Http\Request;

class MobilipaAccountController extends Controller
{
    /**
     * Display Mobilipa sub-accounts.
     * GET /mobilipa-accounts
     */
    public function index()
    {
        $accounts = MobilipaAccount::withCount(['transactions as completed_transactions_count' => function ($query) {
            $query->where('payment_status', 'COMPLETED');
        }])->withSum(['transactions as total_revenue' => function ($query) {
            $query->where('payment_status', 'COMPLETED');
        }], 'amount')->get();

        return view('dashboard.mobilipa-accounts.index', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a new Mobilipa sub-account.
     * POST /mobilipa-accounts
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'required|string|min:10',
            'base_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        MobilipaAccount::create($validated);

        return redirect()->back()
            ->with('success', 'Mobilipa sub-account created successfully.');
    }

    /**
     * Update a Mobilipa sub-account.
     * POST /mobilipa-accounts/{account}/update
     */
    public function update(Request $request, MobilipaAccount $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'required|string|min:10',
            'base_url' => 'nullable|url',
        ]);

        $account->update($validated);

        return redirect()->back()
            ->with('success', 'Mobilipa sub-account updated successfully.');
    }

    /**
     * Toggle Mobilipa sub-account active status.
     * POST /mobilipa-accounts/{account}/toggle
     */
    public function toggle(MobilipaAccount $account)
    {
        $account->update(['is_active' => ! $account->is_active]);

        $status = $account->is_active ? 'enabled' : 'disabled';

        return redirect()->back()
            ->with('success', $account->name.' has been '.$status.'.');
    }

    /**
     * Delete a Mobilipa sub-account.
     * DELETE /mobilipa-accounts/{account}
     */
    public function destroy(MobilipaAccount $account)
    {
        $account->delete();

        return redirect()->back()
            ->with('success', 'Mobilipa sub-account deleted successfully.');
    }
}
