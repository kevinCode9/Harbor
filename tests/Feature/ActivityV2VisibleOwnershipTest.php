<?php

namespace Tests\Feature;

use App\Models\UserEvent;
use App\Models\UserSession;
use App\Services\DocumentAuthority;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\Support\V2VisibleOwnershipSandbox;
use Tests\TestCase;

class ActivityV2VisibleOwnershipTest extends TestCase
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

    public function test_live_client_later_pageshow_reacquires_visible_ownership(): void
    {
        $js = DocumentAuthority::clientScript('dummy_a', 'csrf-dummy', str_repeat('ab', 16), 'page');
        $out = V2VisibleOwnershipSandbox::run($js);

        $this->assertTrue($out['analyze']['page_show_count'], 'canonical pageshow-count restore must be present');
        $this->assertTrue($out['analyze']['poll_authority_heal'], 'poll must carry MATCH vs MISMATCH self-heal');
        $this->assertFalse($out['analyze']['resume_rearms'], 'resume must not independently activate');
        $this->assertFalse($out['analyze']['persist_gated'], 'later pageshow must not require persisted');

        $stale = $out['staleSecondShow'];
        $this->assertSame('fulfilled', $stale['afterFirst']['ready']);
        $this->assertFalse($stale['afterFirst']['dead']);
        $this->assertSame($stale['afterFirst']['gen'], $stale['afterFirst']['started']);
        $this->assertSame(2, $stale['activates'], 'later pageshow must send exactly one new activate after first ownership');
        $this->assertSame(3, $stale['visits']);
        $this->assertSame(['dummy_a', 'dummy_a', 'dummy_a'], $stale['visitPages']);

        $this->assertSame(0, $out['freshGet']['activates']);
        $this->assertSame(1, $out['freshGet']['visits']);

        $this->assertSame(0, $out['backForwardFirst']['activates']);
        $this->assertSame(1, $out['backForwardFirst']['visits']);
        $this->assertSame('fulfilled', $out['backForwardFirst']['ready']);

        $this->assertSame(1, $out['resumeThenPageshow']['activates'], 'resume+pageshow must be one activate');
        $this->assertSame(0, $out['backgroundResume']['activates']);
        $this->assertSame(0, $out['backgroundResume']['visits']);

        $this->assertSame(1, $out['http409']['activates']);
        $this->assertSame(2, $out['abortRetry']['activates']);

        $this->assertSame(3, $out['cycleAAA']['activates']);
        $this->assertSame(['dummy_a', 'dummy_a', 'dummy_a', 'dummy_a'], $out['cycleAAA']['visitPages']);

        $this->assertSame(0, $out['continuousStay']['activates']);
        $this->assertSame(1, $out['continuousStay']['visits']);

        $this->assertSame(0, $out['pollMatch']['activates']);
        $this->assertSame(0, $out['pollMatch']['visits']);
        $this->assertSame(1, $out['pollMismatch']['activates']);
        $this->assertSame(1, $out['pollMismatch']['visits']);
        $this->assertSame(0, $out['pollHidden']['activates']);
        $this->assertSame(0, $out['pollHidden']['visits']);
        $this->assertSame(1, $out['pollAfterHeal']['healActivates']);
        $this->assertSame(1, $out['pollAfterHeal']['laterActivates'], 'healthy match ends the epoch; a later mismatch is a new reconcile');
        $this->assertSame(1, $out['pollAfterHeal']['laterVisits']);

        $this->assertSame(1, $out['pollStaleConsumed']['activates']);
        $this->assertSame(1, $out['pollStaleConsumed']['visits']);
        $this->assertTrue($out['pollStaleConsumed']['genBumped']);
        $this->assertTrue($out['pollStaleConsumed']['reconDone']);

        $this->assertSame(0, $out['pollInFlight']['activates']);
        $this->assertFalse($out['pollInFlight']['reconDone']);
        $this->assertTrue($out['pollInFlight']['genUnchanged']);

        $this->assertSame(1, $out['pollDeadHeal']['activates']);
        $this->assertSame(1, $out['pollDeadHeal']['visits']);
        $this->assertFalse($out['pollDeadHeal']['dead']);

        $this->assertSame(1, $out['poll409Terminal']['firstActivates']);
        $this->assertSame(0, $out['poll409Terminal']['laterActivates']);
        $this->assertTrue($out['poll409Terminal']['closed409']);

        $this->assertSame(2, $out['pollAbortNoConcurrent']['firstActivates']);
        $this->assertSame(0, $out['pollAbortNoConcurrent']['laterActivates']);
    }

    public function test_poll_returns_authority_match_boolean_without_raw_doc(): void
    {
        $token = Str::random(48);
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $doc = (string) UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame('', $doc);
        $this->postVisit($token, 'ledger_3', $doc, '/ledger/3')->assertOk();

        $bare = $this->withCookie('liveled_token', $token)->get('/api/check')->assertOk()->json();
        $this->assertSame([], $bare['commands']);
        $this->assertArrayNotHasKey('authority_match', $bare);
        $this->assertArrayNotHasKey('doc', $bare);
        $this->assertArrayNotHasKey('document_id', $bare);

        $match = $this->withCookie('liveled_token', $token)
            ->get('/api/check?page=ledger_3&doc='.$doc)
            ->assertOk()
            ->json();
        $this->assertTrue($match['authority_match']);
        $this->assertArrayNotHasKey('doc', $match);
        $this->assertArrayNotHasKey('document_id', $match);
        $this->assertSame(1, UserEvent::where('session_token', $token)->where('type', 'page_visit')->count());

        $wrongDoc = $this->withCookie('liveled_token', $token)
            ->get('/api/check?page=ledger_3&doc='.str_repeat('ab', 16))
            ->assertOk()
            ->json();
        $this->assertFalse($wrongDoc['authority_match']);

        $wrongPage = $this->withCookie('liveled_token', $token)
            ->get('/api/check?page=ledger_loading&doc='.$doc)
            ->assertOk()
            ->json();
        $this->assertFalse($wrongPage['authority_match']);

        $this->withCookie('liveled_token', $token)->get('/ledger/loading')->assertOk();
        $docL = (string) UserSession::where('session_token', $token)->value('document_id');
        $this->postVisit($token, 'ledger_loading', $docL, '/ledger/loading')->assertOk();
        $sit = $this->withCookie('liveled_token', $token)
            ->get('/api/check?page=ledger_loading&doc='.$docL)
            ->assertOk()
            ->json();
        $this->assertTrue($sit['authority_match']);
        $this->assertSame(1, UserEvent::where('session_token', $token)->where('type', 'page_visit')->where('page', 'ledger_loading')->count());

        $other = Str::random(48);
        $this->withCookie('liveled_token', $other)->get('/exodus/import')->assertOk();
        $docB = (string) UserSession::where('session_token', $other)->value('document_id');
        $this->postVisit($other, 'exodus_import', $docB, '/exodus/import')->assertOk();
        $cross = $this->withCookie('liveled_token', $other)
            ->get('/api/check?page=ledger_3&doc='.$doc)
            ->assertOk()
            ->json();
        $this->assertFalse($cross['authority_match']);
        $this->assertSame('exodus_import', $this->page($other));
        $this->assertSame('ledger_loading', $this->page($token));
        $this->assertSame(0, UserEvent::where('session_token', $other)->where('page', 'ledger_3')->count());
        $this->assertSame(0, UserEvent::where('session_token', $token)->where('page', 'exodus_import')->count());
    }

    public function test_session_ownership_visit_sequence_a_loading_a_loading_a(): void
    {
        $token = Str::random(48);
        $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk();
        $docA1 = (string) UserSession::where('session_token', $token)->value('document_id');
        $this->postVisit($token, 'coinbase_sms_code', $docA1, '/coinbase/sms-code')->assertOk();
        $this->assertSame('coinbase_sms_code', $this->page($token));

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $docL1 = (string) UserSession::where('session_token', $token)->value('document_id');
        $this->postVisit($token, 'coinbase_loading', $docL1, '/coinbase/loading')->assertOk();
        $this->assertSame('coinbase_loading', $this->page($token));

        $act2 = $this->activate($token, $docA1, '/coinbase/sms-code')->assertOk()->json();
        $this->assertTrue($act2['ok']);
        $this->postVisit($token, 'coinbase_sms_code', $act2['doc'], '/coinbase/sms-code')->assertOk();
        $this->assertSame('coinbase_sms_code', $this->page($token));

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $docL2 = (string) UserSession::where('session_token', $token)->value('document_id');
        $this->postVisit($token, 'coinbase_loading', $docL2, '/coinbase/loading')->assertOk();
        $this->assertSame('coinbase_loading', $this->page($token));

        $act3 = $this->activate($token, $act2['doc'], '/coinbase/sms-code')->assertOk()->json();
        $this->assertTrue($act3['ok']);
        $this->postVisit($token, 'coinbase_sms_code', $act3['doc'], '/coinbase/sms-code')->assertOk();
        $this->assertSame('coinbase_sms_code', $this->page($token));

        $pages = UserEvent::where('session_token', $token)
            ->where('type', 'page_visit')
            ->orderBy('id')
            ->pluck('page')
            ->all();
        $this->assertSame([
            'coinbase_sms_code',
            'coinbase_loading',
            'coinbase_sms_code',
            'coinbase_loading',
            'coinbase_sms_code',
        ], $pages);

        $other = Str::random(48);
        $this->withCookie('liveled_token', $other)->get('/coinbase/sign-in')->assertOk();
        $docB = (string) UserSession::where('session_token', $other)->value('document_id');
        $this->postVisit($other, 'coinbase_sign_in', $docB, '/coinbase/sign-in')->assertOk();
        $this->assertSame(0, UserEvent::where('session_token', $other)->where('page', 'coinbase_sms_code')->count());
        $this->assertSame(0, UserEvent::where('session_token', $token)->where('page', 'coinbase_sign_in')->count());
        $this->assertSame('coinbase_sign_in', $this->page($other));
        $this->assertSame('coinbase_sms_code', $this->page($token));
    }

    public function test_continuous_page_does_not_duplicate_visits(): void
    {
        $token = Str::random(48);
        $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk();
        $doc = (string) UserSession::where('session_token', $token)->value('document_id');
        $this->postVisit($token, 'coinbase_sms_code', $doc, '/coinbase/sms-code')->assertOk();
        $this->assertSame(1, UserEvent::where('session_token', $token)->where('type', 'page_visit')->count());
        $this->assertSame('coinbase_sms_code', $this->page($token));
    }

    private function page(string $token): string
    {
        return (string) UserSession::where('session_token', $token)->value('current_page');
    }

    private function activate(string $token, string $doc, string $path)
    {
        return $this->withCookie('liveled_token', $token)->post('/session/activate', [
            '_token' => csrf_token(),
            'doc' => $doc,
            'path' => $path,
        ]);
    }

    private function postVisit(string $token, string $page, string $doc, string $path)
    {
        return $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => $page,
            'element' => 'page',
            'value' => $path,
            'doc' => $doc,
        ]);
    }
}
