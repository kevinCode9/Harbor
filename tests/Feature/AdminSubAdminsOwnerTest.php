<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 4I Slice 6b — SubAdmins frontend ownership boundary.
 *
 * Current runtime files under public/js/admin/ are transitional.
 * Architectural review / death owner of this path pin:
 * POST-4I / PRE-STAGE5 SOURCE-OF-TRUTH / VITE AUDIT.
 *
 * Do not treat public/js/admin/subadmins.js as a forever source path.
 */
class AdminSubAdminsOwnerTest extends TestCase
{
    public function test_subadmins_frontend_owner_is_not_classic_dashboard(): void
    {
        $classic = file_get_contents(public_path('js/admin-dashboard.js'));
        $this->assertNotFalse($classic);
        $this->assertStringNotContainsString('function loadSubAdmins', $classic);
        $this->assertStringNotContainsString('window.toggleSubAdmin', $classic);
        $this->assertStringNotContainsString('window.openCreateSubAdmin', $classic);
        $this->assertStringNotContainsString('liveled:shell-section-change', $classic);

        $owner = file_get_contents(public_path('js/admin/subadmins.js'));
        $this->assertNotFalse($owner);
        $this->assertStringContainsString('export function start', $owner);
        $this->assertStringContainsString('onSectionChange', $owner);
        $this->assertStringContainsString('setInterval(load, 12000)', $owner);
        $this->assertStringContainsString('navigator.clipboard.writeText', $owner);
        $this->assertStringNotContainsString('window.toggleSubAdmin', $owner);
        $this->assertStringNotContainsString('/toggle', $owner);
        $this->assertStringNotContainsString('liveled:shell-section-change', $owner);
        $this->assertStringNotContainsString('function escapeHtml', $owner);
        $this->assertStringNotContainsString('document.addEventListener(\'click\'', $owner);

        $shell = file_get_contents(public_path('js/admin/shell.js'));
        $this->assertNotFalse($shell);
        $this->assertStringContainsString('export function onSectionChange', $shell);
        $this->assertStringNotContainsString('liveled:shell-section-change', $shell);

        $app = file_get_contents(public_path('js/admin/app.js'));
        $this->assertNotFalse($app);
        $this->assertStringContainsString("from './subadmins.js", $app);
        $this->assertStringContainsString('startSubAdmins();', $app);
        $this->assertTrue(
            strpos($app, 'startSubAdmins();') < strpos($app, 'if (pendingSwitch)'),
            'startSubAdmins must subscribe before pending-switch drain'
        );
        $this->assertTrue(
            strpos($app, 'if (pendingSwitch)') < strpos($app, 'startSettings();'),
            'startSettings must remain after pending-switch drain'
        );

        $blade = file_get_contents(resource_path('views/admin/dashboard.blade.php'));
        $this->assertNotFalse($blade);
        $this->assertStringContainsString('data-subadmin-control="create-open"', $blade);
        $this->assertStringContainsString('data-subadmin-control="create-close"', $blade);
        $this->assertStringContainsString('data-subadmin-control="create-submit"', $blade);
        $this->assertStringNotContainsString('onclick="openCreateSubAdmin()', $blade);
        $this->assertStringNotContainsString('onclick="closeCreateSubAdmin()', $blade);
        $this->assertStringNotContainsString('onclick="submitCreateSubAdmin()', $blade);
    }
}
