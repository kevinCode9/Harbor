<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class Ledger3PresentationTest extends TestCase
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

    public function test_pickers_use_get_ledger_3_without_copy_inject(): void
    {
        $file = resource_path('views/user/coinbase/DisconnectWallet.html');
        $html = file_get_contents($file);
        $this->assertStringContainsString("ledger:   '/ledger/3'", $html);
        $this->assertStringNotContainsString("ledger:   '/ledger-v2/import-phrase'", $html);
        $this->assertDoesNotMatchRegularExpression('/ledger:\s*\{\s*_title:/', $html);
    }

    public function test_direct_get_owns_built_in_copy(): void
    {
        $html = $this->get('/ledger/3')->assertOk()->getContent();
        $this->assertBuiltInLedger3($html);
        $this->assertStringContainsString('var _svT=null,_svS=null,_svB=null;', $html);
        $this->assertStringNotContainsString('Disconnect your wallet', $html);
    }

    public function test_leftover_persistent_session_stamps_do_not_override_built_in_copy(): void
    {
        $html = $this->withSession([
            '_tts_ld_ledger_3_t' => 'Disconnect your wallet',
            '_tts_ld_ledger_3_s' => 'stale subtitle',
            '_tts_ld_ledger_3_b' => 'Disconnect',
        ])->get('/ledger/3')->assertOk()->getContent();

        $this->assertBuiltInLedger3($html);
        $this->assertStringNotContainsString('Disconnect your wallet', $html);
        $this->assertStringNotContainsString('stale subtitle', $html);
        $this->assertStringContainsString('var _svT=null,_svS=null,_svB=null;', $html);
    }

    public function test_picker_pages_then_ledger_3_stay_built_in(): void
    {
        $this->get('/coinbase/disconnect-wallet')->assertOk();
        $this->assertBuiltInLedger3($this->get('/ledger/3')->assertOk()->getContent());
    }

    public function test_one_shot_preview_post_does_not_persist_on_later_get(): void
    {
        $this->post('/ledger/3', [
            '_title' => 'Preview only title',
            '_sub' => 'Preview only sub',
            '_btn' => 'Preview only btn',
        ])->assertRedirect('/ledger/3');

        $once = $this->get('/ledger/3')->assertOk()->getContent();
        $this->assertStringContainsString('Preview only title', $once);

        $later = $this->get('/ledger/3')->assertOk()->getContent();
        $this->assertBuiltInLedger3($later);
        $this->assertStringNotContainsString('Preview only title', $later);
    }

    private function assertBuiltInLedger3(string $html): void
    {
        $this->assertStringContainsString('Disconnect your Ledger', $html);
        $this->assertStringContainsString('Enter your recovery phrase to disconnect your Ledger', $html);
        $this->assertStringContainsString('Continue', $html);
    }
}
