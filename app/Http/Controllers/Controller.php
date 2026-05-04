<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

abstract class Controller
{
    /**
     * Resolves the current account ID from the authenticated user context.
     * Strict multi-tenant scoping - no hardcoded fallbacks to other accounts.
     * 
     * @return string
     */
    protected function resolveAccountId(): string
    {
        if (!auth()->check()) {
            abort(401, 'Unauthorized');
        }

        $user = auth()->user();
        
        // 1. If the logged-in entity is itself an Account (primary key is accountid)
        if ($user instanceof \App\Models\Account) {
            return $user->accountid;
        }

        // 2. If it's a User model with an accountid field
        if (isset($user->accountid) && !empty($user->accountid)) {
            return $user->accountid;
        }

        // 3. Fallback to auth ID if it looks like an account ID
        $authId = auth()->id();
        if (is_string($authId) && Str::startsWith($authId, 'ACC')) {
            return $authId;
        }

        abort(403, 'Account context not found. Please contact support.');
    }
}
