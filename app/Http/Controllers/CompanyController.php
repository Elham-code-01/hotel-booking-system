<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\Audit;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        return view('companies.index', [
            'companies' => Company::withCount('bookings')->orderBy('name')->get(),
            'canWrite'  => in_array($request->user()->role, ['admin', 'staff'], true),
            'canDelete' => $request->user()->role === 'admin',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Company::create($this->validated($request));

        return back()->with('success', 'Company added.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $company->update($this->validated($request, $company));

        return back()->with('success', $company->name.' saved.');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        if ($company->bookings()->exists() || $company->payments()->exists() || $company->otherCharges()->exists()) {
            return back()->with('error', 'This company has bookings or payments, so it cannot be deleted.');
        }

        Audit::record('company.deleted', ['by' => $request->user()->email, 'company' => $company->name]);
        $company->delete();

        return back()->with('success', 'Company deleted.');
    }

    private function validated(Request $request, ?Company $company = null): array
    {
        return $request->validate([
            'name'  => ['required', 'string', 'max:150', Rule::unique('companies', 'name')->ignore($company?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
