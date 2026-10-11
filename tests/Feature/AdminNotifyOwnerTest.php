<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 4I Slice 7 — Notify frontend ownership boundary.
 *
 * Current runtime files under public/js/admin/ are transitional.
 * Architectural review / death owner of this path pin:
 * POST-4I / PRE-STAGE5 SOURCE-OF-TRUTH / VITE AUDIT.
 *
 * Do not treat public/js/admin/notify.js as a forever source path.
 */
class AdminNotifyOwnerTest extends TestCase
{
    public function test_notify_owns_preferences_audio_and_semantic_toggles(): void
    {
        $owner = file_get_contents(public_path('js/admin/notify.js'));
        $this->assertNotFalse($owner);
        $this->assertStringContainsString('export function start', $owner);
        $this->assertStringContainsString('export function notify', $owner);
        $this->assertStringContainsString("localStorage.getItem('liveled_notif_' + key)", $owner);
        $this->assertStringContainsString("localStorage.setItem('liveled_notif_' + key", $owner);
        $this->assertStringContainsString("loadPref('session', true)", $owner);
        $this->assertStringContainsString("loadPref('loading', true)", $owner);
        $this->assertStringContainsString('var notifVolume = 85', $owner);
        $this->assertStringContainsString('{ f: 660, t: 0 }, { f: 880, t: 0.28 }, { f: 1100, t: 0.56 }', $owner);
        $this->assertStringContainsString('{ f: 520, t: 0 }, { f: 700, t: 0.3 }, { f: 880, t: 0.6 }, { f: 1040, t: 0.9 }', $owner);
        $this->assertStringNotContainsString('flash.js', $owner);
        $this->assertStringNotContainsString('showFlash', $owner);
        $this->assertStringContainsString("getElementById('view-settings')", $owner);
        $this->assertStringContainsString('[data-notify-control]', $owner);
        $this->assertStringContainsString('if (started) return', $owner);
        $this->assertStringNotContainsString('new Notification', $owner);
        $this->assertStringNotContainsString('Notification.requestPermission', $owner);
        $this->assertStringNotContainsString('notif-alert', $owner);
        $this->assertStringNotContainsString('notif-bell', $owner);
        $this->assertStringNotContainsString('requestNotifPermission', $owner);
        $this->assertStringNotContainsString('showNotifAlert', $owner);
        $this->assertStringNotContainsString('window.adminNotify', $owner);
        $this->assertStringNotContainsString('window.requestNotifPermission', $owner);
        $this->assertStringNotContainsString('window.onNotifChange', $owner);

        $classic = file_get_contents(public_path('js/admin-dashboard.js'));
        $this->assertNotFalse($classic);
        $this->assertStringNotContainsString('let _knownTokens', $classic);
        $this->assertStringNotContainsString("adminNotify('New session'", $classic);
        $this->assertStringNotContainsString("adminNotify('Waiting'", $classic);
        $this->assertStringNotContainsString('function adminNotify', $classic);
        $this->assertStringNotContainsString('function playAlert', $classic);
        $this->assertStringNotContainsString('function showNotifAlert', $classic);
        $this->assertStringNotContainsString('function updateNotifBell', $classic);
        $this->assertStringNotContainsString('requestNotifPermission', $classic);
        $this->assertStringNotContainsString('onNotifChange', $classic);
        $this->assertStringNotContainsString('_activeAlerts', $classic);
        $this->assertStringNotContainsString('new Notification', $classic);
        $this->assertStringNotContainsString('notif-alert', $classic);

        $app = file_get_contents(public_path('js/admin/app.js'));
        $this->assertNotFalse($app);
        $this->assertStringContainsString("from './notify.js", $app);
        $this->assertStringContainsString('startNotify()', $app);
        $this->assertStringNotContainsString('window.adminNotify', $app);
        $this->assertTrue(
            strpos($app, 'startShell();') < strpos($app, 'startSubAdmins();'),
            'startShell must remain before startSubAdmins'
        );
        $this->assertTrue(
            strpos($app, 'startSubAdmins();') < strpos($app, 'if (pendingSwitch)'),
            'startSubAdmins must remain before pending-switch drain'
        );
        $this->assertTrue(
            strpos($app, 'if (pendingSwitch)') < strpos($app, 'startSettings();'),
            'startSettings must remain after pending-switch drain'
        );
        $this->assertTrue(
            strpos($app, 'startSettings();') < strpos($app, 'startNotify();'),
            'startNotify must remain after startSettings'
        );

        $settings = file_get_contents(public_path('js/admin/settings.js'));
        $this->assertNotFalse($settings);
        $this->assertStringNotContainsString('notify', $settings);
        $this->assertStringNotContainsString('notif', $settings);
        $this->assertStringNotContainsString('adminNotify', $settings);
        $this->assertStringNotContainsString('liveled_notif_', $settings);

        $blade = file_get_contents(resource_path('views/admin/dashboard.blade.php'));
        $this->assertNotFalse($blade);
        $this->assertStringContainsString('data-notify-control="session"', $blade);
        $this->assertStringContainsString('data-notify-control="loading"', $blade);
        $this->assertStringContainsString("./notify.js?v=266", $app);
        $this->assertStringContainsString('/js/admin/app.js?v=272', $blade);
        $this->assertStringContainsString('/js/admin-dashboard.js?v=270', $blade);
        $this->assertStringNotContainsString('onchange="onNotifChange()"', $blade);
        $this->assertStringNotContainsString('notif-bell', $blade);
        $this->assertStringNotContainsString('requestNotifPermission', $blade);

        $css = file_get_contents(public_path('css/admin-dashboard.css'));
        $this->assertNotFalse($css);
        $this->assertStringNotContainsString('notif-alert', $css);
        $this->assertStringNotContainsString('slideInLeft', $css);
        $this->assertStringNotContainsString('alertPulse', $css);
    }
}
