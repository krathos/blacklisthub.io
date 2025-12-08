<?php

namespace App\Services;

class TaxIdValidationService
{
    /**
     * Validate tax ID based on country code
     *
     * @param string|null $taxId
     * @param string $countryCode ISO 3166-1 alpha-2
     * @return bool
     */
    public static function validate(?string $taxId, string $countryCode): bool
    {
        if (empty($taxId)) {
            return true; // Tax ID is optional
        }

        return match (strtoupper($countryCode)) {
            'MX' => self::validateRFC($taxId),
            'US' => self::validateSSN($taxId),
            'ES' => self::validateNIF($taxId),
            'BR' => self::validateCPFOrCNPJ($taxId),
            'AR' => self::validateCUITOrCUIL($taxId),
            'CO' => self::validateNIT($taxId),
            'CL' => self::validateRUT($taxId),
            'PE' => self::validateRUC($taxId),
            default => self::validateGeneric($taxId),
        };
    }

    /**
     * Get tax ID label for country
     *
     * @param string $countryCode
     * @return string
     */
    public static function getLabel(string $countryCode): string
    {
        return match (strtoupper($countryCode)) {
            'MX' => 'RFC',
            'US' => 'SSN/EIN',
            'ES' => 'NIF/CIF',
            'BR' => 'CPF/CNPJ',
            'AR' => 'CUIT/CUIL',
            'CO' => 'NIT',
            'CL' => 'RUT',
            'PE' => 'RUC',
            'FR' => 'SIREN/SIRET',
            'DE' => 'Steuernummer',
            'GB' => 'UTR',
            'CA' => 'SIN/BN',
            default => 'Tax ID',
        };
    }

    /**
     * Validate Mexican RFC (Registro Federal de Contribuyentes)
     * Format:
     * - Person: AAAA######XXX (13 chars)
     * - Company: AAA######XXX (12 chars)
     */
    private static function validateRFC(string $rfc): bool
    {
        $rfc = strtoupper(trim($rfc));

        // Person RFC: 4 letters + 6 digits + 3 alphanumeric
        $personPattern = '/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/';

        // Company RFC: 3 letters + 6 digits + 3 alphanumeric
        $companyPattern = '/^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/';

        return preg_match($personPattern, $rfc) || preg_match($companyPattern, $rfc);
    }

    /**
     * Validate US SSN (Social Security Number) or EIN (Employer Identification Number)
     * SSN Format: ###-##-#### or #########
     * EIN Format: ##-####### or #########
     */
    private static function validateSSN(string $ssn): bool
    {
        $ssn = preg_replace('/[^0-9]/', '', $ssn);

        // Must be exactly 9 digits
        if (strlen($ssn) !== 9) {
            return false;
        }

        // Cannot be all zeros or certain invalid patterns
        if ($ssn === '000000000' || $ssn === '111111111' || $ssn === '999999999') {
            return false;
        }

        return true;
    }

    /**
     * Validate Spanish NIF/CIF/NIE
     * NIF (personal): 8 digits + 1 letter
     * CIF (company): 1 letter + 7 digits + 1 alphanumeric
     * NIE (foreigner): X/Y/Z + 7 digits + 1 letter
     */
    private static function validateNIF(string $nif): bool
    {
        $nif = strtoupper(trim($nif));

        // NIF pattern
        if (preg_match('/^\d{8}[A-Z]$/', $nif)) {
            return true;
        }

        // CIF pattern
        if (preg_match('/^[ABCDEFGHJNPQRSUVW]\d{7}[A-J0-9]$/', $nif)) {
            return true;
        }

        // NIE pattern
        if (preg_match('/^[XYZ]\d{7}[A-Z]$/', $nif)) {
            return true;
        }

        return false;
    }

    /**
     * Validate Brazilian CPF or CNPJ
     * CPF (person): 11 digits
     * CNPJ (company): 14 digits
     */
    private static function validateCPFOrCNPJ(string $doc): bool
    {
        $doc = preg_replace('/[^0-9]/', '', $doc);

        if (strlen($doc) === 11) {
            return self::validateCPF($doc);
        }

        if (strlen($doc) === 14) {
            return self::validateCNPJ($doc);
        }

        return false;
    }

    /**
     * Validate Brazilian CPF with check digits
     */
    private static function validateCPF(string $cpf): bool
    {
        // Cannot be all same digits
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validate check digits
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$t] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate Brazilian CNPJ with check digits
     */
    private static function validateCNPJ(string $cnpj): bool
    {
        // Cannot be all same digits
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // Validate check digits
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights1[$i];
        }
        $digit1 = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);

        if ($cnpj[12] != $digit1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights2[$i];
        }
        $digit2 = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);

        return $cnpj[13] == $digit2;
    }

    /**
     * Validate Argentine CUIT/CUIL
     * Format: ##-########-# (11 digits)
     */
    private static function validateCUITOrCUIL(string $cuit): bool
    {
        $cuit = preg_replace('/[^0-9]/', '', $cuit);

        if (strlen($cuit) !== 11) {
            return false;
        }

        // Validate check digit
        $weights = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $sum += $cuit[$i] * $weights[$i];
        }

        $checkDigit = 11 - ($sum % 11);
        if ($checkDigit === 11) $checkDigit = 0;
        if ($checkDigit === 10) $checkDigit = 9;

        return $cuit[10] == $checkDigit;
    }

    /**
     * Validate Colombian NIT
     * Format: #########-# (9 or 10 digits + check digit)
     */
    private static function validateNIT(string $nit): bool
    {
        $nit = preg_replace('/[^0-9]/', '', $nit);

        if (strlen($nit) < 9 || strlen($nit) > 10) {
            return false;
        }

        return true; // Simplified validation
    }

    /**
     * Validate Chilean RUT
     * Format: ########-# (7-8 digits + check digit)
     */
    private static function validateRUT(string $rut): bool
    {
        $rut = strtoupper(preg_replace('/[^0-9K]/', '', $rut));

        if (strlen($rut) < 8 || strlen($rut) > 9) {
            return false;
        }

        $checkDigit = substr($rut, -1);
        $number = substr($rut, 0, -1);

        $sum = 0;
        $multiplier = 2;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $sum += $number[$i] * $multiplier;
            $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
        }

        $calculatedDigit = 11 - ($sum % 11);
        if ($calculatedDigit === 11) $calculatedDigit = '0';
        if ($calculatedDigit === 10) $calculatedDigit = 'K';

        return $checkDigit == $calculatedDigit;
    }

    /**
     * Validate Peruvian RUC
     * Format: 11 digits
     */
    private static function validateRUC(string $ruc): bool
    {
        $ruc = preg_replace('/[^0-9]/', '', $ruc);

        return strlen($ruc) === 11;
    }

    /**
     * Generic validation - just check if it's alphanumeric and reasonable length
     */
    private static function validateGeneric(string $taxId): bool
    {
        $taxId = trim($taxId);

        // Must be between 5 and 20 characters
        // Must contain at least one alphanumeric character
        return strlen($taxId) >= 5
            && strlen($taxId) <= 20
            && preg_match('/^[A-Z0-9\-\s]+$/i', $taxId);
    }
}
