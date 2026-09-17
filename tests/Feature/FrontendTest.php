<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\View;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Test\TestCase;

class FrontendTest extends TestCase
{
    public function test_all_framework_pages_render_in_each_theme(): void
    {
        $user = $this->user(['name' => '<script>alert(1)</script>']);
        $this->actingAs($user);
        foreach (Frontend::FRAMEWORKS as $framework) {
            foreach (['light', 'dark', 'system'] as $theme) {
                config(['laravelusers-ui' => ['framework' => $framework, 'theme' => $theme]]);
                foreach (['/users', '/users/create', '/users/'.$user->id, '/users/'.$user->id.'/edit'] as $url) {
                    $this->get($url)->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('data-lu-theme="'.$theme.'"', false);
                }
            }
        }
    }

    public function test_modern_frontends_do_not_load_legacy_scripts(): void
    {
        $this->actingAs($this->user());
        foreach (['bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $response = $this->get('/users')->assertOk()->assertDontSee('jquery-3.3.1')->assertDontSee('bootstrap/4.0.0');
            if ($framework === 'bootstrap5') {
                $response->assertSee('bootstrap@5.3.8');
            } else {
                $response->assertDontSee('cdn.jsdelivr.net')->assertSee('lu:overflow-x-auto');
            }
        }
    }

    public function test_custom_view_configuration_wins_over_framework_selection(): void
    {
        config(['laravelusers.frontend' => 'tailwind', 'laravelusers.showUsersBlade' => 'custom']);
        $this->actingAs($this->user());
        $this->get('/users')->assertOk()->assertSee('Custom users view')->assertViewIs('custom');
    }

    public function test_custom_layout_sections_are_preserved(): void
    {
        config(['laravelusers.frontend' => 'bootstrap5', 'laravelusers.laravelUsersBladeExtended' => 'layout']);
        $this->actingAs($this->user());
        $this->get('/users')->assertOk()->assertSee('Host layout')->assertSee('lu-search')->assertSee('bootstrap@5.3.8');
    }

    public function test_published_modern_view_takes_precedence(): void
    {
        config(['laravelusers.frontend' => 'tailwind']);
        View::prependNamespace('laravelusers', __DIR__.'/../Fixtures/overrides');
        $this->actingAs($this->user());
        $this->get('/users')->assertOk()->assertSee('Published modern override');
    }

    public function test_disabled_search_alerts_and_theme_toggle_remain_disabled(): void
    {
        config(['laravelusers.frontend' => 'tailwind', 'laravelusers.enableSearchUsers' => false, 'laravelusers.enablePackageBootstapAlerts' => false]);
        $this->actingAs($this->user());
        $this->withSession(['success' => 'Hidden success'])->get('/users')->assertOk()->assertDontSee('id="lu-search"', false)->assertDontSee('Hidden success')->assertDontSee('id="lu-theme"', false);
    }

    public function test_modern_validation_repopulates_non_secret_fields(): void
    {
        config(['laravelusers.frontend' => 'tailwind']);
        $this->actingAs($this->user());
        $this->from('/users/create')->post('/users', ['name' => 'Attempt', 'email' => 'invalid', 'password' => 'secret123', 'password_confirmation' => 'wrong'])->assertSessionHasErrors();
        $this->get('/users/create')->assertOk()->assertSee('value="Attempt"', false)->assertSee('aria-invalid="true"', false)->assertDontSee('value="secret123"', false);
    }
}
