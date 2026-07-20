<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();

        if (session()->has('impersonating_user')) {
            // When impersonating, check the employee's permissions only for the employee dashboard.
            // For all other routes (like the Users module), keep using the Admin's own permissions.
            if ($request->routeIs('team-work.*')) {
                $impersonatedId = session('impersonating_user');
                $user = User::find($impersonatedId) ?? $user;
            }
        }

        if (! $user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }

        // Implicitly map standard route actions to granular permissions.
        $routeName = $request->route() ? $request->route()->getName() : null;
        if ($routeName) {
            $parts = explode('.', $routeName);
            $action = end($parts);

            // Map standard controller method names to permission verbs
            $actionMap = [
                'create' => ['create'],
                'store' => ['create'],
                'edit' => ['edit'],
                'update' => ['edit'],
                'destroy' => ['delete', 'cancel'],
                'cancel' => ['cancel'],
                'restore' => ['edit', 'create'], // just require edit/create to restore
                'toggle-status' => ['edit'],
                'toggle' => ['edit'],
                'update-sequence' => ['edit'],
                'default' => ['edit'],
                'ajax-save' => ['create', 'edit'],
                'ajax-delete' => ['delete'],
            ];

            if (isset($actionMap[$action])) {
                $module = $parts[0]; // e.g. 'clients'

                // Map sub-resources back to their parent module's permissions
                $moduleAliases = [
                    'services' => 'items',
                    'groups' => 'clients',
                    'client-categories' => 'clients',
                    'service-categories' => 'items',
                    'product-categories' => 'items',
                    'roles' => 'users',
                    'departments' => 'users',
                    'taxes' => 'settings',
                    'account' => 'settings',
                    'serial' => 'settings',
                    'financial-year' => 'settings',
                    'terms-conditions' => 'settings',
                    'message-templates' => 'settings',
                    'holidays' => 'settings',
                ];

                if (array_key_exists($module, $moduleAliases)) {
                    $module = $moduleAliases[$module];
                }

                // Skip granular checks for team-work since it's controlled by team_work.view
                if ($module === 'team-work') {
                    return $next($request);
                }

                $hasGranularAccess = false;

                foreach ($actionMap[$action] as $mappedAction) {
                    // E.g., clients.create, users.cancel
                    if ($user->hasPermission($module.'.'.$mappedAction)) {
                        $hasGranularAccess = true;
                        break;
                    }

                    // Fallback: If the system lacks specific create/delete permissions for this module
                    // (like settings), we treat `.edit` as full write access.
                    if (in_array($mappedAction, ['create', 'delete', 'cancel']) && $user->hasPermission($module.'.edit')) {
                        $hasGranularAccess = true;
                        break;
                    }
                }

                // If none of the required mapped granular permissions match, abort
                if (! $hasGranularAccess) {
                    // Check if the module actually has these granular permissions defined
                    // by seeing if any user could possibly have it (not easily done without hardcoding)
                    // We'll enforce strictly.
                    abort(403, 'Unauthorized action (requires '.implode(' or ', $actionMap[$action]).' permission).');
                }
            }
        }

        return $next($request);
    }
}
