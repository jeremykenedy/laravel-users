<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\View;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Test\TestCase;

class FrontendTest extends TestCase
{
    public function test_impersonation_markup_styles_and_search_actions_are_not_rendered_without_a_roles_integration(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework, 'laravelusers.impersonation.enabled' => true]);
            foreach (['/users', '/users/create', '/users/'.$user->id, '/users/'.$user->id.'/edit'] as $url) {
                $this->get($url)->assertOk()->assertDontSee('impersonat', false);
            }
        }
    }

    public function test_saved_names_cannot_close_the_document_title_on_show_or_edit(): void
    {
        $name = '</title><script>alert(1)</script>';
        $user = $this->user(['name' => $name]);
        $this->actingAs($user);
        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework]);
            foreach (['/users/'.$user->id, '/users/'.$user->id.'/edit'] as $url) {
                $this->get($url)->assertOk()->assertDontSee($name, false)->assertSee(e($name), false);
            }
        }
    }

    public function test_card_grid_counts_use_config_and_reject_invalid_css(): void
    {
        $this->actingAs($this->user());
        config(['laravelusers.frontend' => 'bootstrap5', 'laravelusers.cardColumns' => ['mobile' => 2, 'tablet' => 3, 'desktop' => 4, 'wide' => 5]]);
        $response = $this->get('/users')->assertOk();
        foreach ([2, 3, 4, 5] as $count) {
            $response->assertSee('repeat('.$count.', minmax(0, 1fr))', false);
        }
        config(['laravelusers.cardColumns' => ['mobile' => 'bad; color:red', 'tablet' => -1, 'desktop' => 99, 'wide' => null]]);
        $this->get('/users')->assertOk()->assertDontSee('bad; color:red')->assertSee('repeat(1, minmax(0, 1fr))', false)->assertSee('repeat(12, minmax(0, 1fr))', false);
    }

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
        config(['laravelusers.frontend' => 'bootstrap5']);
        $this->get('/users/'.$user->id)
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<title><script>alert(1)</script>', false);
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

    public function test_brand_returns_home_and_breadcrumbs_are_opt_in_below_the_navigation(): void
    {
        $this->actingAs($this->user());

        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework, 'laravelusers.showBreadcrumbs' => false]);
            $response = $this->get('/users')->assertOk()->assertDontSee('class="lu-breadcrumbs"', false);
            if ($framework === 'bootstrap4') {
                $response->assertSee('class="navbar-brand" href="'.route('users').'"', false);
            } else {
                $response->assertSee('class="lu-brand" href="'.route('users').'"', false);
            }

            config(['laravelusers.showBreadcrumbs' => true]);
            $response = $this->get('/users/create')->assertOk()->assertSee('aria-label="Breadcrumbs"', false)->assertSee('aria-current="page"', false)->assertSee('Home', false)->assertSee('Create user');
            $content = $response->getContent();
            $headerPosition = strpos($content, 'class="lu-toolbar"');
            if ($headerPosition === false) {
                $headerPosition = strpos($content, 'class="navbar navbar-expand-md');
            }
            $this->assertLessThan(strpos($content, 'class="lu-breadcrumbs'), $headerPosition);
            $breadcrumbPosition = strpos($content, 'class="lu-breadcrumbs');
            $this->assertStringContainsString('<a href="'.route('users').'">Users</a>', substr($content, $breadcrumbPosition));
            $this->assertStringContainsString('<span>Create user</span>', substr($content, $breadcrumbPosition));
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
        config(['laravelusers.enableSearchUsers' => false, 'laravelusers.enablePackageBootstapAlerts' => false]);
        $this->actingAs($this->user());
        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->withSession(['success' => 'Hidden success'])->get('/users')->assertOk()->assertDontSee('id="lu-search"', false)->assertDontSee('id="search_users"', false)->assertDontSee('id="user_search_box"', false)->assertDontSee('Hidden success')->assertDontSee('id="lu-theme"', false);
        }
    }

    public function test_host_asset_switches_work_in_all_frameworks(): void
    {
        $this->actingAs($this->user());
        config(['laravelusers.appCssPublicFile' => 'host/styles.css', 'laravelusers.appJsPublicFile' => 'host/scripts.js']);
        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework, 'laravelusers.enableAppCss' => true, 'laravelusers.enableAppJs' => true]);
            $this->get('/users')->assertOk()->assertSee('host/styles.css')->assertSee('host/scripts.js');
            config(['laravelusers.enableAppCss' => false, 'laravelusers.enableAppJs' => false]);
            $this->get('/users')->assertOk()->assertDontSee('host/styles.css')->assertDontSee('host/scripts.js')->assertDontSee('css/app.css')->assertDontSee('js/app.js');
        }
    }

    public function test_theme_button_can_be_disabled_without_changing_the_configured_theme(): void
    {
        $this->actingAs($this->user());
        foreach (Frontend::FRAMEWORKS as $framework) {
            foreach (['light', 'dark', 'system'] as $theme) {
                config(['laravelusers.frontend' => $framework, 'laravelusers.theme' => $theme, 'laravelusers.themeToggle' => true]);
                $this->get('/users')->assertOk()->assertSee('id="lu-theme"', false)->assertSee('data-theme-icon="'.$theme.'"', false)->assertDontSee('<select id="lu-theme"', false);
                config(['laravelusers.themeToggle' => false]);
                $this->get('/users')->assertOk()->assertDontSee('id="lu-theme"', false)->assertSee('data-lu-theme="'.$theme.'"', false);
            }
        }
    }

    public function test_modern_validation_repopulates_non_secret_fields(): void
    {
        config(['laravelusers.frontend' => 'tailwind']);
        $this->actingAs($this->user());
        $this->from('/users/create')->post('/users', ['name' => 'Attempt', 'email' => 'invalid', 'password' => 'secret123', 'password_confirmation' => 'wrong'])->assertSessionHasErrors();
        $this->get('/users/create')->assertOk()->assertSee('value="Attempt"', false)->assertSee('aria-invalid="true"', false)->assertDontSee('value="secret123"', false);
    }

    public function test_profile_color_controls_the_gradient_and_readable_text(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        config(['laravelusers.frontend' => 'bootstrap5']);
        foreach (['#264e36' => '#fff', '#eeddbb' => '#000', '#abc' => '#000'] as $color => $text) {
            config(['laravelusers.profileCardColor' => $color]);
            $normalized = $color === '#abc' ? '#aabbcc' : $color;
            $this->get('/users/'.$user->id)->assertOk()->assertSee('--lu-profile-color: '.$normalized, false)->assertSee('--lu-profile-text: '.$text, false)->assertSee('linear-gradient(145deg');
        }
    }

    public function test_invalid_profile_colors_fall_back_without_rendering_css_or_markup(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        config(['laravelusers.frontend' => 'bootstrap5']);
        foreach (['</style><script>alert(1)</script>', '#fff; background:url(https://example.com)', '', 123, null, []] as $color) {
            config(['laravelusers.profileCardColor' => $color]);
            $this->get('/users/'.$user->id)->assertOk()->assertSee('--lu-profile-color: #2458b7', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('background:url(https://example.com)', false);
        }
    }
}
