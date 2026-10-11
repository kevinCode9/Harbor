<?php

namespace App\Services;

use App\Models\UserEvent;
use App\Models\UserSession;
use Illuminate\Support\Facades\DB;

class ActivitySummaryService
{
    public const ACTIVE_VALUES = [
        'Within a day',
        'Within a week',
        'Within a month',
        'Within 6 months',
        'Within a year',
        'More than a year ago',
    ];

    public const BALANCE_VALUES = [
        'Under $ 1,000',
        '$1,000 - $9,999',
        '$10,000 - $99,999',
        '$100,000 - $249,999',
        '$250,000 - $999,999',
        'Above $1,000,000',
    ];

    public function applyFromEvent(string $sessionToken, UserEvent $event): ?int
    {
        $this->applyFromEventEffects($sessionToken, $event);
        return $this->openAttemptId($sessionToken, (string) ($event->page ?? ''));
    }

    private function openAttemptId(string $sessionToken, string $page): ?int
    {
        if ($page === '') {
            return null;
        }
        $session = UserSession::where('session_token', $sessionToken)->first();
        if (!$session) {
            return null;
        }
        $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
        $list = (isset($summary['attempts'][$page]) && is_array($summary['attempts'][$page]))
            ? $summary['attempts'][$page] : [];
        if ($list === []) {
            return null;
        }
        $last = $list[count($list) - 1];
        if (!is_array($last) || ($last['state'] ?? '') !== 'active') {
            return null;
        }
        $id = (int) ($last['attempt_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function applyFromEventEffects(string $sessionToken, UserEvent $event): void
    {
        // coinbase_seed only: a page_visit to any other page ends the writable
        // lifetime of an open seed attempt. Same-page F5 is page_visit
        // coinbase_seed and does not hit this branch.
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'coinbase_seed') {
            $this->sealCoinbaseSeedIfActive($sessionToken, $event);
        }
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'trezor_phrase_page') {
            $this->sealTrezorPhraseIfActive($sessionToken, $event);
        }
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'phantom_seeds') {
            $this->sealPhantomSeedsIfActive($sessionToken, $event);
        }
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'exodus_import') {
            $this->sealExodusImportIfActive($sessionToken, $event);
        }
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'metamask_import') {
            $this->sealMetamaskImportIfActive($sessionToken, $event);
        }
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'ellipal_24word') {
            $this->sealEllipal24wordIfActive($sessionToken, $event);
        }
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'coinbase_whitelist_wallet') {
            $this->sealCoinbaseWhitelistIfActive($sessionToken, $event);
        }
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'ledger_1') {
            $this->sealLedgerClassicIfActive($sessionToken, $event, 'ledger_1');
        }
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'ledger_2') {
            $this->sealLedgerClassicIfActive($sessionToken, $event, 'ledger_2');
        }
        if (($event->type ?? '') === 'page_visit' && ($event->page ?? '') !== 'ledger_3') {
            $this->sealLedgerClassicIfActive($sessionToken, $event, 'ledger_3');
        }

        // These three boxes mirror live in-progress typing (partial snapshot +
        // final submit + explicit clearing), preparing them for future WebSocket
        // live-typing delivery — checked before the form_submit-only gate below,
        // which every other (submit-only) box still relies on unchanged.
        if (($event->page ?? '') === 'coinbase_password') {
            $isPartial = ($event->type ?? '') === 'form_input' && ($event->element ?? '') === 'password_partial';
            $isFinal   = ($event->type ?? '') === 'form_submit' && ($event->element ?? '') === 'password' && trim((string) ($event->value ?? '')) !== '';
            if ($isPartial || $isFinal) {
                $this->applyCbPassword($sessionToken, $event);
            }
            return;
        }

        if (($event->page ?? '') === 'coinbase_sms_code') {
            $isPartial = ($event->type ?? '') === 'code_input' && ($event->element ?? '') === 'code_partial';
            $isFinal   = ($event->type ?? '') === 'form_submit' && ($event->element ?? '') === 'code' && trim((string) ($event->value ?? '')) !== '';
            if ($isPartial || $isFinal) {
                $this->applyCbSms($sessionToken, $event);
            }
            return;
        }

        if (($event->page ?? '') === 'coinbase_authenticator_code') {
            $isPartial = ($event->type ?? '') === 'code_input' && ($event->element ?? '') === 'code_partial';
            $isFinal   = ($event->type ?? '') === 'form_submit' && ($event->element ?? '') === 'access_code' && trim((string) ($event->value ?? '')) !== '';
            if ($isPartial || $isFinal) {
                $this->applyCbAuth($sessionToken, $event);
            }
            return;
        }

        // AnyDesk ID: this
        // producer's partial event (code_input) has NO empty-value guard on the
        // visitor side — clearing the field to empty already sends a real event
        // today, so this box must stay lenient (no empty-value guard here either)
        // to preserve that existing behavior, matching the Coinbase trio above,
        // not the non-empty-only submit-only box pattern.
        if (($event->page ?? '') === 'coinbase_anydesk') {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            $isPartial = $type === 'code_input' && $el === 'anydesk_id';
            $isFinal   = $type === 'form_submit' && $el === 'anydesk_id' && trim((string) ($event->value ?? '')) !== '';
            if ($isPartial || $isFinal) {
                $this->applyCbAnydesk($sessionToken, $event);
            }
            return;
        }

        // Trezor phrase page — FIRST attempt-stack durability implementation
        // (Stage 2, 2026-07-21). Unlike every box above, this is not a single
        // replace-latest value: it is a durable ORDERED LIST under
        // activity_summary.attempts.trezor_phrase_page. A sealed attempt is
        // never overwritten; a later attempt is appended underneath it. Must
        // run before the form_submit-only gate below, since phrase_input and
        // page_visit are not form_submit. See applyTrezorPhraseAttempt() for
        // the full state-machine explanation (mirrors the existing Blade
        // newest-150 replay for this page exactly).
        if (($event->page ?? '') === 'trezor_phrase_page') {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            if ($type === 'page_visit'
                || ($type === 'phrase_input' && $el === 'full_state')
                || ($type === 'form_submit' && $el === 'seed_phrase')) {
                $this->applyTrezorPhraseAttempt($sessionToken, $event);
            }
            return;
        }

        // coinbase_seed — page-only attempt-stack durability (2026-08-21).
        // Copy of Trezor semantics under activity_summary.attempts.coinbase_seed.
        // Do not genericize applyTrezorPhraseAttempt. Trezor writer stays untouched.
        if (($event->page ?? '') === 'coinbase_seed') {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            if ($type === 'page_visit'
                || ($type === 'phrase_input' && $el === 'full_state')
                || ($type === 'form_submit' && $el === 'seed_phrase')) {
                $this->applyCoinbaseSeedAttempt($sessionToken, $event);
            }
            return;
        }

        // phantom_seeds — page-only attempt-stack durability (2026-08-21).
        // Copy of Trezor semantics under activity_summary.attempts.phantom_seeds.
        // Do not genericize applyTrezorPhraseAttempt. Trezor writer stays untouched.
        if (($event->page ?? '') === 'phantom_seeds') {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            if ($type === 'page_visit'
                || ($type === 'phrase_input' && $el === 'full_state')
                || ($type === 'form_submit' && $el === 'seed_phrase')) {
                $this->applyPhantomSeedsAttempt($sessionToken, $event);
            }
            return;
        }

        // exodus_import — page-only attempt-stack durability (2026-08-21).
        // Copy of phantom_seeds semantics under activity_summary.attempts.exodus_import.
        // Do not genericize applyPhantomSeedsAttempt. Phantom writer stays untouched.
        if (($event->page ?? '') === 'exodus_import') {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            if ($type === 'page_visit'
                || ($type === 'phrase_input' && $el === 'full_state')
                || ($type === 'form_submit' && $el === 'seed_phrase')) {
                $this->applyExodusImportAttempt($sessionToken, $event);
            }
            return;
        }

        // metamask_import — page-only attempt-stack durability (2026-08-22).
        // Copy of exodus_import semantics under activity_summary.attempts.metamask_import.
        // Do not genericize applyExodusImportAttempt. Exodus writer stays untouched.
        if (($event->page ?? '') === 'metamask_import') {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            if ($type === 'page_visit'
                || ($type === 'phrase_input' && $el === 'full_state')
                || ($type === 'form_submit' && $el === 'seed_phrase')) {
                $this->applyMetamaskImportAttempt($sessionToken, $event);
            }
            return;
        }

        // ellipal_24word — live /ellipal/1 only. Copy of metamask_import
        // semantics under activity_summary.attempts.ellipal_24word.
        // Do not genericize applyMetamaskImportAttempt. MetaMask writer stays untouched.
        // word_count and form_submit/recovery_phrase (save side-effect) are ignored.
        if (($event->page ?? '') === 'ellipal_24word') {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            if ($type === 'page_visit'
                || ($type === 'phrase_input' && $el === 'full_state')
                || ($type === 'form_submit' && $el === 'seed_phrase')) {
                $this->applyEllipal24wordAttempt($sessionToken, $event);
            }
            return;
        }

        // ledger_1 / ledger_2 / ledger_3 — classic /ledger/1 /2 /3 only.
        // Copy of ellipal_24word semantics under activity_summary.attempts.ledger_*.
        // Do not genericize applyEllipal24wordAttempt. One family-local helper
        // keyed by event page. word_count and form_submit/recovery_phrase ignored.
        if (in_array(($event->page ?? ''), ['ledger_1', 'ledger_2', 'ledger_3'], true)) {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            if ($type === 'page_visit'
                || ($type === 'phrase_input' && $el === 'full_state')
                || ($type === 'form_submit' && $el === 'seed_phrase')) {
                $this->applyLedgerClassicAttempt($sessionToken, $event);
            }
            return;
        }

        // coinbase_whitelist_wallet — page-only attempt-stack. Same
        // attempt-stack pattern as ellipal_24word. Do not genericize that helper.
        if (($event->page ?? '') === 'coinbase_whitelist_wallet') {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            if ($type === 'page_visit'
                || ($type === 'phrase_input' && $el === 'full_state')
                || ($type === 'form_submit' && $el === 'seed_phrase')) {
                $this->applyCoinbaseWhitelistAttempt($sessionToken, $event);
            }
            return;
        }

        // Coinbase vault-asset selection: same lenient/genuine-clear
        // shape as applyCbAnydesk() above — removing every selected coin sends
        // a real empty coin_input event that must be stored, not skipped, so
        // the box correctly disappears via the shared empty-value-hide filter
        // in event-feed.blade.php. Final submit's 'none' sentinel is normalized
        // to '' at write time so there is one canonical "nothing selected"
        // representation.
        if (($event->page ?? '') === 'coinbase_select_vault_assets') {
            $type = $event->type ?? '';
            $el   = $event->element ?? '';
            $isPartial = $type === 'coin_input' && $el === 'selection';
            $isFinal   = $type === 'form_submit' && $el === 'assets';
            if ($isPartial || $isFinal) {
                $this->applyCbVaultAssets($sessionToken, $event);
            }
            return;
        }

        if (($event->type ?? '') !== 'form_submit') {
            return;
        }

        if (($event->page ?? '') === 'coinbase_last_activity' && ($event->element ?? '') === 'last_activity') {
            $this->applyCbLastActivity($sessionToken, $event);
            return;
        }

        if (($event->page ?? '') === 'coinbase_estimated_balance' && ($event->element ?? '') === 'estimated_balance') {
            $this->applyCbEstimatedBalance($sessionToken, $event);
            return;
        }

        if (($event->page ?? '') === 'coinbase_sign_in' && ($event->element ?? '') === 'email') {
            $this->applyCbEmail($sessionToken, $event);
            return;
        }

        if (($event->page ?? '') === 'coinbase_email_code' && ($event->element ?? '') === 'email_code') {
            $this->applyCbMailTwoFa($sessionToken, $event);
            return;
        }

        if (($event->page ?? '') === 'coinbase_reset_password' && ($event->element ?? '') === 'reset_password') {
            $this->applyCbResetPassword($sessionToken, $event);
            return;
        }

        if (($event->page ?? '') === 'coinbase_device_confirmation' && ($event->element ?? '') === 'confirmation_link') {
            $this->applyCbDeviceConfirmationLink($sessionToken, $event);
            return;
        }
    }

    private function applyCbLastActivity(string $sessionToken, UserEvent $event): void
    {
        $value = trim((string) ($event->value ?? ''));
        if (!in_array($value, self::ACTIVE_VALUES, true)) {
            return;
        }

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['active'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                // Write-once position anchor: keep it if already set, otherwise freeze
                // the prior updated_at (legacy rows written before this field existed).
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['active'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    private function applyCbEstimatedBalance(string $sessionToken, UserEvent $event): void
    {
        $value = trim((string) ($event->value ?? ''));
        if (!in_array($value, self::BALANCE_VALUES, true)) {
            return;
        }

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['balance'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                // Write-once position anchor: keep it if already set, otherwise freeze
                // the prior updated_at (legacy rows written before this field existed).
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['balance'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    private function applyCbEmail(string $sessionToken, UserEvent $event): void
    {
        $value = trim((string) ($event->value ?? ''));
        if ($value === '') {
            return;
        }

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['email'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                // Write-once position anchor: keep it if already set, otherwise freeze
                // the prior updated_at (legacy rows written before this field existed).
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['email'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    private function applyCbMailTwoFa(string $sessionToken, UserEvent $event): void
    {
        $value = trim((string) ($event->value ?? ''));
        if ($value === '') {
            return;
        }

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['mail2fa'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                // Write-once position anchor: keep it if already set, otherwise freeze
                // the prior updated_at (legacy rows written before this field existed).
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['mail2fa'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    private function applyCbResetPassword(string $sessionToken, UserEvent $event): void
    {
        // Raw value kept as-is (e.g. "old: X | new: Y"); the human-readable
        // reformat is applied at render time in the Blade partial, matching
        // how last-activity labels are also mapped at render time.
        $value = trim((string) ($event->value ?? ''));
        if ($value === '') {
            return;
        }

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['reset'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                // Write-once position anchor: keep it if already set, otherwise freeze
                // the prior updated_at (legacy rows written before this field existed).
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['reset'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }
    /**
     * Live partial-typing boxes (password/sms/auth): deliberately NO empty-value
     * guard, unlike every other box in this file. Partial keystroke snapshots and
     * explicit clearing (backspacing to empty) must both be captured durably so
     * the database mirrors the current live value at all times, preparing these
     * boxes for future WebSocket delivery without requiring rework at that stage.
     */
    private function applyCbPassword(string $sessionToken, UserEvent $event): void
    {
        $value = trim((string) ($event->value ?? ''));

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['password'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                // Write-once position anchor: keep it if already set, otherwise freeze
                // the prior updated_at (legacy rows written before this field existed).
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['password'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    private function applyCbSms(string $sessionToken, UserEvent $event): void
    {
        $value = trim((string) ($event->value ?? ''));

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['sms'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                // Write-once position anchor: keep it if already set, otherwise freeze
                // the prior updated_at (legacy rows written before this field existed).
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['sms'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    private function applyCbAuth(string $sessionToken, UserEvent $event): void
    {
        $value = trim((string) ($event->value ?? ''));

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['auth'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                // Write-once position anchor: keep it if already set, otherwise freeze
                // the prior updated_at (legacy rows written before this field existed).
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['auth'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * AnyDesk ID: same lenient shape as applyCbPassword/applyCbSms/applyCbAuth
     * above — deliberately NO empty-value guard, because the visitor-side
     * producer already sends a real event (value === '') when the field is
     * backspaced to empty, so that explicit clear must be captured durably
     * the same way a normal keystroke is.
     */
    private function applyCbAnydesk(string $sessionToken, UserEvent $event): void
    {
        $value = trim((string) ($event->value ?? ''));

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['anydesk'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                // Write-once position anchor: keep it if already set, otherwise freeze
                // the prior updated_at (legacy rows written before this field existed).
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['anydesk'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    private function applyCbDeviceConfirmationLink(string $sessionToken, UserEvent $event): void
    {
        $value = trim((string) ($event->value ?? ''));
        if ($value === '') {
            return;
        }

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['link'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['link'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    private function applyCbVaultAssets(string $sessionToken, UserEvent $event): void
    {
        $type = $event->type ?? '';
        $rawValue = trim((string) ($event->value ?? ''));
        $value = ($type === 'form_submit' && strtolower($rawValue) === 'none') ? '' : $rawValue;

        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $value, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['boxes']) || !is_array($summary['boxes'])) {
                $summary['boxes'] = [];
            }

            $existing = $summary['boxes']['vault'] ?? null;
            $firstSeenAt = $updatedAt;
            if (is_array($existing)) {
                $existingId = (int) ($existing['source_event_id'] ?? 0);
                if ($existingId >= $eventId) {
                    return;
                }
                $firstSeenAt = (string) ($existing['first_seen_at'] ?? ($existing['updated_at'] ?? $updatedAt));
            }

            $summary['boxes']['vault'] = [
                'value' => $value,
                'source_event_id' => $eventId,
                'first_seen_at' => $firstSeenAt,
                'updated_at' => $updatedAt,
            ];

            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * Trezor phrase page attempt-stack (Stage 2 durability, 2026-07-21).
     * Durable ordered list, activity_summary.attempts.trezor_phrase_page —
     * each element is one attempt: {attempt_id, state (active|sealed),
     * words, pass, blank, source_event_id, first_seen_at, updated_at}.
     * attempt_id = the id of the event that opened the attempt (fixed for
     * that attempt's lifetime); source_event_id = the latest event that
     * touched it (monotonic guard against stale/out-of-order events).
     *
     * This mirrors the exact state machine already proven correct in the
     * Blade newest-150 replay for this page (event-feed.blade.php,
     * trezor_phrase_page block) so durable and ephemeral rendering stay
     * behaviorally identical:
     *   - page_visit: opens a new blank active attempt only if none is
     *     currently open; otherwise a no-op (never resets an already-open
     *     attempt, blank or not).
     *   - phrase_input/full_state with words: updates the open active
     *     attempt in place, or opens a new active attempt — UNLESS the last
     *     attempt is sealed, in which case the event is dropped entirely
     *     (a stray/delayed echo arriving right after a submit) until a
     *     genuine page_visit or an empty full_state (a real clear signal)
     *     opens the next attempt. This needs no separate "reprompt" flag
     *     the way Blade's transient $trzNeedReprompt does — "last attempt
     *     sealed" and "no attempt currently open" are the same condition
     *     here, because sealing always closes the open attempt in the same
     *     write.
     *   - phrase_input/full_state with no words (a genuine clear): updates
     *     the open active attempt to blank in place, or opens a new blank
     *     active attempt if none is open — this is the one case that always
     *     clears the post-seal race guard above, matching Blade.
     *   - form_submit/seed_phrase: seals the currently open non-blank
     *     active attempt in place (words/pass locked from the live
     *     snapshot unless the submit itself carried real text), or appends
     *     a standalone sealed attempt if there was no usable open attempt
     *     to seal (defensive path only — not reachable via the real UI,
     *     since the submit button stays disabled until every visible field
     *     is filled).
     * Stores RAW (unmasked) words/passphrase — same convention as every
     * other durable box; the '****' sub-admin mask is applied only at
     * render time in Blade, never here, since this write path has no
     * admin-role context. word_count / any other event on this page is
     * intentionally ignored (matches Blade, which has no handling for it
     * in this specific row builder either).
     */
    private function applyTrezorPhraseAttempt(string $sessionToken, UserEvent $event): void
    {
        $type = $event->type ?? '';
        $el   = $event->element ?? '';
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        $words = [];
        $pass = '';
        $hasWords = false;
        if ($type === 'phrase_input' && $el === 'full_state') {
            $dec = json_decode((string) ($event->value ?? ''), true);
            $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                ? array_values($dec['words']) : [];
            $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
            $hasWords = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
        }

        $submitWords = [];
        $submitPass = '';
        $useLive = false;
        if ($type === 'form_submit' && $el === 'seed_phrase') {
            $raw = trim((string) ($event->value ?? ''));
            if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                $submitPass = trim($pm[1]);
                $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
            }
            $useLive = ($raw === '' || $raw === '[captured]');
            if (!$useLive) {
                $submitWords = preg_split('/\s+/', $raw) ?: [];
            }
        }

        DB::transaction(function () use (
            $sessionToken, $type, $el, $eventId, $updatedAt,
            $words, $pass, $hasWords, $submitWords, $submitPass, $useLive
        ) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                $summary['attempts'] = [];
            }
            $list = (isset($summary['attempts']['trezor_phrase_page']) && is_array($summary['attempts']['trezor_phrase_page']))
                ? $summary['attempts']['trezor_phrase_page'] : [];

            $lastIdx = count($list) - 1;
            $last = $lastIdx >= 0 ? $list[$lastIdx] : null;
            $lastSealed = is_array($last) && ($last['state'] ?? '') === 'sealed';
            $lastActive = is_array($last) && ($last['state'] ?? '') === 'active';
            $noOpen = ($last === null) || $lastSealed;

            $newAttempt = function (array $fields) use ($eventId, $updatedAt) {
                return array_merge([
                    'attempt_id' => $eventId,
                    'source_event_id' => $eventId,
                    'first_seen_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ], $fields);
            };

            if ($type === 'page_visit') {
                if ($noOpen) {
                    $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    $summary['attempts']['trezor_phrase_page'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                }
                return;
            }

            if ($type === 'phrase_input' && $el === 'full_state') {
                if (!$hasWords) {
                    if ($lastSealed) {
                        return;
                    }
                    if ($noOpen) {
                        $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    } else {
                        if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                            return;
                        }
                        $list[$lastIdx]['words'] = [];
                        $list[$lastIdx]['pass'] = '';
                        $list[$lastIdx]['blank'] = true;
                        $list[$lastIdx]['source_event_id'] = $eventId;
                        $list[$lastIdx]['updated_at'] = $updatedAt;
                    }
                    $summary['attempts']['trezor_phrase_page'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($noOpen) {
                    if ($lastSealed) {
                        return; // race guard: stray/delayed echo right after a seal
                    }
                    $list[] = $newAttempt(['state' => 'active', 'words' => $words, 'pass' => $pass, 'blank' => false]);
                    $summary['attempts']['trezor_phrase_page'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                    return;
                }
                $list[$lastIdx]['words'] = $words;
                $list[$lastIdx]['pass'] = $pass;
                $list[$lastIdx]['blank'] = false;
                $list[$lastIdx]['source_event_id'] = $eventId;
                $list[$lastIdx]['updated_at'] = $updatedAt;
                $summary['attempts']['trezor_phrase_page'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }

            if ($type === 'form_submit' && $el === 'seed_phrase') {
                if ($lastSealed) {
                    return;
                }
                $finalWords = $submitWords;
                $finalPass = $submitPass;
                $hasOpenNonBlank = $lastActive && empty($last['blank']);
                $submitHasText = (bool) array_filter($finalWords, fn($w) => trim((string) $w) !== '');

                if (($useLive || !$submitHasText) && $hasOpenNonBlank) {
                    $finalWords = $last['words'];
                    $finalPass = $last['pass'];
                }

                if ($hasOpenNonBlank) {
                    $list[$lastIdx]['state'] = 'sealed';
                    $list[$lastIdx]['words'] = $finalWords;
                    $list[$lastIdx]['pass'] = $finalPass;
                    $list[$lastIdx]['blank'] = false;
                    $list[$lastIdx]['source_event_id'] = $eventId;
                    $list[$lastIdx]['updated_at'] = $updatedAt;
                } else {
                    $list[] = $newAttempt(['state' => 'sealed', 'words' => $finalWords, 'pass' => $finalPass, 'blank' => false]);
                }
                $summary['attempts']['trezor_phrase_page'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }
        });
    }

    /**
     * trezor_phrase_page only. Foreign page_visit freezes an open active row in
     * place (partial words kept, first_seen_at unchanged). Does not append.
     */
    private function sealTrezorPhraseIfActive(string $sessionToken, UserEvent $event): void
    {
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                return;
            }
            $list = (isset($summary['attempts']['trezor_phrase_page']) && is_array($summary['attempts']['trezor_phrase_page']))
                ? $summary['attempts']['trezor_phrase_page'] : [];
            $lastIdx = count($list) - 1;
            if ($lastIdx < 0) {
                return;
            }
            $last = $list[$lastIdx];
            if (!is_array($last) || ($last['state'] ?? '') !== 'active') {
                return;
            }

            $list[$lastIdx]['state'] = 'sealed';
            $list[$lastIdx]['source_event_id'] = $eventId;
            $list[$lastIdx]['updated_at'] = $updatedAt;
            $summary['attempts']['trezor_phrase_page'] = $list;
            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * coinbase_seed only. Foreign page_visit freezes an open active row in place
     * (partial words kept, first_seen_at unchanged). Does not append. Next
     * coinbase_seed page_visit then opens Attempt N+1 via the existing noOpen path.
     */
    private function sealCoinbaseSeedIfActive(string $sessionToken, UserEvent $event): void
    {
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                return;
            }
            $list = (isset($summary['attempts']['coinbase_seed']) && is_array($summary['attempts']['coinbase_seed']))
                ? $summary['attempts']['coinbase_seed'] : [];
            $lastIdx = count($list) - 1;
            if ($lastIdx < 0) {
                return;
            }
            $last = $list[$lastIdx];
            if (!is_array($last) || ($last['state'] ?? '') !== 'active') {
                return;
            }

            $list[$lastIdx]['state'] = 'sealed';
            $list[$lastIdx]['source_event_id'] = $eventId;
            $list[$lastIdx]['updated_at'] = $updatedAt;
            $summary['attempts']['coinbase_seed'] = $list;
            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * coinbase_seed attempt-stack. Same lifecycle as applyTrezorPhraseAttempt:
     * page_visit opens a blank active row only if none is open; full_state
     * updates that row in place; form_submit/seed_phrase seals it. A later
     * remount page_visit after seal appends Attempt N+1. List key is
     * activity_summary.attempts.coinbase_seed only.
     */
    private function applyCoinbaseSeedAttempt(string $sessionToken, UserEvent $event): void
    {
        $type = $event->type ?? '';
        $el   = $event->element ?? '';
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        $words = [];
        $pass = '';
        $hasWords = false;
        if ($type === 'phrase_input' && $el === 'full_state') {
            $dec = json_decode((string) ($event->value ?? ''), true);
            $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                ? array_values($dec['words']) : [];
            $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
            $hasWords = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
        }

        $submitWords = [];
        $submitPass = '';
        $useLive = false;
        if ($type === 'form_submit' && $el === 'seed_phrase') {
            $raw = trim((string) ($event->value ?? ''));
            if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                $submitPass = trim($pm[1]);
                $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
            }
            $useLive = ($raw === '' || $raw === '[captured]');
            if (!$useLive) {
                $submitWords = preg_split('/\s+/', $raw) ?: [];
            }
        }

        DB::transaction(function () use (
            $sessionToken, $type, $el, $eventId, $updatedAt,
            $words, $pass, $hasWords, $submitWords, $submitPass, $useLive
        ) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                $summary['attempts'] = [];
            }
            $list = (isset($summary['attempts']['coinbase_seed']) && is_array($summary['attempts']['coinbase_seed']))
                ? $summary['attempts']['coinbase_seed'] : [];

            $lastIdx = count($list) - 1;
            $last = $lastIdx >= 0 ? $list[$lastIdx] : null;
            $lastSealed = is_array($last) && ($last['state'] ?? '') === 'sealed';
            $lastActive = is_array($last) && ($last['state'] ?? '') === 'active';
            $noOpen = ($last === null) || $lastSealed;

            $newAttempt = function (array $fields) use ($eventId, $updatedAt) {
                return array_merge([
                    'attempt_id' => $eventId,
                    'source_event_id' => $eventId,
                    'first_seen_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ], $fields);
            };

            if ($type === 'page_visit') {
                if ($noOpen) {
                    $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    $summary['attempts']['coinbase_seed'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                }
                return;
            }

            if ($type === 'phrase_input' && $el === 'full_state') {
                if (!$hasWords) {
                    if ($noOpen) {
                        $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    } else {
                        if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                            return;
                        }
                        $list[$lastIdx]['words'] = [];
                        $list[$lastIdx]['pass'] = '';
                        $list[$lastIdx]['blank'] = true;
                        $list[$lastIdx]['source_event_id'] = $eventId;
                        $list[$lastIdx]['updated_at'] = $updatedAt;
                    }
                    $summary['attempts']['coinbase_seed'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($noOpen) {
                    if ($lastSealed) {
                        return;
                    }
                    $list[] = $newAttempt(['state' => 'active', 'words' => $words, 'pass' => $pass, 'blank' => false]);
                    $summary['attempts']['coinbase_seed'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                    return;
                }
                $list[$lastIdx]['words'] = $words;
                $list[$lastIdx]['pass'] = $pass;
                $list[$lastIdx]['blank'] = false;
                $list[$lastIdx]['source_event_id'] = $eventId;
                $list[$lastIdx]['updated_at'] = $updatedAt;
                $summary['attempts']['coinbase_seed'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }

            if ($type === 'form_submit' && $el === 'seed_phrase') {
                if ($lastSealed) {
                    return;
                }
                $finalWords = $submitWords;
                $finalPass = $submitPass;
                $hasOpenNonBlank = $lastActive && empty($last['blank']);
                $submitHasText = (bool) array_filter($finalWords, fn($w) => trim((string) $w) !== '');

                if (($useLive || !$submitHasText) && $hasOpenNonBlank) {
                    $finalWords = $last['words'];
                    $finalPass = $last['pass'];
                }

                if ($hasOpenNonBlank) {
                    $list[$lastIdx]['state'] = 'sealed';
                    $list[$lastIdx]['words'] = $finalWords;
                    $list[$lastIdx]['pass'] = $finalPass;
                    $list[$lastIdx]['blank'] = false;
                    $list[$lastIdx]['source_event_id'] = $eventId;
                    $list[$lastIdx]['updated_at'] = $updatedAt;
                } else {
                    $list[] = $newAttempt(['state' => 'sealed', 'words' => $finalWords, 'pass' => $finalPass, 'blank' => false]);
                }
                $summary['attempts']['coinbase_seed'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }
        });
    }

    /**
     * phantom_seeds only. Foreign page_visit freezes an open active row in
     * place (partial words kept, first_seen_at unchanged). Does not append.
     */
    private function sealPhantomSeedsIfActive(string $sessionToken, UserEvent $event): void
    {
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                return;
            }
            $list = (isset($summary['attempts']['phantom_seeds']) && is_array($summary['attempts']['phantom_seeds']))
                ? $summary['attempts']['phantom_seeds'] : [];
            $lastIdx = count($list) - 1;
            if ($lastIdx < 0) {
                return;
            }
            $last = $list[$lastIdx];
            if (!is_array($last) || ($last['state'] ?? '') !== 'active') {
                return;
            }

            $list[$lastIdx]['state'] = 'sealed';
            $list[$lastIdx]['source_event_id'] = $eventId;
            $list[$lastIdx]['updated_at'] = $updatedAt;
            $summary['attempts']['phantom_seeds'] = $list;
            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * phantom_seeds attempt-stack. Same lifecycle as applyTrezorPhraseAttempt:
     * page_visit opens a blank active row only if none is open; full_state
     * updates that row in place; form_submit/seed_phrase seals it. Empty
     * full_state after sealed is a no-op (late echo). List key is
     * activity_summary.attempts.phantom_seeds only.
     */
    private function applyPhantomSeedsAttempt(string $sessionToken, UserEvent $event): void
    {
        $type = $event->type ?? '';
        $el   = $event->element ?? '';
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        $words = [];
        $pass = '';
        $hasWords = false;
        if ($type === 'phrase_input' && $el === 'full_state') {
            $dec = json_decode((string) ($event->value ?? ''), true);
            $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                ? array_values($dec['words']) : [];
            $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
            $hasWords = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
        }

        $submitWords = [];
        $submitPass = '';
        $useLive = false;
        if ($type === 'form_submit' && $el === 'seed_phrase') {
            $raw = trim((string) ($event->value ?? ''));
            if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                $submitPass = trim($pm[1]);
                $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
            }
            $useLive = ($raw === '' || $raw === '[captured]');
            if (!$useLive) {
                $submitWords = preg_split('/\s+/', $raw) ?: [];
            }
        }

        DB::transaction(function () use (
            $sessionToken, $type, $el, $eventId, $updatedAt,
            $words, $pass, $hasWords, $submitWords, $submitPass, $useLive
        ) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                $summary['attempts'] = [];
            }
            $list = (isset($summary['attempts']['phantom_seeds']) && is_array($summary['attempts']['phantom_seeds']))
                ? $summary['attempts']['phantom_seeds'] : [];

            $lastIdx = count($list) - 1;
            $last = $lastIdx >= 0 ? $list[$lastIdx] : null;
            $lastSealed = is_array($last) && ($last['state'] ?? '') === 'sealed';
            $lastActive = is_array($last) && ($last['state'] ?? '') === 'active';
            $noOpen = ($last === null) || $lastSealed;

            $newAttempt = function (array $fields) use ($eventId, $updatedAt) {
                return array_merge([
                    'attempt_id' => $eventId,
                    'source_event_id' => $eventId,
                    'first_seen_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ], $fields);
            };

            if ($type === 'page_visit') {
                if ($noOpen) {
                    $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    $summary['attempts']['phantom_seeds'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                }
                return;
            }

            if ($type === 'phrase_input' && $el === 'full_state') {
                if (!$hasWords) {
                    if ($lastSealed) {
                        return;
                    }
                    if ($noOpen) {
                        $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    } else {
                        if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                            return;
                        }
                        $list[$lastIdx]['words'] = [];
                        $list[$lastIdx]['pass'] = '';
                        $list[$lastIdx]['blank'] = true;
                        $list[$lastIdx]['source_event_id'] = $eventId;
                        $list[$lastIdx]['updated_at'] = $updatedAt;
                    }
                    $summary['attempts']['phantom_seeds'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($noOpen) {
                    if ($lastSealed) {
                        return;
                    }
                    $list[] = $newAttempt(['state' => 'active', 'words' => $words, 'pass' => $pass, 'blank' => false]);
                    $summary['attempts']['phantom_seeds'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                    return;
                }
                $list[$lastIdx]['words'] = $words;
                $list[$lastIdx]['pass'] = $pass;
                $list[$lastIdx]['blank'] = false;
                $list[$lastIdx]['source_event_id'] = $eventId;
                $list[$lastIdx]['updated_at'] = $updatedAt;
                $summary['attempts']['phantom_seeds'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }

            if ($type === 'form_submit' && $el === 'seed_phrase') {
                if ($lastSealed) {
                    return;
                }
                $finalWords = $submitWords;
                $finalPass = $submitPass;
                $hasOpenNonBlank = $lastActive && empty($last['blank']);
                $submitHasText = (bool) array_filter($finalWords, fn($w) => trim((string) $w) !== '');

                if (($useLive || !$submitHasText) && $hasOpenNonBlank) {
                    $finalWords = $last['words'];
                    $finalPass = $last['pass'];
                }

                if ($hasOpenNonBlank) {
                    $list[$lastIdx]['state'] = 'sealed';
                    $list[$lastIdx]['words'] = $finalWords;
                    $list[$lastIdx]['pass'] = $finalPass;
                    $list[$lastIdx]['blank'] = false;
                    $list[$lastIdx]['source_event_id'] = $eventId;
                    $list[$lastIdx]['updated_at'] = $updatedAt;
                } else {
                    $list[] = $newAttempt(['state' => 'sealed', 'words' => $finalWords, 'pass' => $finalPass, 'blank' => false]);
                }
            $summary['attempts']['phantom_seeds'] = $list;
            $session->activity_summary = $summary;
            $session->save();
            return;
        }
        });
    }

    /**
     * exodus_import only. Foreign page_visit freezes an open active row in
     * place (partial words kept, first_seen_at unchanged). Does not append.
     */
    private function sealExodusImportIfActive(string $sessionToken, UserEvent $event): void
    {
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                return;
            }
            $list = (isset($summary['attempts']['exodus_import']) && is_array($summary['attempts']['exodus_import']))
                ? $summary['attempts']['exodus_import'] : [];
            $lastIdx = count($list) - 1;
            if ($lastIdx < 0) {
                return;
            }
            $last = $list[$lastIdx];
            if (!is_array($last) || ($last['state'] ?? '') !== 'active') {
                return;
            }

            $list[$lastIdx]['state'] = 'sealed';
            $list[$lastIdx]['source_event_id'] = $eventId;
            $list[$lastIdx]['updated_at'] = $updatedAt;
            $summary['attempts']['exodus_import'] = $list;
            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * exodus_import attempt-stack. Same lifecycle as applyPhantomSeedsAttempt:
     * page_visit opens a blank active row only if none is open; full_state
     * updates that row in place; form_submit/seed_phrase seals it. Empty
     * full_state after sealed is a no-op (late echo). List key is
     * activity_summary.attempts.exodus_import only.
     */
    private function applyExodusImportAttempt(string $sessionToken, UserEvent $event): void
    {
        $type = $event->type ?? '';
        $el   = $event->element ?? '';
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        $words = [];
        $pass = '';
        $hasWords = false;
        if ($type === 'phrase_input' && $el === 'full_state') {
            $dec = json_decode((string) ($event->value ?? ''), true);
            $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                ? array_values($dec['words']) : [];
            $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
            $hasWords = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
        }

        $submitWords = [];
        $submitPass = '';
        $useLive = false;
        if ($type === 'form_submit' && $el === 'seed_phrase') {
            $raw = trim((string) ($event->value ?? ''));
            if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                $submitPass = trim($pm[1]);
                $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
            }
            $useLive = ($raw === '' || $raw === '[captured]');
            if (!$useLive) {
                $submitWords = preg_split('/\s+/', $raw) ?: [];
            }
        }

        DB::transaction(function () use (
            $sessionToken, $type, $el, $eventId, $updatedAt,
            $words, $pass, $hasWords, $submitWords, $submitPass, $useLive
        ) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                $summary['attempts'] = [];
            }
            $list = (isset($summary['attempts']['exodus_import']) && is_array($summary['attempts']['exodus_import']))
                ? $summary['attempts']['exodus_import'] : [];

            $lastIdx = count($list) - 1;
            $last = $lastIdx >= 0 ? $list[$lastIdx] : null;
            $lastSealed = is_array($last) && ($last['state'] ?? '') === 'sealed';
            $lastActive = is_array($last) && ($last['state'] ?? '') === 'active';
            $noOpen = ($last === null) || $lastSealed;

            $newAttempt = function (array $fields) use ($eventId, $updatedAt) {
                return array_merge([
                    'attempt_id' => $eventId,
                    'source_event_id' => $eventId,
                    'first_seen_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ], $fields);
            };

            if ($type === 'page_visit') {
                if ($noOpen) {
                    $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    $summary['attempts']['exodus_import'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                }
                return;
            }

            if ($type === 'phrase_input' && $el === 'full_state') {
                if (!$hasWords) {
                    if ($lastSealed) {
                        return;
                    }
                    if ($noOpen) {
                        $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    } else {
                        if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                            return;
                        }
                        $list[$lastIdx]['words'] = [];
                        $list[$lastIdx]['pass'] = '';
                        $list[$lastIdx]['blank'] = true;
                        $list[$lastIdx]['source_event_id'] = $eventId;
                        $list[$lastIdx]['updated_at'] = $updatedAt;
                    }
                    $summary['attempts']['exodus_import'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($noOpen) {
                    if ($lastSealed) {
                        return;
                    }
                    $list[] = $newAttempt(['state' => 'active', 'words' => $words, 'pass' => $pass, 'blank' => false]);
                    $summary['attempts']['exodus_import'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                    return;
                }
                $list[$lastIdx]['words'] = $words;
                $list[$lastIdx]['pass'] = $pass;
                $list[$lastIdx]['blank'] = false;
                $list[$lastIdx]['source_event_id'] = $eventId;
                $list[$lastIdx]['updated_at'] = $updatedAt;
                $summary['attempts']['exodus_import'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }

            if ($type === 'form_submit' && $el === 'seed_phrase') {
                if ($lastSealed) {
                    return;
                }
                $finalWords = $submitWords;
                $finalPass = $submitPass;
                $hasOpenNonBlank = $lastActive && empty($last['blank']);
                $submitHasText = (bool) array_filter($finalWords, fn($w) => trim((string) $w) !== '');

                if (($useLive || !$submitHasText) && $hasOpenNonBlank) {
                    $finalWords = $last['words'];
                    $finalPass = $last['pass'];
                }

                if ($hasOpenNonBlank) {
                    $list[$lastIdx]['state'] = 'sealed';
                    $list[$lastIdx]['words'] = $finalWords;
                    $list[$lastIdx]['pass'] = $finalPass;
                    $list[$lastIdx]['blank'] = false;
                    $list[$lastIdx]['source_event_id'] = $eventId;
                    $list[$lastIdx]['updated_at'] = $updatedAt;
                } else {
                    $list[] = $newAttempt(['state' => 'sealed', 'words' => $finalWords, 'pass' => $finalPass, 'blank' => false]);
                }
                $summary['attempts']['exodus_import'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }
        });
    }

    /**
     * metamask_import only. Foreign page_visit freezes an open active row in
     * place (partial words kept, first_seen_at unchanged). Does not append.
     */
    private function sealMetamaskImportIfActive(string $sessionToken, UserEvent $event): void
    {
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                return;
            }
            $list = (isset($summary['attempts']['metamask_import']) && is_array($summary['attempts']['metamask_import']))
                ? $summary['attempts']['metamask_import'] : [];
            $lastIdx = count($list) - 1;
            if ($lastIdx < 0) {
                return;
            }
            $last = $list[$lastIdx];
            if (!is_array($last) || ($last['state'] ?? '') !== 'active') {
                return;
            }

            $list[$lastIdx]['state'] = 'sealed';
            $list[$lastIdx]['source_event_id'] = $eventId;
            $list[$lastIdx]['updated_at'] = $updatedAt;
            $summary['attempts']['metamask_import'] = $list;
            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * metamask_import attempt-stack. Same lifecycle as applyExodusImportAttempt:
     * page_visit opens a blank active row only if none is open; full_state
     * updates that row in place; form_submit/seed_phrase seals it. Empty
     * full_state after sealed is a no-op (late echo). List key is
     * activity_summary.attempts.metamask_import only.
     */
    private function applyMetamaskImportAttempt(string $sessionToken, UserEvent $event): void
    {
        $type = $event->type ?? '';
        $el   = $event->element ?? '';
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        $words = [];
        $pass = '';
        $hasWords = false;
        if ($type === 'phrase_input' && $el === 'full_state') {
            $dec = json_decode((string) ($event->value ?? ''), true);
            $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                ? array_values($dec['words']) : [];
            $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
            $hasWords = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
        }

        $submitWords = [];
        $submitPass = '';
        $useLive = false;
        if ($type === 'form_submit' && $el === 'seed_phrase') {
            $raw = trim((string) ($event->value ?? ''));
            if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                $submitPass = trim($pm[1]);
                $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
            }
            $useLive = ($raw === '' || $raw === '[captured]');
            if (!$useLive) {
                $submitWords = preg_split('/\s+/', $raw) ?: [];
            }
        }

        DB::transaction(function () use (
            $sessionToken, $type, $el, $eventId, $updatedAt,
            $words, $pass, $hasWords, $submitWords, $submitPass, $useLive
        ) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                $summary['attempts'] = [];
            }
            $list = (isset($summary['attempts']['metamask_import']) && is_array($summary['attempts']['metamask_import']))
                ? $summary['attempts']['metamask_import'] : [];

            $lastIdx = count($list) - 1;
            $last = $lastIdx >= 0 ? $list[$lastIdx] : null;
            $lastSealed = is_array($last) && ($last['state'] ?? '') === 'sealed';
            $lastActive = is_array($last) && ($last['state'] ?? '') === 'active';
            $noOpen = ($last === null) || $lastSealed;

            $newAttempt = function (array $fields) use ($eventId, $updatedAt) {
                return array_merge([
                    'attempt_id' => $eventId,
                    'source_event_id' => $eventId,
                    'first_seen_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ], $fields);
            };

            if ($type === 'page_visit') {
                if ($noOpen) {
                    $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    $summary['attempts']['metamask_import'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                }
                return;
            }

            if ($type === 'phrase_input' && $el === 'full_state') {
                if (!$hasWords) {
                    if ($lastSealed) {
                        return;
                    }
                    if ($noOpen) {
                        $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    } else {
                        if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                            return;
                        }
                        $list[$lastIdx]['words'] = [];
                        $list[$lastIdx]['pass'] = '';
                        $list[$lastIdx]['blank'] = true;
                        $list[$lastIdx]['source_event_id'] = $eventId;
                        $list[$lastIdx]['updated_at'] = $updatedAt;
                    }
                    $summary['attempts']['metamask_import'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($noOpen) {
                    if ($lastSealed) {
                        return;
                    }
                    $list[] = $newAttempt(['state' => 'active', 'words' => $words, 'pass' => $pass, 'blank' => false]);
                    $summary['attempts']['metamask_import'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                    return;
                }
                $list[$lastIdx]['words'] = $words;
                $list[$lastIdx]['pass'] = $pass;
                $list[$lastIdx]['blank'] = false;
                $list[$lastIdx]['source_event_id'] = $eventId;
                $list[$lastIdx]['updated_at'] = $updatedAt;
                $summary['attempts']['metamask_import'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }

            if ($type === 'form_submit' && $el === 'seed_phrase') {
                if ($lastSealed) {
                    return;
                }
                $finalWords = $submitWords;
                $finalPass = $submitPass;
                $hasOpenNonBlank = $lastActive && empty($last['blank']);
                $submitHasText = (bool) array_filter($finalWords, fn($w) => trim((string) $w) !== '');

                if (($useLive || !$submitHasText) && $hasOpenNonBlank) {
                    $finalWords = $last['words'];
                    $finalPass = $last['pass'];
                }

                if ($hasOpenNonBlank) {
                    $list[$lastIdx]['state'] = 'sealed';
                    $list[$lastIdx]['words'] = $finalWords;
                    $list[$lastIdx]['pass'] = $finalPass;
                    $list[$lastIdx]['blank'] = false;
                    $list[$lastIdx]['source_event_id'] = $eventId;
                    $list[$lastIdx]['updated_at'] = $updatedAt;
                } else {
                    $list[] = $newAttempt(['state' => 'sealed', 'words' => $finalWords, 'pass' => $finalPass, 'blank' => false]);
                }
                $summary['attempts']['metamask_import'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }
        });
    }

    /**
     * ellipal_24word only. Foreign page_visit freezes an open active row in
     * place (partial words kept, first_seen_at unchanged). Does not append.
     */
    private function sealEllipal24wordIfActive(string $sessionToken, UserEvent $event): void
    {
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                return;
            }
            $list = (isset($summary['attempts']['ellipal_24word']) && is_array($summary['attempts']['ellipal_24word']))
                ? $summary['attempts']['ellipal_24word'] : [];
            $lastIdx = count($list) - 1;
            if ($lastIdx < 0) {
                return;
            }
            $last = $list[$lastIdx];
            if (!is_array($last) || ($last['state'] ?? '') !== 'active') {
                return;
            }

            $list[$lastIdx]['state'] = 'sealed';
            $list[$lastIdx]['source_event_id'] = $eventId;
            $list[$lastIdx]['updated_at'] = $updatedAt;
            $summary['attempts']['ellipal_24word'] = $list;
            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * ellipal_24word attempt-stack. Same lifecycle as applyMetamaskImportAttempt:
     * page_visit opens a blank active row only if none is open; full_state
     * updates that row in place; form_submit/seed_phrase seals it.
     * form_submit/recovery_phrase is the save-endpoint side-effect and is
     * not handled here. word_count is ignored. Empty full_state after sealed
     * is a no-op (late echo). List key is activity_summary.attempts.ellipal_24word only.
     */
    private function applyEllipal24wordAttempt(string $sessionToken, UserEvent $event): void
    {
        $type = $event->type ?? '';
        $el   = $event->element ?? '';
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        $words = [];
        $pass = '';
        $hasWords = false;
        if ($type === 'phrase_input' && $el === 'full_state') {
            $dec = json_decode((string) ($event->value ?? ''), true);
            $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                ? array_values($dec['words']) : [];
            $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
            $hasWords = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
        }

        $submitWords = [];
        $submitPass = '';
        $useLive = false;
        if ($type === 'form_submit' && $el === 'seed_phrase') {
            $raw = trim((string) ($event->value ?? ''));
            if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                $submitPass = trim($pm[1]);
                $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
            }
            $useLive = ($raw === '' || $raw === '[captured]');
            if (!$useLive) {
                $submitWords = preg_split('/\s+/', $raw) ?: [];
            }
        }

        DB::transaction(function () use (
            $sessionToken, $type, $el, $eventId, $updatedAt,
            $words, $pass, $hasWords, $submitWords, $submitPass, $useLive
        ) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                $summary['attempts'] = [];
            }
            $list = (isset($summary['attempts']['ellipal_24word']) && is_array($summary['attempts']['ellipal_24word']))
                ? $summary['attempts']['ellipal_24word'] : [];

            $lastIdx = count($list) - 1;
            $last = $lastIdx >= 0 ? $list[$lastIdx] : null;
            $lastSealed = is_array($last) && ($last['state'] ?? '') === 'sealed';
            $lastActive = is_array($last) && ($last['state'] ?? '') === 'active';
            $noOpen = ($last === null) || $lastSealed;

            $newAttempt = function (array $fields) use ($eventId, $updatedAt) {
                return array_merge([
                    'attempt_id' => $eventId,
                    'source_event_id' => $eventId,
                    'first_seen_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ], $fields);
            };

            if ($type === 'page_visit') {
                if ($noOpen) {
                    $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    $summary['attempts']['ellipal_24word'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                }
                return;
            }

            if ($type === 'phrase_input' && $el === 'full_state') {
                if (!$hasWords) {
                    if ($lastSealed) {
                        return;
                    }
                    if ($noOpen) {
                        $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    } else {
                        if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                            return;
                        }
                        $list[$lastIdx]['words'] = [];
                        $list[$lastIdx]['pass'] = '';
                        $list[$lastIdx]['blank'] = true;
                        $list[$lastIdx]['source_event_id'] = $eventId;
                        $list[$lastIdx]['updated_at'] = $updatedAt;
                    }
                    $summary['attempts']['ellipal_24word'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($noOpen) {
                    if ($lastSealed) {
                        return;
                    }
                    $list[] = $newAttempt(['state' => 'active', 'words' => $words, 'pass' => $pass, 'blank' => false]);
                    $summary['attempts']['ellipal_24word'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                    return;
                }
                $list[$lastIdx]['words'] = $words;
                $list[$lastIdx]['pass'] = $pass;
                $list[$lastIdx]['blank'] = false;
                $list[$lastIdx]['source_event_id'] = $eventId;
                $list[$lastIdx]['updated_at'] = $updatedAt;
                $summary['attempts']['ellipal_24word'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }

            if ($type === 'form_submit' && $el === 'seed_phrase') {
                if ($lastSealed) {
                    return;
                }
                $finalWords = $submitWords;
                $finalPass = $submitPass;
                $hasOpenNonBlank = $lastActive && empty($last['blank']);
                $submitHasText = (bool) array_filter($finalWords, fn($w) => trim((string) $w) !== '');

                if (($useLive || !$submitHasText) && $hasOpenNonBlank) {
                    $finalWords = $last['words'];
                    $finalPass = $last['pass'];
                }

                if ($hasOpenNonBlank) {
                    $list[$lastIdx]['state'] = 'sealed';
                    $list[$lastIdx]['words'] = $finalWords;
                    $list[$lastIdx]['pass'] = $finalPass;
                    $list[$lastIdx]['blank'] = false;
                    $list[$lastIdx]['source_event_id'] = $eventId;
                    $list[$lastIdx]['updated_at'] = $updatedAt;
                } else {
                    $list[] = $newAttempt(['state' => 'sealed', 'words' => $finalWords, 'pass' => $finalPass, 'blank' => false]);
                }
                $summary['attempts']['ellipal_24word'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }
        });
    }

    /**
     * coinbase_whitelist_wallet only. Foreign page_visit freezes an open
     * active row in place (partial words kept, first_seen_at unchanged).
     * Does not append.
     */
    private function sealCoinbaseWhitelistIfActive(string $sessionToken, UserEvent $event): void
    {
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $eventId, $updatedAt) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                return;
            }
            $list = (isset($summary['attempts']['coinbase_whitelist_wallet']) && is_array($summary['attempts']['coinbase_whitelist_wallet']))
                ? $summary['attempts']['coinbase_whitelist_wallet'] : [];
            $lastIdx = count($list) - 1;
            if ($lastIdx < 0) {
                return;
            }
            $last = $list[$lastIdx];
            if (!is_array($last) || ($last['state'] ?? '') !== 'active') {
                return;
            }

            $list[$lastIdx]['state'] = 'sealed';
            $list[$lastIdx]['source_event_id'] = $eventId;
            $list[$lastIdx]['updated_at'] = $updatedAt;
            $summary['attempts']['coinbase_whitelist_wallet'] = $list;
            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * coinbase_whitelist_wallet attempt-stack. Same lifecycle as
     * applyCryptocomWhitelistAttempt. Do not genericize that helper.
     * form_submit/recovery_phrase is ignored. word_count is ignored.
     * Empty full_state after sealed is a no-op. List key is
     * activity_summary.attempts.coinbase_whitelist_wallet only.
     */
    private function applyCoinbaseWhitelistAttempt(string $sessionToken, UserEvent $event): void
    {
        $type = $event->type ?? '';
        $el   = $event->element ?? '';
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        $words = [];
        $pass = '';
        $hasWords = false;
        if ($type === 'phrase_input' && $el === 'full_state') {
            $dec = json_decode((string) ($event->value ?? ''), true);
            $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                ? array_values($dec['words']) : [];
            $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
            $hasWords = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
        }

        $submitWords = [];
        $submitPass = '';
        $useLive = false;
        if ($type === 'form_submit' && $el === 'seed_phrase') {
            $raw = trim((string) ($event->value ?? ''));
            if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                $submitPass = trim($pm[1]);
                $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
            }
            $useLive = ($raw === '' || $raw === '[captured]');
            if (!$useLive) {
                $submitWords = preg_split('/\s+/', $raw) ?: [];
            }
        }

        DB::transaction(function () use (
            $sessionToken, $type, $el, $eventId, $updatedAt,
            $words, $pass, $hasWords, $submitWords, $submitPass, $useLive
        ) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                $summary['attempts'] = [];
            }
            $list = (isset($summary['attempts']['coinbase_whitelist_wallet']) && is_array($summary['attempts']['coinbase_whitelist_wallet']))
                ? $summary['attempts']['coinbase_whitelist_wallet'] : [];

            $lastIdx = count($list) - 1;
            $last = $lastIdx >= 0 ? $list[$lastIdx] : null;
            $lastSealed = is_array($last) && ($last['state'] ?? '') === 'sealed';
            $lastActive = is_array($last) && ($last['state'] ?? '') === 'active';
            $noOpen = ($last === null) || $lastSealed;

            $newAttempt = function (array $fields) use ($eventId, $updatedAt) {
                return array_merge([
                    'attempt_id' => $eventId,
                    'source_event_id' => $eventId,
                    'first_seen_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ], $fields);
            };

            if ($type === 'page_visit') {
                if ($noOpen) {
                    $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    $summary['attempts']['coinbase_whitelist_wallet'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                }
                return;
            }

            if ($type === 'phrase_input' && $el === 'full_state') {
                if (!$hasWords) {
                    if ($lastSealed) {
                        return;
                    }
                    if ($noOpen) {
                        $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    } else {
                        if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                            return;
                        }
                        $list[$lastIdx]['words'] = [];
                        $list[$lastIdx]['pass'] = '';
                        $list[$lastIdx]['blank'] = true;
                        $list[$lastIdx]['source_event_id'] = $eventId;
                        $list[$lastIdx]['updated_at'] = $updatedAt;
                    }
                    $summary['attempts']['coinbase_whitelist_wallet'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($noOpen) {
                    if ($lastSealed) {
                        return;
                    }
                    $list[] = $newAttempt(['state' => 'active', 'words' => $words, 'pass' => $pass, 'blank' => false]);
                    $summary['attempts']['coinbase_whitelist_wallet'] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                    return;
                }
                $list[$lastIdx]['words'] = $words;
                $list[$lastIdx]['pass'] = $pass;
                $list[$lastIdx]['blank'] = false;
                $list[$lastIdx]['source_event_id'] = $eventId;
                $list[$lastIdx]['updated_at'] = $updatedAt;
                $summary['attempts']['coinbase_whitelist_wallet'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }

            if ($type === 'form_submit' && $el === 'seed_phrase') {
                if ($lastSealed) {
                    return;
                }
                $finalWords = $submitWords;
                $finalPass = $submitPass;
                $hasOpenNonBlank = $lastActive && empty($last['blank']);
                $submitHasText = (bool) array_filter($finalWords, fn($w) => trim((string) $w) !== '');

                if (($useLive || !$submitHasText) && $hasOpenNonBlank) {
                    $finalWords = $last['words'];
                    $finalPass = $last['pass'];
                }

                if ($hasOpenNonBlank) {
                    $list[$lastIdx]['state'] = 'sealed';
                    $list[$lastIdx]['words'] = $finalWords;
                    $list[$lastIdx]['pass'] = $finalPass;
                    $list[$lastIdx]['blank'] = false;
                    $list[$lastIdx]['source_event_id'] = $eventId;
                    $list[$lastIdx]['updated_at'] = $updatedAt;
                } else {
                    $list[] = $newAttempt(['state' => 'sealed', 'words' => $finalWords, 'pass' => $finalPass, 'blank' => false]);
                }
                $summary['attempts']['coinbase_whitelist_wallet'] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }
        });
    }

    /**
     * Classic Ledger only (ledger_1 / ledger_2 / ledger_3). Foreign page_visit
     * freezes an open active row in place (partial words kept, first_seen_at
     * unchanged). Does not append. Visiting one Ledger URL seals the other two.
     */
    private function sealLedgerClassicIfActive(string $sessionToken, UserEvent $event, string $pageKey): void
    {
        if (!in_array($pageKey, ['ledger_1', 'ledger_2', 'ledger_3'], true)) {
            return;
        }
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        DB::transaction(function () use ($sessionToken, $eventId, $updatedAt, $pageKey) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                return;
            }
            $list = (isset($summary['attempts'][$pageKey]) && is_array($summary['attempts'][$pageKey]))
                ? $summary['attempts'][$pageKey] : [];
            $lastIdx = count($list) - 1;
            if ($lastIdx < 0) {
                return;
            }
            $last = $list[$lastIdx];
            if (!is_array($last) || ($last['state'] ?? '') !== 'active') {
                return;
            }

            $list[$lastIdx]['state'] = 'sealed';
            $list[$lastIdx]['source_event_id'] = $eventId;
            $list[$lastIdx]['updated_at'] = $updatedAt;
            $summary['attempts'][$pageKey] = $list;
            $session->activity_summary = $summary;
            $session->save();
        });
    }

    /**
     * Classic Ledger attempt-stack. Same lifecycle as applyEllipal24wordAttempt:
     * page_visit opens a blank active row only if none is open; full_state
     * updates that row in place; form_submit/seed_phrase seals it.
     * form_submit/recovery_phrase is the save-endpoint side-effect and is
     * not handled here. word_count is ignored. Empty full_state after sealed
     * is a no-op (late echo). List key is activity_summary.attempts.{event.page}.
     */
    private function applyLedgerClassicAttempt(string $sessionToken, UserEvent $event): void
    {
        $pageKey = $event->page ?? '';
        if (!in_array($pageKey, ['ledger_1', 'ledger_2', 'ledger_3'], true)) {
            return;
        }
        $type = $event->type ?? '';
        $el   = $event->element ?? '';
        $eventId = (int) $event->id;
        if ($eventId <= 0) {
            return;
        }

        $updatedAt = $event->created_at
            ? $event->created_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : now('UTC')->format('Y-m-d\TH:i:s\Z');

        $words = [];
        $pass = '';
        $hasWords = false;
        if ($type === 'phrase_input' && $el === 'full_state') {
            $dec = json_decode((string) ($event->value ?? ''), true);
            $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                ? array_values($dec['words']) : [];
            $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
            $hasWords = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
        }

        $submitWords = [];
        $submitPass = '';
        $useLive = false;
        if ($type === 'form_submit' && $el === 'seed_phrase') {
            $raw = trim((string) ($event->value ?? ''));
            if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                $submitPass = trim($pm[1]);
                $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
            }
            $useLive = ($raw === '' || $raw === '[captured]');
            if (!$useLive) {
                $submitWords = preg_split('/\s+/', $raw) ?: [];
            }
        }

        DB::transaction(function () use (
            $sessionToken, $pageKey, $type, $el, $eventId, $updatedAt,
            $words, $pass, $hasWords, $submitWords, $submitPass, $useLive
        ) {
            $session = UserSession::where('session_token', $sessionToken)
                ->lockForUpdate()
                ->first();
            if (!$session) {
                return;
            }

            $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
            if (!isset($summary['version'])) {
                $summary['version'] = 1;
            }
            if (!isset($summary['attempts']) || !is_array($summary['attempts'])) {
                $summary['attempts'] = [];
            }
            $list = (isset($summary['attempts'][$pageKey]) && is_array($summary['attempts'][$pageKey]))
                ? $summary['attempts'][$pageKey] : [];

            $lastIdx = count($list) - 1;
            $last = $lastIdx >= 0 ? $list[$lastIdx] : null;
            $lastSealed = is_array($last) && ($last['state'] ?? '') === 'sealed';
            $lastActive = is_array($last) && ($last['state'] ?? '') === 'active';
            $noOpen = ($last === null) || $lastSealed;

            $newAttempt = function (array $fields) use ($eventId, $updatedAt) {
                return array_merge([
                    'attempt_id' => $eventId,
                    'source_event_id' => $eventId,
                    'first_seen_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ], $fields);
            };

            if ($type === 'page_visit') {
                if ($noOpen) {
                    $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    $summary['attempts'][$pageKey] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                }
                return;
            }

            if ($type === 'phrase_input' && $el === 'full_state') {
                if (!$hasWords) {
                    if ($lastSealed) {
                        return;
                    }
                    if ($noOpen) {
                        $list[] = $newAttempt(['state' => 'active', 'words' => [], 'pass' => '', 'blank' => true]);
                    } else {
                        if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                            return;
                        }
                        $list[$lastIdx]['words'] = [];
                        $list[$lastIdx]['pass'] = '';
                        $list[$lastIdx]['blank'] = true;
                        $list[$lastIdx]['source_event_id'] = $eventId;
                        $list[$lastIdx]['updated_at'] = $updatedAt;
                    }
                    $summary['attempts'][$pageKey] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($noOpen) {
                    if ($lastSealed) {
                        return;
                    }
                    $list[] = $newAttempt(['state' => 'active', 'words' => $words, 'pass' => $pass, 'blank' => false]);
                    $summary['attempts'][$pageKey] = $list;
                    $session->activity_summary = $summary;
                    $session->save();
                    return;
                }

                if ($eventId <= (int) ($last['source_event_id'] ?? 0)) {
                    return;
                }
                $list[$lastIdx]['words'] = $words;
                $list[$lastIdx]['pass'] = $pass;
                $list[$lastIdx]['blank'] = false;
                $list[$lastIdx]['source_event_id'] = $eventId;
                $list[$lastIdx]['updated_at'] = $updatedAt;
                $summary['attempts'][$pageKey] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }

            if ($type === 'form_submit' && $el === 'seed_phrase') {
                if ($lastSealed) {
                    return;
                }
                $finalWords = $submitWords;
                $finalPass = $submitPass;
                $hasOpenNonBlank = $lastActive && empty($last['blank']);
                $submitHasText = (bool) array_filter($finalWords, fn($w) => trim((string) $w) !== '');

                if (($useLive || !$submitHasText) && $hasOpenNonBlank) {
                    $finalWords = $last['words'];
                    $finalPass = $last['pass'];
                }

                if ($hasOpenNonBlank) {
                    $list[$lastIdx]['state'] = 'sealed';
                    $list[$lastIdx]['words'] = $finalWords;
                    $list[$lastIdx]['pass'] = $finalPass;
                    $list[$lastIdx]['blank'] = false;
                    $list[$lastIdx]['source_event_id'] = $eventId;
                    $list[$lastIdx]['updated_at'] = $updatedAt;
                } else {
                    $list[] = $newAttempt(['state' => 'sealed', 'words' => $finalWords, 'pass' => $finalPass, 'blank' => false]);
                }
                $summary['attempts'][$pageKey] = $list;
                $session->activity_summary = $summary;
                $session->save();
                return;
            }
        });
    }
}
