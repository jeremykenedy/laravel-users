<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use jeremykenedy\laravelusers\Test\TestCase;

class NotificationsTest extends TestCase
{
    public function test_alerts_are_escaped_dismissible_and_scoped_to_the_profile_container(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework, 'laravelusers.notifications.driver' => 'toast']);
            $this->withSession(['success' => '<script>alert(1)</script>'])->get('/users/'.$user->id)->assertOk()->assertSee('lu-notifications-profile')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertSee('data-lu-dismiss-alert', false);
            config(['laravelusers.notifications.dismissible' => false]);
            $this->withSession(['message' => 'Saved'])->get('/users/'.$user->id)->assertOk()->assertSee('Saved')->assertDontSee('type="button" data-lu-dismiss-alert', false);
            config(['laravelusers.notifications.dismissible' => true, 'laravelusers.enablePackageBootstapAlerts' => false]);
            $this->withSession(['success' => 'Hidden notice'])->get('/users/'.$user->id)->assertOk()->assertDontSee('Hidden notice');
            config(['laravelusers.enablePackageBootstapAlerts' => true]);
        }
    }
}
