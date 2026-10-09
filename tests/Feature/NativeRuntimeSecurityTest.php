<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use jeremykenedy\laravelusers\Livewire\UsersScreen;
use jeremykenedy\laravelusers\Livewire\UserTable;
use jeremykenedy\laravelusers\Support\NativePageData;
use jeremykenedy\laravelusers\Test\TestCase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use ReflectionClass;

class NativeRuntimeSecurityTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), class_exists(LivewireServiceProvider::class) ? [LivewireServiceProvider::class] : []);
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(LivewireServiceProvider::class) || !class_exists(UsersScreen::class)) {
            $this->markTestSkipped('This regression requires the optional Livewire integration.');
        }
        View::prependNamespace('laravelusers', dirname((new ReflectionClass(NativePageData::class))->getFileName(), 2).'/resources/views');
        Livewire::component('laravelusers.users-screen', UsersScreen::class);
        Livewire::component('laravelusers.user-table', UserTable::class);
        $this->actingAs($this->user());
    }

    public function test_livewire_rejects_changes_to_the_trusted_form_envelope(): void
    {
        $page = $this->page();
        $component = Livewire::test(UsersScreen::class, ['nativePage' => $page]);
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $component->set('page.forms.delete-user.action', 'https://example.com/collect');
    }

    public function test_livewire_rejects_changes_to_authorized_table_rows(): void
    {
        $page = $this->page();
        $component = Livewire::test(UserTable::class, ['users' => $page['data']['users'], 'columns' => $page['data']['columns'], 'features' => $page['features'], 'labels' => $page['labels']]);
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $component->set('users.0.actions.0.url', '/users/999/impersonate');
    }

    public function test_livewire_only_opens_server_supplied_actions_and_never_deletes_directly(): void
    {
        $target = $this->user();
        $component = Livewire::test(UsersScreen::class, ['nativePage' => $this->page([$target])]);

        $component->call('openUserAction', 'delete', '999')->assertSet('activeForm', null);
        $component->call('openUserAction', 'impersonate', (string) $target->id)->assertSet('activeForm', null);
        $component->call('openUserAction', 'delete', (string) $target->id)
            ->assertSet('activeForm', 'delete-user')
            ->assertSet('activeAction', route('user.destroy', $target->id));
        $component->call('confirmSubmit')->assertDispatched('laravelusers-native-submit');

        $this->assertNotNull($target->fresh());
    }

    public function test_livewire_does_not_send_unlisted_ids_in_bulk_action_events(): void
    {
        config(['laravelusers.bulkActions' => true]);
        $target = $this->user();
        $page = $this->page([$target]);
        $component = Livewire::test(UserTable::class, ['users' => $page['data']['users'], 'columns' => $page['data']['columns'], 'features' => $page['features'], 'labels' => $page['labels']]);

        $component->set('selected', [(string) $target->id, '999'])
            ->call('requestBulkAction', 'delete')
            ->assertDispatched('laravelusers-bulk-action', action: 'delete', ids: [(string) $target->id]);

        $this->assertNotNull($target->fresh());
    }

    private function page(?array $users = null): array
    {
        $request = Request::create('/users');
        $request->setLaravelSession($this->app['session']->driver());
        $request->setUserResolver(fn () => $this->app['auth']->user());

        return $this->app->make(NativePageData::class)->forView('laravelusers::modern.show-users', ['users' => collect($users ?? [$this->user()])], $request);
    }
}
