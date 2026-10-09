<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use jeremykenedy\laravelusers\Support\AccountLinks;

class AccountLinksController extends Controller
{
    public function show(string $token, AccountLinks $links): Response
    {
        $link = $links->inspect($token);

        return $this->page(['link' => $link, 'token' => $token], $link ? 200 : 410);
    }

    public function confirm(string $token, AccountLinks $links): Response
    {
        $action = $links->consume($token);

        return $this->page(['completed' => $action], $action ? 200 : 410);
    }

    private function page(array $data, int $status): Response
    {
        return response()->view('laravelusers::account-links.confirm', $data, $status)
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow')->header('X-Frame-Options', 'DENY');
    }
}
