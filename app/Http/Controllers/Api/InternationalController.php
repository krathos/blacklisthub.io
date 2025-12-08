<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Services\TaxIdValidationService;
use Illuminate\Http\Request;

class InternationalController extends Controller
{
    /**
     * Get Supported Countries
     *
     * Get list of all supported countries with their currencies and tax ID labels.
     * Useful for populating country selection dropdowns in UIs.
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Countries retrieved successfully",
     *   "data": {
     *     "countries": [
     *       {
     *         "code": "MX",
     *         "name": "Mexico",
     *         "currency": "MXN",
     *         "tax_id": "RFC"
     *       },
     *       {
     *         "code": "US",
     *         "name": "United States",
     *         "currency": "USD",
     *         "tax_id": "SSN/EIN"
     *       }
     *     ],
     *     "total": 17
     *   }
     * }
     */
    public function countries()
    {
        $countries = CurrencyService::getSupportedCountries();

        return api_success([
            'countries' => $countries,
            'total' => count($countries),
        ], 'Countries retrieved successfully', 200);
    }

    /**
     * Get Supported Currencies
     *
     * Get list of all supported currencies with their codes, names, and symbols.
     * Useful for populating currency selection dropdowns in UIs.
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Currencies retrieved successfully",
     *   "data": {
     *     "currencies": [
     *       {
     *         "code": "USD",
     *         "name": "US Dollar",
     *         "symbol": "$"
     *       },
     *       {
     *         "code": "MXN",
     *         "name": "Mexican Peso",
     *         "symbol": "$"
     *       },
     *       {
     *         "code": "EUR",
     *         "name": "Euro",
     *         "symbol": "€"
     *       }
     *     ],
     *     "total": 15
     *   }
     * }
     */
    public function currencies()
    {
        $currencies = CurrencyService::getSupportedCurrencies();

        return api_success([
            'currencies' => $currencies,
            'total' => count($currencies),
        ], 'Currencies retrieved successfully', 200);
    }

    /**
     * Get Country Details
     *
     * Get detailed information about a specific country including currency and tax ID format.
     *
     * @urlParam code string required The country code (ISO 3166-1 alpha-2). Example: MX
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Country details retrieved successfully",
     *   "data": {
     *     "country": {
     *       "code": "MX",
     *       "name": "Mexico",
     *       "currency": "MXN",
     *       "tax_id": "RFC",
     *       "currency_details": {
     *         "code": "MXN",
     *         "name": "Mexican Peso",
     *         "symbol": "$"
     *       }
     *     }
     *   }
     * }
     *
     * @response 404 scenario="Country Not Found" {
     *   "success": false,
     *   "status": 404,
     *   "message": "Country not found",
     *   "data": []
     * }
     */
    public function countryDetails($code)
    {
        $countries = CurrencyService::getSupportedCountries();
        $country = collect($countries)->firstWhere('code', strtoupper($code));

        if (!$country) {
            return api_error('Country not found', 404);
        }

        $currencies = CurrencyService::getSupportedCurrencies();
        $currencyDetails = collect($currencies)->firstWhere('code', $country['currency']);

        return api_success([
            'country' => array_merge($country, [
                'currency_details' => $currencyDetails,
            ]),
        ], 'Country details retrieved successfully', 200);
    }

    /**
     * Convert Currency
     *
     * Convert an amount from one currency to another using current exchange rates.
     *
     * @queryParam amount number required The amount to convert. Example: 1000
     * @queryParam from string required Source currency code (ISO 4217). Example: MXN
     * @queryParam to string required Target currency code (ISO 4217). Example: USD
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "status": 200,
     *   "message": "Currency converted successfully",
     *   "data": {
     *     "original": {
     *       "amount": 1000,
     *       "currency": "MXN",
     *       "formatted": "$1,000.00"
     *     },
     *     "converted": {
     *       "amount": 59,
     *       "currency": "USD",
     *       "formatted": "$59.00"
     *     },
     *     "exchange_rate": 0.059
     *   }
     * }
     *
     * @response 422 scenario="Validation Error" {
     *   "success": false,
     *   "status": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "amount": ["The amount field is required."]
     *     }
     *   }
     * }
     */
    public function convertCurrency(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        $fromCurrency = strtoupper($request->from);
        $toCurrency = strtoupper($request->to);

        if (!CurrencyService::isValidCurrency($fromCurrency)) {
            return api_error("Invalid source currency: {$fromCurrency}", 422);
        }

        if (!CurrencyService::isValidCurrency($toCurrency)) {
            return api_error("Invalid target currency: {$toCurrency}", 422);
        }

        $convertedAmount = CurrencyService::convert(
            $request->amount,
            $fromCurrency,
            $toCurrency
        );

        $exchangeRate = CurrencyService::getExchangeRate($fromCurrency);

        return api_success([
            'original' => [
                'amount' => (float) $request->amount,
                'currency' => $fromCurrency,
                'formatted' => CurrencyService::format($request->amount, $fromCurrency),
            ],
            'converted' => [
                'amount' => $convertedAmount,
                'currency' => $toCurrency,
                'formatted' => CurrencyService::format($convertedAmount, $toCurrency),
            ],
            'exchange_rate' => $exchangeRate,
        ], 'Currency converted successfully', 200);
    }
}
