<?php

namespace App\Observers;

use App\Models\BlacklistReport;
use App\Services\TrustScoreService;

class BlacklistReportObserver
{
    public function created(BlacklistReport $report): void
    {
        $this->recalculateScore($report);
    }

    public function updated(BlacklistReport $report): void
    {
        $this->recalculateScore($report);
    }

    public function deleted(BlacklistReport $report): void
    {
        $this->recalculateScore($report);
    }

    private function recalculateScore(BlacklistReport $report): void
    {
        $client = $report->blacklistedClient;
        
        if ($client) {
            $trustScoreService = new TrustScoreService();
            $trustScoreService->updateClientScore($client);
        }
    }
}