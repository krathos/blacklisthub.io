<?php

namespace App\Services;

use App\Models\BlacklistedClient;
use Carbon\Carbon;

class TrustScoreService
{
    public function calculateTrustScore(BlacklistedClient $client): array
    {
        $score = 100;
        $factors = [];

        // Factor 1: Número de reportes (cada reporte -15 puntos)
        $reportsDeduction = min($client->reports_count * 15, 60);
        $score -= $reportsDeduction;
        
        if ($client->reports_count >= 5) {
            $factors[] = "Reported by {$client->reports_count}+ companies (Critical)";
        } elseif ($client->reports_count >= 3) {
            $factors[] = "Reported by {$client->reports_count} companies";
        } elseif ($client->reports_count >= 2) {
            $factors[] = "Multiple company reports ({$client->reports_count})";
        }

        // Factor 2: Deuda total (convertida a USD para comparación consistente)
        $totalDebtUSD = 0;
        $reports = $client->blacklistReports()->whereNotNull('debt_amount')->get();

        foreach ($reports as $report) {
            $debtInUSD = CurrencyService::convertToUSD(
                $report->debt_amount,
                $report->currency ?? 'USD'
            );
            $totalDebtUSD += $debtInUSD;
        }

        if ($totalDebtUSD > 10000) {
            $score -= 20;
            $factors[] = "Total debt exceeds $10,000 USD (~" . CurrencyService::format($totalDebtUSD, 'USD') . ")";
        } elseif ($totalDebtUSD > 5000) {
            $score -= 10;
            $factors[] = "Total debt exceeds $5,000 USD (~" . CurrencyService::format($totalDebtUSD, 'USD') . ")";
        } elseif ($totalDebtUSD > 1000) {
            $score -= 5;
            $factors[] = "Debt amount: " . CurrencyService::format($totalDebtUSD, 'USD');
        }

        // Factor 3: Tipos de fraude diferentes
        $fraudTypesCount = $client->blacklistReports()
            ->whereNotNull('fraud_type_id')
            ->distinct('fraud_type_id')
            ->count('fraud_type_id');
        
        if ($fraudTypesCount >= 3) {
            $score -= 15;
            $factors[] = "Multiple fraud types ({$fraudTypesCount} different types)";
        } elseif ($fraudTypesCount >= 2) {
            $score -= 8;
            $factors[] = "Different fraud patterns ({$fraudTypesCount} types)";
        }

        // Factor 4: Actividad reciente (últimos 30 días)
        $recentReports = $client->blacklistReports()
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();
        
        if ($recentReports >= 2) {
            $score -= 10;
            $factors[] = "Recent activity ({$recentReports} reports in last 30 days)";
        }

        // Factor 5: Múltiples teléfonos (posible evasión)
        $phoneCount = $client->phoneNumbers()->count();
        if ($phoneCount >= 3) {
            $score -= 5;
            $factors[] = "Multiple phone numbers used ({$phoneCount} phones)";
        }

        // Factor 6: Distribución geográfica (múltiples ciudades/estados)
        $citiesCount = $client->blacklistReports()
            ->distinct('blacklisted_client_id')
            ->count();
        
        if ($citiesCount > 1 && ($client->city || $client->state)) {
            $score -= 5;
            $factors[] = "Geographic inconsistencies detected";
        }

        // Asegurar que el score esté entre 0 y 100
        $score = max(0, min(100, $score));

        // Determinar nivel de riesgo
        $riskLevel = $this->getRiskLevel($score);
        $recommendation = $this->getRecommendation($score);

        return [
            'trust_score' => $score,
            'risk_level' => $riskLevel,
            'risk_factors' => $factors,
            'total_debt' => round($totalDebtUSD, 2),
            'total_debt_currency' => 'USD',
            'recommendation' => $recommendation,
            'analysis' => [
                'reports_count' => $client->reports_count,
                'fraud_types_count' => $fraudTypesCount,
                'phone_numbers_count' => $phoneCount,
                'recent_reports' => $recentReports,
                'first_report' => $client->blacklistReports()->min('created_at'),
                'last_report' => $client->blacklistReports()->max('created_at'),
            ]
        ];
    }

    private function getRiskLevel(int $score): string
    {
        if ($score >= 80) return 'LOW';
        if ($score >= 50) return 'MEDIUM';
        if ($score >= 25) return 'HIGH';
        return 'CRITICAL';
    }

    private function getRecommendation(int $score): string
    {
        return match(true) {
            $score >= 80 => 'Proceed with standard verification process',
            $score >= 50 => 'Request additional verification and references',
            $score >= 25 => 'Require upfront payment or secured transaction',
            default => 'AVOID - High fraud risk. Do not proceed with transaction'
        };
    }

    public function updateClientScore(BlacklistedClient $client): void
    {
        $scoreData = $this->calculateTrustScore($client);
        
        $client->update([
            'trust_score' => $scoreData['trust_score'],
            'risk_level' => $scoreData['risk_level'],
            'risk_factors' => $scoreData['risk_factors'],
            'total_debt' => $scoreData['total_debt'],
        ]);
    }
}