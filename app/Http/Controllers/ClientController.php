<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Services\ReceivablesReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::query()
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")))
            ->withCount(['invoices as open_invoices_count' => fn ($q) => $q->open()])
            ->withSum(['invoices as open_amount' => fn ($q) => $q->open()], 'amount')
            ->withSum(['invoices as open_paid' => fn ($q) => $q->open()], 'paid_amount')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.form', ['client' => new Client(['reminders_enabled' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Client::create($this->validated($request));
        ActivityLog::record('client.created', 'إضافة عميل: '.$client->name);

        return redirect()->route('clients.show', $client)->with('success', __('تم إضافة العميل.'));
    }

    public function show(Request $request, Client $client, ReceivablesReport $report): View
    {
        $client->load(['invoices' => fn ($q) => $q->latest('due_date'), 'reminders' => fn ($q) => $q->with('invoice')->latest()->limit(15)]);

        return view('clients.show', [
            'client' => $client,
            'aging' => $request->user()->company->canUse('client_aging') ? $report->aging($client->id) : null,
        ]);
    }

    public function edit(Client $client): View
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validated($request, $client));
        ActivityLog::record('client.updated', 'تعديل عميل: '.$client->name);

        return redirect()->route('clients.show', $client)->with('success', __('تم حفظ التعديلات.'));
    }

    public function destroy(Client $client): RedirectResponse
    {
        ActivityLog::record('client.deleted', 'حذف عميل: '.$client->name);
        $client->delete();

        return redirect()->route('clients.index')->with('success', __('تم حذف العميل وفواتيره.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Client $client = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('clients')->where('company_id', $request->user()->company_id)->ignore($client?->id)],
            'contact_name' => ['nullable', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'trn' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['reminders_enabled'] = $request->boolean('reminders_enabled');
        $data['locale'] = in_array($request->input('locale'), ['ar', 'en'], true) ? $request->input('locale') : null;

        return $data;
    }
}
