<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Middleware;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use jeremykenedy\laravelusers\Actions\SearchUsers;
use jeremykenedy\laravelusers\Support\NativePageData;
use jeremykenedy\laravelusers\Support\NativeRuntime;

class RenderNativePage
{
    public function __construct(private NativePageData $pages, private SearchUsers $users)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        if (NativeRuntime::name() === 'blade') {
            return $response;
        }
        if ($request->routeIs('search-users') && $response instanceof JsonResponse && $response->getStatusCode() === 200 && NativeRuntime::expectsJson($request)) {
            $term = $request->input('user_search_box');
            if (is_string($term)) {
                return response()->json($this->pages->forView('laravelusers::usersmanagement.show-users', ['users' => $this->users->handle($term), 'pagintaionEnabled' => false], $request))
                    ->header('Cache-Control', 'no-store, private');
            }
        }
        if ($response instanceof RedirectResponse && NativeRuntime::expectsJson($request)) {
            return response()->json(['message' => $request->session()->get('success', $request->session()->get('error')), 'redirect' => $response->getTargetUrl()])
                ->header('Cache-Control', 'no-store, private');
        }
        $view = method_exists($response, 'getOriginalContent') ? $response->getOriginalContent() : null;
        if (!$view instanceof View || !NativeRuntime::supportsView($view)) {
            return $response;
        }
        abort_unless(NativeRuntime::available(NativeRuntime::name()), 503, 'The selected user interface is not installed. Run laravelusers:update to complete its setup or select Blade.');
        $page = $this->page($request, $view);
        if (NativeRuntime::expectsJson($request)) {
            $headers = $response->headers->all();
            unset($headers['content-type']);

            return response()->json($page, $response->getStatusCode(), $headers)->header('Cache-Control', 'no-store, private');
        }

        return $response->setContent(view('laravelusers::runtime.page', ['nativePage' => $page, 'nativeRuntime' => NativeRuntime::name()]))
            ->header('Cache-Control', 'no-store, private');
    }

    private function page(Request $request, View $view): array
    {
        $data = $view->getData();
        $term = $request->attributes->get('laravelusers.search');
        if ($request->routeIs('users') && is_string($term) && $term !== '') {
            $data['users'] = $this->users->handle($term);
            $data['pagintaionEnabled'] = false;
        }

        return $this->pages->forView($view->name(), $data, $request);
    }
}
