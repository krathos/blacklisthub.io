<?php

namespace App\Services;

class CurrencyService
{
    /**
     * Exchange rates to USD (base currency)
     * These should be updated periodically or fetched from an API
     * Current rates as of December 2025 (approximate)
     *
     * @var array<string, float>
     */
    private static array $exchangeRates = [
        'USD' => 1.0,      // US Dollar (base)
        'MXN' => 0.059,    // Mexican Peso
        'EUR' => 1.10,     // Euro
        'GBP' => 1.27,     // British Pound
        'CAD' => 0.74,     // Canadian Dollar
        'BRL' => 0.20,     // Brazilian Real
        'ARS' => 0.0010,   // Argentine Peso
        'COP' => 0.00025,  // Colombian Peso
        'CLP' => 0.0011,   // Chilean Peso
        'PEN' => 0.27,     // Peruvian Sol
        'JPY' => 0.0071,   // Japanese Yen
        'CNY' => 0.14,     // Chinese Yuan
        'INR' => 0.012,    // Indian Rupee
        'AUD' => 0.66,     // Australian Dollar
        'NZD' => 0.61,     // New Zealand Dollar
    ];

    /**
     * Get default currency for country code
     *
     * @param string $countryCode ISO 3166-1 alpha-2
     * @return string ISO 4217 currency code
     */
    public static function getDefaultCurrency(string $countryCode): string
    {
        return match (strtoupper($countryCode)) {
            'MX' => 'MXN',
            'US' => 'USD',
            'ES' => 'EUR',
            'BR' => 'BRL',
            'AR' => 'ARS',
            'CO' => 'COP',
            'CL' => 'CLP',
            'PE' => 'PEN',
            'FR', 'DE', 'IT', 'PT', 'NL', 'BE', 'AT', 'IE', 'FI', 'GR' => 'EUR',
            'GB' => 'GBP',
            'CA' => 'CAD',
            'JP' => 'JPY',
            'CN' => 'CNY',
            'IN' => 'INR',
            'AU' => 'AUD',
            'NZ' => 'NZD',
            default => 'USD', // Default to USD
        };
    }

    /**
     * Convert amount to USD (base currency)
     *
     * @param float $amount
     * @param string $fromCurrency
     * @return float Amount in USD
     */
    public static function convertToUSD(float $amount, string $fromCurrency): float
    {
        $fromCurrency = strtoupper($fromCurrency);

        if ($fromCurrency === 'USD') {
            return $amount;
        }

        $rate = self::$exchangeRates[$fromCurrency] ?? 1.0;

        return round($amount * $rate, 2);
    }

    /**
     * Convert amount from USD to target currency
     *
     * @param float $usdAmount
     * @param string $toCurrency
     * @return float
     */
    public static function convertFromUSD(float $usdAmount, string $toCurrency): float
    {
        $toCurrency = strtoupper($toCurrency);

        if ($toCurrency === 'USD') {
            return $usdAmount;
        }

        $rate = self::$exchangeRates[$toCurrency] ?? 1.0;

        return round($usdAmount / $rate, 2);
    }

    /**
     * Convert between two currencies
     *
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    public static function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $usdAmount = self::convertToUSD($amount, $fromCurrency);
        return self::convertFromUSD($usdAmount, $toCurrency);
    }

    /**
     * Format amount with currency symbol
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public static function format(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);

        $symbol = match ($currency) {
            'USD' => '$',
            'MXN' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'CA$',
            'BRL' => 'R$',
            'ARS' => 'AR$',
            'COP' => 'CO$',
            'CLP' => 'CL$',
            'PEN' => 'S/',
            'JPY' => '¥',
            'CNY' => '¥',
            'INR' => '₹',
            'AUD' => 'A$',
            'NZD' => 'NZ$',
            default => $currency . ' ',
        };

        $decimals = in_array($currency, ['JPY', 'CLP']) ? 0 : 2;

        return $symbol . number_format($amount, $decimals, '.', ',');
    }

    /**
     * Get all supported currencies
     *
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        return [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CA$'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
            ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => 'AR$'],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => 'CO$'],
            ['code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => 'CL$'],
            ['code' => 'PEN', 'name' => 'Peruvian Sol', 'symbol' => 'S/'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$'],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$'],
        ];
    }

    /**
     * Get all supported countries with their currencies
     *
     * @return array
     */
    public static function getSupportedCountries(): array
    {
        return [
            ['code' => 'MX', 'name' => 'Mexico', 'currency' => 'MXN', 'tax_id' => 'RFC'],
            ['code' => 'US', 'name' => 'United States', 'currency' => 'USD', 'tax_id' => 'SSN/EIN'],
            ['code' => 'ES', 'name' => 'Spain', 'currency' => 'EUR', 'tax_id' => 'NIF/CIF'],
            ['code' => 'BR', 'name' => 'Brazil', 'currency' => 'BRL', 'tax_id' => 'CPF/CNPJ'],
            ['code' => 'AR', 'name' => 'Argentina', 'currency' => 'ARS', 'tax_id' => 'CUIT/CUIL'],
            ['code' => 'CO', 'name' => 'Colombia', 'currency' => 'COP', 'tax_id' => 'NIT'],
            ['code' => 'CL', 'name' => 'Chile', 'currency' => 'CLP', 'tax_id' => 'RUT'],
            ['code' => 'PE', 'name' => 'Peru', 'currency' => 'PEN', 'tax_id' => 'RUC'],
            ['code' => 'FR', 'name' => 'France', 'currency' => 'EUR', 'tax_id' => 'SIREN'],
            ['code' => 'DE', 'name' => 'Germany', 'currency' => 'EUR', 'tax_id' => 'Steuernummer'],
            ['code' => 'GB', 'name' => 'United Kingdom', 'currency' => 'GBP', 'tax_id' => 'UTR'],
            ['code' => 'CA', 'name' => 'Canada', 'currency' => 'CAD', 'tax_id' => 'SIN/BN'],
            ['code' => 'JP', 'name' => 'Japan', 'currency' => 'JPY', 'tax_id' => 'Corporate Number'],
            ['code' => 'CN', 'name' => 'China', 'currency' => 'CNY', 'tax_id' => 'USCC'],
            ['code' => 'IN', 'name' => 'India', 'currency' => 'INR', 'tax_id' => 'PAN'],
            ['code' => 'AU', 'name' => 'Australia', 'currency' => 'AUD', 'tax_id' => 'TFN/ABN'],
            ['code' => 'NZ', 'name' => 'New Zealand', 'currency' => 'NZD', 'tax_id' => 'IRD'],
        ];
    }

    /**
     * Validate currency code
     *
     * @param string $currency
     * @return bool
     */
    public static function isValidCurrency(string $currency): bool
    {
        return isset(self::$exchangeRates[strtoupper($currency)]);
    }

    /**
     * Get exchange rate to USD
     *
     * @param string $currency
     * @return float
     */
    public static function getExchangeRate(string $currency): float
    {
        return self::$exchangeRates[strtoupper($currency)] ?? 1.0;
    }
}
