<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Http\Request;
use jeremykenedy\laravelusers\Livewire\UsersScreen;
use jeremykenedy\laravelusers\Livewire\UserTable;
use jeremykenedy\laravelusers\Support\NativePageData;
use jeremykenedy\laravelusers\Test\TestCase;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;

class LivewireScreensTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return class_exists(LivewireServiceProvider::class) ? array_merge(parent::getPackageProviders($app), [LivewireServiceProvider::class]) : parent::getPackageProviders($app);
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(Livewire::class)) {
            $this->markTestSkipped('Livewire is an optional frontend dependency.');
        }
        Livewire::component('laravelusers.users-screen', UsersScreen::class);
        Livewire::component('laravelusers.user-table', UserTable::class);
    }

    public function test_table_filters_sorts_selects_and_changes_view_without_mutating_users(): void
    {
        $actor = $this->user();
        $first = $this->user(['name' => 'Alpha']);
        $last = $this->user(['name' => 'Zulu']);
        $this->actingAs($actor);
        config(['laravelusers.tableSorting' => true, 'laravelusers.tableFiltering' => true, 'laravelusers.columnVisibility' => true, 'laravelusers.tableViewToggle' => true, 'laravelusers.bulkActions' => true]);
        $page = $this->page('laravelusers::modern.show-users', ['users' => collect([$first, $last])]);
        $table = Livewire::test(UserTable::class, ['users' => $page['data']['users'], 'columns' => $page['data']['columns'], 'features' => $page['features'], 'labels' => $page['labels']]);

        $table->call('sortBy', 'name')->assertSeeInOrder(['Alpha', 'Zulu'])
            ->set('filter', 'ALPHA')->assertSee('Alpha')->assertDontSee('Zulu')
            ->call('selectAll')->assertSet('selected', [(string) $first->id])
            ->set('selected', [(string) $first->id, '999999'])->call('requestBulkAction', 'delete')
            ->assertDispatched('laravelusers-bulk-action', action: 'delete', ids: [(string) $first->id])
            ->call('setMode', 'cards')->assertSee('lu-native-user-card', false)
            ->call('toggleColumn', 'email')->assertSet('hiddenColumns', ['email'])
            ->call('sortBy', 'private_token')->assertSet('sort', 'name');
        $this->assertDatabaseHas('users', ['id' => $first->id, 'name' => 'Alpha']);
    }

    public function test_screen_renders_native_forms_and_uses_only_trusted_row_actions(): void
    {
        $actor = $this->user();
        $target = $this->user(['name' => 'Target']);
        $this->actingAs($actor);
        $page = $this->page('laravelusers::modern.show-users', ['users' => collect([$target])]);

        Livewire::test(UsersScreen::class, ['nativePage' => $page])
            ->call('openUserAction', 'delete', '999999')->assertSet('activeForm', null)
            ->call('openUserAction', 'unknown', (string) $target->id)->assertSet('activeForm', null)
            ->call('openUserAction', 'delete', (string) $target->id)
            ->assertSet('activeForm', 'delete-user')->assertSet('activeAction', route('user.destroy', $target->id))
            ->assertSee('aria-modal="true"', false)
            ->call('confirmSubmit')->assertDispatched('laravelusers-native-submit', form: 'delete-user', dialog: true)
            ->call('closeDialog')->assertSet('activeForm', null);
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_account_confirmation_keeps_password_private_and_requires_the_exact_phrase(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $page = $this->page('laravelusers::account.page', ['user' => $user, 'accountEditable' => true, 'fullName' => 'Account Person']);

        $screen = Livewire::test(UsersScreen::class, ['nativePage' => $page])
            ->assertSee('Account Person')->assertSee($user->email)
            ->call('openSettingsAction', 'account-delete')
            ->set('values.account-delete.current_password', 'temporary-password')
            ->set('values.account-delete.confirmation', 'Delete')
            ->call('confirmSubmit')->assertNotDispatched('laravelusers-native-submit');
        $screen->set('values.account-delete.confirmation', 'delete')->call('confirmSubmit')
            ->assertDispatched('laravelusers-native-submit', form: 'account-delete', dialog: true)
            ->call('closeDialog')->assertSet('values.account-delete.current_password', '')
            ->assertSet('values.account-delete.confirmation', '')
            ->call('openSettingsAction', 'account-delete')->assertSet('values.account-delete.confirmation', '');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_edit_screen_preserves_nested_field_names_and_server_errors(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $page = $this->page('laravelusers::modern.edit-user', ['user' => $user, 'rolesEnabled' => false]);
        $page['forms']['user']['errors'] = ['email' => ['This address already belongs to a user.']];

        Livewire::test(UsersScreen::class, ['nativePage' => $page])
            ->assertSee('This address already belongs to a user.')
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('value="PUT"', false)
            ->set('values.user.name', 'Edited Name')->call('prepareSubmit', 'user')->assertSet('activeForm', 'user')
            ->call('confirmSubmit')->assertDispatched('laravelusers-native-submit', form: 'user', dialog: true);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => $user->name]);
    }

    public function test_apply_all_confirmation_uses_current_account_access_selections_without_writing_them(): void
    {
        (require dirname(__DIR__, 2).'/src/database/accounts/2026_10_08_182816_create_laravelusers_account_preferences_table.php')->up();
        $user = $this->user();
        $this->actingAs($user);
        config(['laravelusers.settings.enabled' => true]);
        $page = $this->page('laravelusers::modern.settings', ['settingsAvailable' => true]);

        Livewire::test(UsersScreen::class, ['nativePage' => $page])
            ->set('values.accounts.enabled', true)
            ->call('openSettingsAction', 'accounts-apply-enabled')
            ->assertSet('values.accounts-apply-enabled.enabled', true)
            ->assertSet('values.accounts-apply-enabled.confirmation', '')
            ->set('values.accounts-apply-enabled.confirmation', 'change')
            ->call('confirmSubmit')->assertDispatched('laravelusers-native-submit', form: 'accounts-apply-enabled', dialog: true)
            ->call('closeDialog')->assertSet('values.accounts-apply-enabled.confirmation', '');
        $this->assertDatabaseCount('laravelusers_account_preferences', 0);
    }

    private function page(string $view, array $data): array
    {
        $request = Request::create('/users');
        $request->setLaravelSession($this->app['session']->driver());
        $request->setUserResolver(fn () => $this->app['auth']->user());

        return $this->app->make(NativePageData::class)->forView($view, $data, $request);
    }
}
