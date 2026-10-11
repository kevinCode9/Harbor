<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 4I Slice 8 — Sessions frontend ownership boundary.
 *
 * Current runtime files under public/js/admin/ are transitional.
 * Architectural review / death owner of this path pin:
 * POST-4I / PRE-STAGE5 SOURCE-OF-TRUTH / VITE AUDIT.
 *
 * Do not treat public/js/admin/sessions.js as a forever source path.
 *
 * Do not treat 1-second HTMX polling, morph transport, 8s/20s detect
 * windows, or sessionStorage selected-token restore as final architecture.
 */
class AdminSessionsOwnerTest extends TestCase
{
    public function test_sessions_owns_list_selection_and_direct_owner_imports(): void
    {
        $owner = file_get_contents(public_path('js/admin/sessions.js'));
        $this->assertNotFalse($owner);
        $this->assertStringContainsString('export function start', $owner);
        $this->assertStringContainsString('export function selected', $owner);
        $this->assertStringContainsString('if (started) return', $owner);
        $this->assertStringContainsString("from './notify.js", $owner);
        $this->assertStringContainsString("from './clock.js", $owner);
        $this->assertStringContainsString("from './geo.js", $owner);
        $this->assertStringContainsString("from './shell.js", $owner);
        $this->assertStringContainsString("import { notify }", $owner);
        $this->assertStringContainsString("import { tick }", $owner);
        $this->assertStringContainsString("import { lookup }", $owner);
        $this->assertStringContainsString("import { show }", $owner);
        $this->assertStringContainsString("getElementById('session-list')", $owner);
        $this->assertStringContainsString('[data-sessions-control]', $owner);
        $this->assertStringContainsString("admin_selected_token", $owner);
        $this->assertStringContainsString('var LIST_CAP = 100', $owner);
        $this->assertStringContainsString('function listSwapSucceeded', $owner);
        $this->assertStringContainsString('function rowHasToken', $owner);
        $this->assertStringContainsString('sessionRows.length < LIST_CAP', $owner);
        $this->assertStringNotContainsString('setTimeout(function () { select(restoredTok); }, 0);', $owner);
        $this->assertStringContainsString("getElementById('nav-sessions-btn')", $owner);
        $this->assertStringContainsString("Object.defineProperty(window, 'selectedToken'", $owner);
        $this->assertStringNotContainsString('export function startNotify', $owner);
        $this->assertStringNotContainsString('function sendCommand', $owner);
        $this->assertStringNotContainsString('function _startEventPoll', $owner);
        $this->assertStringNotContainsString('function _vbRestoreState', $owner);
        $this->assertStringNotContainsString('const PAGE_META', $owner);
        $this->assertStringNotContainsString('function showFlowPanel', $owner);
        $this->assertStringNotContainsString('function updateFlowPanel', $owner);
        $this->assertStringNotContainsString('WebSocket', $owner);
        $this->assertStringNotContainsString('window.adminNotify', $owner);
        $this->assertStringNotContainsString('_adminPanelOpenedAt', $owner);
        $this->assertStringNotContainsString('function checkWaiting', $owner);
        $this->assertStringNotContainsString('terminateSelectedSession', $owner);
        $this->assertStringNotContainsString('function copyText', $owner);
        $this->assertStringContainsString("window._sessionsLegacy('enter'", $owner);
        $this->assertStringContainsString("window._sessionsLegacy('leave')", $owner);
        $this->assertStringContainsString("window._sessionsLegacy('refresh')", $owner);
        $this->assertStringNotContainsString('window.updateFlowPanel', $owner);
        $this->assertStringNotContainsString('window.showFlowPanel', $owner);
        $this->assertStringNotContainsString('window.hideFlowsContent', $owner);
        $this->assertStringNotContainsString('window._clearAllRpPanelStorage', $owner);
        $this->assertStringNotContainsString('window._vbRestoreState', $owner);

        $classic = file_get_contents(public_path('js/admin-dashboard.js'));
        $this->assertNotFalse($classic);
        $this->assertStringNotContainsString('let selectedToken', $classic);
        $this->assertStringNotContainsString('window.selectSession', $classic);
        $this->assertStringNotContainsString('window.backToSessions', $classic);
        $this->assertStringNotContainsString('window.headerRenameSession', $classic);
        $this->assertStringNotContainsString('window.deleteSession', $classic);
        $this->assertStringNotContainsString('window.deleteAllIdleSessions', $classic);
        $this->assertStringNotContainsString('window.togglePinSession', $classic);
        $this->assertStringNotContainsString('window.openRenameModal', $classic);
        $this->assertStringNotContainsString('let _knownTokens', $classic);
        $this->assertStringNotContainsString('let _notifiedLoading', $classic);
        $this->assertStringNotContainsString('let _initialSeedDone', $classic);
        $this->assertStringNotContainsString('let _adminPanelOpenedAt', $classic);
        $this->assertStringNotContainsString("adminNotify('New session'", $classic);
        $this->assertStringNotContainsString("adminNotify('Waiting'", $classic);
        $this->assertStringNotContainsString('window.tickTimestamps', $classic);
        $this->assertStringNotContainsString('window._fetchIpGeo = function', $classic);
        $this->assertStringNotContainsString('window.switchSection = function', $classic);
        $this->assertStringNotContainsString('window.copyText', $classic);
        $this->assertStringNotContainsString('window.terminateSelectedSession', $classic);
        $this->assertStringNotContainsString('function checkWaiting', $classic);
        $this->assertStringNotContainsString('function isLoadingPage', $classic);
        $this->assertStringNotContainsString("e.target.id === 'session-list'", $classic);
        $this->assertStringContainsString('window.sendCommand', $classic);
        $this->assertStringContainsString('window._startEventPoll', $classic);
        $this->assertStringContainsString('function _vbRestoreState', $classic);
        $this->assertStringContainsString('window._sessionsLegacy = function', $classic);
        $this->assertStringNotContainsString('window.updateFlowPanel = updateFlowPanel', $classic);
        $this->assertStringNotContainsString('window.showFlowPanel = showFlowPanel', $classic);
        $this->assertStringNotContainsString('window.hideFlowsContent = hideFlowsContent', $classic);
        $this->assertStringNotContainsString('window._clearAllRpPanelStorage =', $classic);
        $this->assertStringNotContainsString('window._vbRestoreState =', $classic);
        $this->assertStringContainsString('const PAGE_META', $classic);

        $app = file_get_contents(public_path('js/admin/app.js'));
        $this->assertNotFalse($app);
        $this->assertStringContainsString("from './sessions.js", $app);
        $this->assertStringContainsString('startSessions();', $app);
        $this->assertStringNotContainsString('window.adminNotify', $app);
        $this->assertStringNotContainsString('window.tickTimestamps', $app);
        $this->assertStringNotContainsString('window.switchSection = function', $app);
        $this->assertStringContainsString('window._fetchIpGeo = lookup', $app);
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
        $this->assertTrue(
            strpos($app, 'startNotify();') < strpos($app, 'startSessions();'),
            'startSessions must run after startNotify'
        );

        $blade = file_get_contents(resource_path('views/admin/dashboard.blade.php'));
        $this->assertNotFalse($blade);
        $this->assertStringContainsString('data-sessions-control="back"', $blade);
        $this->assertStringContainsString('data-sessions-control="rename"', $blade);
        $this->assertStringContainsString('data-sessions-control="rename-submit"', $blade);
        $this->assertStringContainsString('data-sessions-control="rename-close"', $blade);
        $this->assertStringContainsString('/js/admin/app.js?v=272', $blade);
        $this->assertStringContainsString('/js/admin-dashboard.js?v=270', $blade);
        $this->assertStringContainsString("./sessions.js?v=272", $app);
        $this->assertStringNotContainsString('onclick="backToSessions()"', $blade);
        $this->assertStringNotContainsString('onclick="headerRenameSession()"', $blade);
        $this->assertStringNotContainsString('onclick="submitRename()"', $blade);
        $this->assertStringNotContainsString('onclick="closeRenameModal()"', $blade);

        $list = file_get_contents(resource_path('views/admin/partials/session-list.blade.php'));
        $this->assertNotFalse($list);
        $this->assertStringContainsString('data-sessions-control="select"', $list);
        $this->assertStringContainsString('data-sessions-control="pin"', $list);
        $this->assertStringContainsString('data-sessions-control="rename"', $list);
        $this->assertStringContainsString('data-sessions-control="delete"', $list);
        $this->assertStringContainsString('data-sessions-control="delete-idle"', $list);
        $this->assertStringNotContainsString('onclick="selectSession(', $list);
        $this->assertStringNotContainsString('onclick="togglePinSession(', $list);
        $this->assertStringNotContainsString('onclick="openRenameModal(', $list);
        $this->assertStringNotContainsString('onclick="deleteSession(', $list);
        $this->assertStringNotContainsString('deleteAllIdleSessions', $list);

        $settings = file_get_contents(public_path('js/admin/settings.js'));
        $this->assertNotFalse($settings);
        $this->assertStringNotContainsString('sessions.js', $settings);
        $this->assertStringNotContainsString('selectedToken', $settings);
        $this->assertStringNotContainsString('admin_selected_token', $settings);

        $notify = file_get_contents(public_path('js/admin/notify.js'));
        $this->assertNotFalse($notify);
        $this->assertStringContainsString('export function notify', $notify);
        $this->assertStringNotContainsString('sessions.js', $notify);
        $this->assertStringNotContainsString('selectedToken', $notify);
    }
}
