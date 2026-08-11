<?php

namespace App\Http\Controllers\Concerns;

trait RequiresMasterAccess
{
    protected function authorizeMaster(): void
    {
        abort_unless(auth()->user()->role()->canManageMasterData(), 403);
    }
}
