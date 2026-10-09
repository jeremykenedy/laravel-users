<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Route;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Test\TestCase;

class HomeNavigationTest extends TestCase
{
    public function test_brand_falls_back_to_users_when_the_application_has_no_home(): void
    {
        $this->assertBrandDestination(route('users'));
    }

    public function test_brand_keeps_the_application_root_when_it_exists(): void
    {
        Route::get('/', fn () => 'Application home');
        $this->assertBrandDestination(url('/'));
        $this->get('/')->assertOk()->assertSee('Application home');
    }

    public function test_brand_uses_a_named_home_page_when_the_root_is_missing(): void
    {
        Route::get('/dashboard', fn () => 'Dashboard')->name('home');
        $this->assertBrandDestination(route('home'));
        $this->get('/dashboard')->assertOk()->assertSee('Dashboard');
    }

    public function test_post_only_root_and_parameterized_home_routes_do_not_break_navigation(): void
    {
        Route::post('/', fn () => 'Submit');
        Route::get('/dashboard/{organization}', fn () => 'Dashboard')->name('home');
        $this->assertBrandDestination(route('users'));
    }

    private function assertBrandDestination(string $url): void
    {
        $this->actingAs($this->user());
        foreach (Frontend::RELEASE_FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $class = $framework === 'bootstrap4' ? 'navbar-brand' : 'lu-brand';
            $this->get('/users')->assertOk()->assertSee('class="'.$class.'" href="'.$url.'"', false);
        }
    }
}
