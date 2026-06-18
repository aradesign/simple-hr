<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Middleware\EnsurePortalAuth;
use App\Models\Person;
use Illuminate\Http\Request;

trait ResolvesPortalPerson
{
    protected function portalPerson(Request $request): Person
    {
        return Person::query()->findOrFail(
            $request->session()->get(EnsurePortalAuth::SESSION_KEY),
        );
    }
}
