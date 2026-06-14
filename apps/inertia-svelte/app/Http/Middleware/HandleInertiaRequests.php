<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Rankbeam\Examples\Fixtures;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Props shared with every page — here, the nav links so client-side
     * navigation between the contract pages works the same on every Inertia app.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'nav' => array_map(
                static fn (array $p): array => [
                    'key' => $p['key'],
                    'label' => $p['nav_label'],
                    'href' => url($p['path']),
                ],
                Fixtures::pages(),
            ),
        ]);
    }
}
