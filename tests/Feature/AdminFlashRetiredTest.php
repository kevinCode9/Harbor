<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Global Flash retirement — no toast owner remains.
 *
 * Current runtime files under public/js/admin/ are transitional.
 * Architectural review / death owner of this path pin:
 * POST-4I / PRE-STAGE5 SOURCE-OF-TRUTH / VITE AUDIT.
 */
class AdminFlashRetiredTest extends TestCase
{
    public function test_global_flash_mechanism_is_gone(): void
    {
        $this->assertFileDoesNotExist(public_path('js/admin/flash.js'));

        $app = file_get_contents(public_path('js/admin/app.js'));
        $this->assertNotFalse($app);
        $this->assertStringNotContainsString('flash.js', $app);
        $this->assertStringNotContainsString('window.showFlash', $app);
        $this->assertStringNotContainsString('showFlash', $app);

        foreach (['js/admin/settings.js', 'js/admin/subadmins.js', 'js/admin/notify.js', 'js/admin-dashboard.js'] as $rel) {
            $src = file_get_contents(public_path($rel));
            $this->assertNotFalse($src, $rel);
            $this->assertStringNotContainsString('showFlash', $src, $rel);
            $this->assertStringNotContainsString('flash.js', $src, $rel);
        }

        $settings = file_get_contents(public_path('js/admin/settings.js'));
        $this->assertStringContainsString('function revertPlatform', $settings);
        $this->assertStringContainsString('function revertPow', $settings);
        $this->assertStringContainsString('function revertEntry', $settings);
        $this->assertStringContainsString('function revertExit', $settings);
        $this->assertStringContainsString('updateSiteStatusUI()', $settings);
        $this->assertStringContainsString('updatePowUI()', $settings);
        $this->assertStringContainsString('applyEntryUi(entryConfirmed', $settings);
        $this->assertStringContainsString("getElementById('exit-url')", $settings);

        $sub = file_get_contents(public_path('js/admin/subadmins.js'));
        $this->assertStringContainsString('subadmin-create-err', $sub);
        $this->assertStringContainsString("btn.textContent = 'Copied!'", $sub);

        $blade = file_get_contents(resource_path('views/admin/dashboard.blade.php'));
        $this->assertStringNotContainsString('flash.js', $blade);
        $this->assertStringNotContainsString('showFlash', $blade);
    }
}
