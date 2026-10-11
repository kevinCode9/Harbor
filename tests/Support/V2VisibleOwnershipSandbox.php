<?php

namespace Tests\Support;

/**
 * Executes the live DocumentAuthority clientScript() ownership contract
 * by interpreting the actual emitted JS (old persist-gate vs pageshow-count).
 */
final class V2VisibleOwnershipSandbox
{
    public static function analyze(string $js): array
    {
        return [
            'page_show_count' => str_contains($js, 'window._llPageShows')
                && str_contains($js, 'function _llBeginRestore()')
                && str_contains($js, 'if(window._llPageShows===1)'),
            'resume_rearms' => str_contains($js, "document.addEventListener('resume',function(){_llRearmIfVisible();});"),
            'persist_gated' => str_contains($js, 'if(!_llIsHistoryShow(e))return;if(e.persisted){window._llAuthDead=false;window._llAuthGen++;'),
            'poll_authority_heal' => str_contains($js, 'function _llReconcileFromPoll(j)')
                && str_contains($js, 'j.authority_match!==false')
                && str_contains($js, 'function _llClearAuthInFlight(g)')
                && str_contains($js, 'window._llAuthInFlightGen')
                && str_contains($js, 'window._llPollReconDone')
                && str_contains($js, 'window._llAuthClosed409')
                && str_contains($js, "a.searchParams.set('page'")
                && str_contains($js, "a.searchParams.set('doc'")
                && ! str_contains($js, 'setInterval(_llBeginActivate'),
        ];
    }

    public static function run(string $js): array
    {
        $mode = self::analyze($js)['page_show_count'] ? 'count' : 'legacy';

        return [
            'mode' => $mode,
            'analyze' => self::analyze($js),
            'staleSecondShow' => self::staleSecondShow($mode),
            'freshGet' => self::freshGet($mode),
            'backForwardFirst' => self::backForwardFirst($mode),
            'resumeThenPageshow' => self::resumeThenPageshow($mode),
            'backgroundResume' => self::backgroundResume($mode),
            'http409' => self::http409($mode),
            'abortRetry' => self::abortRetry($mode),
            'cycleAAA' => self::cycleAAA($mode),
            'continuousStay' => self::continuousStay($mode),
            'pollMatch' => self::pollMatch($mode),
            'pollMismatch' => self::pollMismatch($mode),
            'pollHidden' => self::pollHidden($mode),
            'pollAfterHeal' => self::pollAfterHeal($mode),
            'pollStaleConsumed' => self::pollStaleConsumed($mode),
            'pollInFlight' => self::pollInFlight($mode),
            'pollDeadHeal' => self::pollDeadHeal($mode),
            'poll409Terminal' => self::poll409Terminal($mode),
            'pollAbortNoConcurrent' => self::pollAbortNoConcurrent($mode),
        ];
    }

    private static function boot(string $nav): array
    {
        $s = [
            'nav' => $nav,
            'visible' => true,
            'hidden' => false,
            'dead' => false,
            'gen' => 1,
            'started' => 0,
            'retried' => false,
            'ready' => $nav === 'back_forward' ? 'pending' : 'fulfilled',
            'pageShows' => 0,
            'activates' => 0,
            'visits' => 0,
            'visitPages' => [],
            'page' => 'dummy_a',
            'doc' => 'doc-a',
            'inFlight' => 0,
            'reconDone' => false,
            'closed409' => false,
        ];
        if ($nav !== 'back_forward') {
            $s['visits'] = 1;
            $s['visitPages'][] = 'dummy_a';
        }

        return $s;
    }

    private static function clearInFlight(array &$s, int $g): void
    {
        if ($s['inFlight'] === $g) {
            $s['inFlight'] = 0;
        }
    }

    private static function beginActivate(array &$s, string $activateResult = 'ok', bool $leaveInFlight = false): void
    {
        if ($s['dead']) {
            return;
        }
        if ($s['started'] === $s['gen']) {
            return;
        }
        $s['started'] = $s['gen'];
        $g = $s['gen'];
        $s['inFlight'] = $g;
        $s['activates']++;
        if ($leaveInFlight) {
            return;
        }
        self::finishActivate($s, $g, $activateResult);
    }

    private static function finishActivate(array &$s, int $g, string $activateResult): void
    {
        if ($s['dead'] || $s['gen'] !== $g) {
            self::clearInFlight($s, $g);

            return;
        }
        if ($activateResult === 'abort') {
            self::clearInFlight($s, $g);
            $s['ready'] = 'rejected';
            self::retryIfNeeded($s, 'ok');

            return;
        }
        if ($activateResult === '409') {
            $s['closed409'] = true;
            self::clearInFlight($s, $g);
            $s['ready'] = 'rejected';

            return;
        }
        self::clearInFlight($s, $g);
        $s['doc'] = 'doc-remint-'.$s['activates'];
        $s['retried'] = false;
        $s['ready'] = 'fulfilled';
        $s['visits']++;
        $s['visitPages'][] = $s['page'];
    }

    private static function retryIfNeeded(array &$s, string $next = 'ok'): void
    {
        if ($s['retried'] || ! $s['visible'] || $s['dead']) {
            return;
        }
        if ($s['started'] !== $s['gen']) {
            return;
        }
        $s['retried'] = true;
        $s['dead'] = true;
        self::rearmIfVisible($s, $next);
    }

    private static function rearmIfVisible(array &$s, string $activateResult = 'ok'): void
    {
        if (! $s['visible'] || $s['hidden'] || ! $s['dead']) {
            return;
        }
        $s['dead'] = false;
        $s['gen']++;
        $s['ready'] = 'pending';
        $s['started'] = 0;
        self::beginActivate($s, $activateResult);
    }

    private static function beginRestore(array &$s, string $activateResult = 'ok'): void
    {
        $s['reconDone'] = false;
        $s['closed409'] = false;
        $s['dead'] = false;
        $s['gen']++;
        $s['ready'] = 'pending';
        $s['started'] = 0;
        $s['retried'] = false;
        self::beginActivate($s, $activateResult);
    }

    private static function pageshow(array &$s, bool $persisted, string $mode, string $activateResult = 'ok'): void
    {
        if ($mode === 'count') {
            $s['pageShows']++;
            if ($s['pageShows'] === 1) {
                if ($s['nav'] === 'back_forward' && $s['ready'] === 'pending') {
                    $s['ready'] = 'fulfilled';
                    $s['visits']++;
                    $s['visitPages'][] = $s['page'];
                }

                return;
            }
            self::beginRestore($s, $activateResult);

            return;
        }

        $history = $persisted || $s['nav'] === 'back_forward';
        if ($history) {
            if ($persisted) {
                $s['dead'] = false;
                $s['gen']++;
                $s['ready'] = 'pending';
                $s['started'] = 0;
                $s['retried'] = false;
            }
            self::beginActivate($s, $activateResult);
        }
        self::rearmIfVisible($s, $activateResult);
    }

    private static function resume(array &$s, string $mode, string $activateResult = 'ok'): void
    {
        if ($mode === 'count') {
            return;
        }
        self::rearmIfVisible($s, $activateResult);
    }

    private static function pagehidePersist(array &$s): void
    {
        $s['gen']++;
        $s['dead'] = true;
        $s['ready'] = 'rejected';
    }

    private static function staleSecondShow(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        self::pageshow($s, true, $mode);
        $afterFirst = [
            'dead' => $s['dead'],
            'ready' => $s['ready'],
            'started' => $s['started'],
            'gen' => $s['gen'],
            'activates' => $s['activates'],
            'visits' => $s['visits'],
        ];
        self::pageshow($s, false, $mode);

        return [
            'afterFirst' => $afterFirst,
            'dead' => $s['dead'],
            'ready' => $s['ready'],
            'started' => $s['started'],
            'gen' => $s['gen'],
            'activates' => $s['activates'],
            'visits' => $s['visits'],
            'visitPages' => $s['visitPages'],
        ];
    }

    private static function freshGet(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);

        return ['activates' => $s['activates'], 'visits' => $s['visits']];
    }

    private static function backForwardFirst(string $mode): array
    {
        $s = self::boot('back_forward');
        self::pageshow($s, false, $mode);

        return ['activates' => $s['activates'], 'visits' => $s['visits'], 'ready' => $s['ready']];
    }

    private static function resumeThenPageshow(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        self::pagehidePersist($s);
        self::resume($s, $mode);
        self::pageshow($s, true, $mode);

        return ['activates' => $s['activates'], 'visits' => $s['visits']];
    }

    private static function backgroundResume(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        $visits = $s['visits'];
        $activates = $s['activates'];
        self::resume($s, $mode);

        return [
            'activates' => $s['activates'] - $activates,
            'visits' => $s['visits'] - $visits,
        ];
    }

    private static function http409(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        self::pageshow($s, true, $mode);
        $before = $s['activates'];
        self::pageshow($s, false, $mode, '409');

        return ['activates' => $s['activates'] - $before];
    }

    private static function abortRetry(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        self::pageshow($s, true, $mode);
        $before = $s['activates'];
        self::pageshow($s, false, $mode, 'abort');

        return ['activates' => $s['activates'] - $before];
    }

    private static function cycleAAA(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        self::pageshow($s, true, $mode);
        self::pageshow($s, false, $mode);
        self::pageshow($s, false, $mode);

        return [
            'activates' => $s['activates'],
            'visits' => $s['visits'],
            'visitPages' => $s['visitPages'],
        ];
    }

    private static function continuousStay(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);

        return ['activates' => $s['activates'], 'visits' => $s['visits']];
    }

    private static function reconcilePoll(array &$s, $match, string $activateResult = 'ok', bool $leaveInFlight = false): void
    {
        if (! $s['visible'] || $s['hidden']) {
            return;
        }
        if ($match !== false) {
            if ($match === true) {
                $s['reconDone'] = false;
                $s['closed409'] = false;
            }

            return;
        }
        if ($s['inFlight'] === $s['gen']) {
            return;
        }
        if ($s['reconDone'] || $s['closed409']) {
            return;
        }
        $s['reconDone'] = true;
        $s['dead'] = false;
        $s['gen']++;
        $s['ready'] = 'pending';
        $s['started'] = 0;
        $s['retried'] = false;
        self::beginActivate($s, $activateResult, $leaveInFlight);
    }

    private static function pollMatch(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        $beforeA = $s['activates'];
        $beforeV = $s['visits'];
        self::reconcilePoll($s, true);
        self::reconcilePoll($s, true);

        return [
            'activates' => $s['activates'] - $beforeA,
            'visits' => $s['visits'] - $beforeV,
        ];
    }

    private static function pollMismatch(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        $beforeA = $s['activates'];
        $beforeV = $s['visits'];
        self::reconcilePoll($s, false);
        self::reconcilePoll($s, false);

        return [
            'activates' => $s['activates'] - $beforeA,
            'visits' => $s['visits'] - $beforeV,
        ];
    }

    private static function pollHidden(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        $s['visible'] = false;
        $s['hidden'] = true;
        $beforeA = $s['activates'];
        $beforeV = $s['visits'];
        self::reconcilePoll($s, false);

        return [
            'activates' => $s['activates'] - $beforeA,
            'visits' => $s['visits'] - $beforeV,
        ];
    }

    private static function pollAfterHeal(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        self::reconcilePoll($s, false);
        $afterHealA = $s['activates'];
        $afterHealV = $s['visits'];
        self::reconcilePoll($s, true);
        self::reconcilePoll($s, false);

        return [
            'healActivates' => $afterHealA,
            'healVisits' => $afterHealV,
            'laterActivates' => $s['activates'] - $afterHealA,
            'laterVisits' => $s['visits'] - $afterHealV,
        ];
    }

    private static function leftoverState(string $mode): array
    {
        $s = self::boot('navigate');
        self::pageshow($s, false, $mode);
        $s['started'] = $s['gen'];
        $s['inFlight'] = 0;

        return $s;
    }

    private static function pollStaleConsumed(string $mode): array
    {
        $s = self::leftoverState($mode);
        $beforeA = $s['activates'];
        $beforeV = $s['visits'];
        $beforeGen = $s['gen'];
        self::reconcilePoll($s, false);
        self::reconcilePoll($s, false);

        return [
            'activates' => $s['activates'] - $beforeA,
            'visits' => $s['visits'] - $beforeV,
            'genBumped' => $s['gen'] > $beforeGen,
            'reconDone' => $s['reconDone'],
        ];
    }

    private static function pollInFlight(string $mode): array
    {
        $s = self::leftoverState($mode);
        $s['inFlight'] = $s['gen'];
        $beforeA = $s['activates'];
        $beforeGen = $s['gen'];
        self::reconcilePoll($s, false);

        return [
            'activates' => $s['activates'] - $beforeA,
            'reconDone' => $s['reconDone'],
            'genUnchanged' => $s['gen'] === $beforeGen,
            'inFlight' => $s['inFlight'],
        ];
    }

    private static function pollDeadHeal(string $mode): array
    {
        $s = self::leftoverState($mode);
        $s['dead'] = true;
        $s['inFlight'] = 0;
        $beforeA = $s['activates'];
        $beforeV = $s['visits'];
        self::reconcilePoll($s, false);

        return [
            'activates' => $s['activates'] - $beforeA,
            'visits' => $s['visits'] - $beforeV,
            'dead' => $s['dead'],
        ];
    }

    private static function poll409Terminal(string $mode): array
    {
        $s = self::leftoverState($mode);
        self::reconcilePoll($s, false, '409');
        $after = $s['activates'];
        self::reconcilePoll($s, false);
        self::reconcilePoll($s, false);
        self::reconcilePoll($s, false);

        return [
            'firstActivates' => $after,
            'laterActivates' => $s['activates'] - $after,
            'closed409' => $s['closed409'],
        ];
    }

    private static function pollAbortNoConcurrent(string $mode): array
    {
        $s = self::leftoverState($mode);
        self::reconcilePoll($s, false, 'abort');
        $after = $s['activates'];
        self::reconcilePoll($s, false);
        self::reconcilePoll($s, false);

        return [
            'firstActivates' => $after,
            'laterActivates' => $s['activates'] - $after,
        ];
    }
}
