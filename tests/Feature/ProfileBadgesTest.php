<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use DOMDocument;
use DOMXPath;
use jeremykenedy\laravelusers\Test\TestCase;

class ProfileBadgesTest extends TestCase
{
    public function test_profile_roles_levels_and_permissions_use_the_selected_bootstrap_badge_style(): void
    {
        $user = $this->user();
        $user->setRelation('roles', collect([(object) ['name' => 'Manager']]));
        $this->actingAs($user);
        config(['laravelusers.rolesEnabled' => true, 'laravelusers.showRoleLevels' => true]);

        foreach (['bootstrap4', 'bootstrap5'] as $framework) {
            foreach (['light', 'dark'] as $theme) {
                config(['laravelusers.frontend' => $framework, 'laravelusers.theme' => $theme]);
                $html = view('laravelusers::partials.profile-card', ['user' => $user, 'modern' => $framework === 'bootstrap5', 'roleLevel' => 3, 'directPermissions' => collect([(object) ['name' => 'users.manage']])])->render();
                $previous = libxml_use_internal_errors(true);
                $document = new DOMDocument();
                $document->loadHTML($html);
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
                $badges = (new DOMXPath($document))->query('//dl[@class="lu-profile-details"]//span[contains(concat(" ", @class, " "), " lu-badge ")]');
                $this->assertSame(5, $badges->length, $framework.' '.$theme);
                $this->assertSame(['Manager', '3', '2', '1', 'users.manage'], array_map(fn ($badge) => $badge->textContent, iterator_to_array($badges)));
                foreach ($badges as $badge) {
                    $this->assertSame('badge badge-primary lu-badge'.($framework === 'bootstrap5' ? ' bg-primary' : ''), $badge->getAttribute('class'));
                }
            }
        }
    }
}
