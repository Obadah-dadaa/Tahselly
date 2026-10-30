<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * A beta account tried to create more clients or invoices than the beta allows.
 * Thrown from the models themselves, so every path (forms, import, anything later) is covered.
 */
class BetaLimitReached extends RuntimeException
{
    public function render(Request $request): RedirectResponse
    {
        return back()->withInput()->with('error', $this->getMessage());
    }
}
