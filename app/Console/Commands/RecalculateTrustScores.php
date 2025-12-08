<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BlacklistedClient;
use App\Services\TrustScoreService;

class RecalculateTrustScores extends Command
{
    protected $signature = 'trust:recalculate';
    protected $description = 'Recalculate trust scores for all blacklisted clients';

    public function handle()
    {
        $this->info('Recalculating trust scores...');
        
        $trustScoreService = new TrustScoreService();
        $clients = BlacklistedClient::all();
        
        $bar = $this->output->createProgressBar($clients->count());
        $bar->start();

        foreach ($clients as $client) {
            $trustScoreService->updateClientScore($client);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Trust scores recalculated successfully!');
    }
}