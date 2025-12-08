# BlacklistHub API - Project Context

## Project Overview
BlacklistHub is a collaborative fraud prevention REST API that allows companies across different industries to report and search for problematic clients in a shared database. It functions as a "shared blacklist network" where businesses protect each other by reporting clients who have committed fraud, failed to pay, or engaged in other problematic behaviors.

## Tech Stack
- **Framework:** Laravel 11.x
- **PHP Version:** 8.2+
- **Database:** MySQL 8.0+
- **Documentation:** Scribe (auto-generated)
- **Authentication:** Bearer Token + API Keys (X-API-Key header)
- **API Version:** v1

## Domain Structure
- **API Domain:** `api.blacklisthub.io/v1` → API endpoints
- **Docs:** `api.blacklisthub.io/docs` → API documentation
- **Root Path:** `/` → Returns JSON 404 error (API-only application)

## Core Concepts

### Business Logic
1. **Multi-report System:** The same company CAN report the same client multiple times (each incident is separate)
2. **Client Matching:** Clients are matched by email OR phone number
3. **Trust Score:** Algorithmic score (0-100) that decreases with more reports, higher debt, and fraud patterns
4. **Phone Tracking:** Multiple phone numbers per client are tracked to identify fraud patterns
5. **Cross-category:** Clients can be reported across different business categories

### Key Features
- Company registration with admin approval workflow
- Trust Score system (0-100) with risk levels (LOW, MEDIUM, HIGH, CRITICAL)
- Advanced search by email, phone, name, tax ID, fraud type, category
- Bulk operations for uploading multiple reports
- Fraud type classification (11 predefined types)
- Statistics and insights dashboard
- Phone number tracking across reports
- **International support with 17 countries and 15 currencies**
- **Multi-currency debt tracking with automatic USD conversion**
- **Country-specific Tax ID validation (RFC, SSN, NIF, CPF, etc.)**
- **Jurisdictional filtering by country**

## International Support

BlacklistHub is a fully internationalized API that supports companies from multiple countries with automatic currency conversion, country-specific tax ID validation, and jurisdictional data separation.

### Supported Countries (17)

The API currently supports the following countries with their respective currencies and tax ID formats:

| Country | Code | Currency | Tax ID Format |
|---------|------|----------|---------------|
| 🇲🇽 Mexico | MX | MXN | RFC |
| 🇺🇸 United States | US | USD | SSN/EIN |
| 🇪🇸 Spain | ES | EUR | NIF/CIF |
| 🇧🇷 Brazil | BR | BRL | CPF/CNPJ |
| 🇦🇷 Argentina | AR | ARS | CUIT/CUIL |
| 🇨🇴 Colombia | CO | COP | NIT |
| 🇨🇱 Chile | CL | CLP | RUT |
| 🇵🇪 Peru | PE | PEN | RUC |
| 🇫🇷 France | FR | EUR | SIREN |
| 🇩🇪 Germany | DE | EUR | Steuernummer |
| 🇬🇧 United Kingdom | GB | GBP | UTR |
| 🇨🇦 Canada | CA | CAD | SIN/BN |
| 🇯🇵 Japan | JP | JPY | Corporate Number |
| 🇨🇳 China | CN | CNY | USCC |
| 🇮🇳 India | IN | INR | PAN |
| 🇦🇺 Australia | AU | AUD | TFN/ABN |
| 🇳🇿 New Zealand | NZ | NZD | IRD |

### Supported Currencies (15)

USD, MXN, EUR, GBP, CAD, BRL, ARS, COP, CLP, PEN, JPY, CNY, INR, AUD, NZD

### Key International Features

#### 1. Jurisdictional Filtering
Companies automatically see only clients from their own country by default, ensuring compliance with regional data protection laws (GDPR, CCPA, LFPDPPP).

```bash
# Mexican company sees only Mexican clients
GET /v1/blacklist?country_code=MX

# Can override to view other countries if needed
GET /v1/blacklist?country_code=US
```

#### 2. Multi-Currency Support
- Debts are stored in their original currency (MXN, USD, EUR, etc.)
- Trust Score calculations convert all debts to USD internally for fair comparison
- Display always shows original currency to users
- Automatic currency detection based on company's country

**Example:**
```json
{
  "debt_amount": 5000.00,
  "currency": "MXN",           // Original currency preserved
  "trust_score": 65,            // Calculated using USD equivalent
  "total_debt_usd": 295.00     // Internal calculation
}
```

#### 3. Tax ID Validation
The API validates tax IDs according to each country's format:

- **Mexico (RFC)**: AAAA######XXX (person) or AAA######XXX (company)
- **USA (SSN/EIN)**: ###-##-#### or #########
- **Spain (NIF/CIF/NIE)**: 8 digits + letter, or specific patterns
- **Brazil (CPF/CNPJ)**: 11 digits (person) or 14 digits (company) with check digits
- **Argentina (CUIT/CUIL)**: ##-########-# with check digit
- **Chile (RUT)**: 7-8 digits + check digit
- And more...

Invalid tax IDs are automatically rejected with helpful error messages:
```json
{
  "success": false,
  "message": "Invalid RFC format for MX",
  "status": 422
}
```

#### 4. Automatic Currency Detection
When a company registers or reports a client, the currency is automatically detected from their country:

```bash
# Register company from Mexico
POST /v1/auth/register
{
  "country_code": "MX"  # currency auto-set to MXN
}

# Report client - currency inherited from company
POST /v1/blacklist
{
  "debt_amount": 5000  # Automatically saved as MXN
}
```

### International Endpoints

#### Get Countries
```bash
GET /v1/countries
```
Returns list of all supported countries with their currencies and tax ID labels.

#### Get Country Details
```bash
GET /v1/countries/MX
```
Returns detailed information about a specific country.

#### Get Currencies
```bash
GET /v1/currencies
```
Returns list of all supported currencies with codes, names, and symbols.

#### Convert Currency
```bash
GET /v1/currency/convert?amount=1000&from=MXN&to=USD
```
Converts an amount between two currencies using current exchange rates.

**Response:**
```json
{
  "success": true,
  "data": {
    "original": {
      "amount": 1000,
      "currency": "MXN",
      "formatted": "$1,000.00"
    },
    "converted": {
      "amount": 59,
      "currency": "USD",
      "formatted": "$59.00"
    },
    "exchange_rate": 0.059
  }
}
```

### Database Schema Updates

#### Updated Fields

**`companies` table:**
- `country_code` (VARCHAR(2), indexed) - ISO 3166-1 alpha-2 country code
- `currency` (VARCHAR(3)) - ISO 4217 currency code

**`blacklisted_clients` table:**
- `tax_id` (VARCHAR(50)) - Generic tax ID (was `rfc_tax_id`)
- `country_code` (VARCHAR(2), indexed) - ISO country code (was `country`)
- `currency` (VARCHAR(3)) - Client's currency

**`blacklist_reports` table:**
- `currency` (VARCHAR(3), indexed) - Currency context for debt_amount

### Services

#### TaxIdValidationService
Located: `app/Services/TaxIdValidationService.php`

Validates tax IDs for multiple countries with country-specific algorithms.

**Methods:**
- `validate(?string $taxId, string $countryCode): bool` - Validate tax ID format
- `getLabel(string $countryCode): string` - Get tax ID label for country

**Supported Validations:**
- Mexico (RFC) - Full validation with check digits
- USA (SSN/EIN) - Format and invalid pattern detection
- Spain (NIF/CIF/NIE) - Multiple format support
- Brazil (CPF/CNPJ) - Check digit validation
- Argentina (CUIT/CUIL) - Check digit validation
- Chile (RUT) - Check digit validation
- Generic validation for other countries

#### CurrencyService
Located: `app/Services/CurrencyService.php`

Handles currency operations including conversion, formatting, and country-currency mapping.

**Methods:**
- `getDefaultCurrency(string $countryCode): string` - Get default currency for country
- `convertToUSD(float $amount, string $fromCurrency): float` - Convert to USD
- `convertFromUSD(float $usdAmount, string $toCurrency): float` - Convert from USD
- `convert(float $amount, string $from, string $to): float` - Convert between currencies
- `format(float $amount, string $currency): string` - Format with currency symbol
- `getSupportedCurrencies(): array` - Get all supported currencies
- `getSupportedCountries(): array` - Get all supported countries
- `isValidCurrency(string $currency): bool` - Validate currency code
- `getExchangeRate(string $currency): float` - Get exchange rate to USD

**Exchange Rates:**
Exchange rates are currently stored as static values in the service (approximate December 2025 rates). For production, consider integrating with an external API like:
- ExchangeRate-API (free)
- Fixer.io
- CurrencyAPI.com

### Usage Examples

#### Example 1: Register Mexican Company
```bash
POST /v1/auth/register
{
  "name": "Estafeta Express",
  "email": "contacto@estafeta.mx",
  "password": "password123",
  "country_code": "MX"
  # currency automatically set to MXN
}
```

#### Example 2: Report Client with Auto-Detection
```bash
POST /v1/blacklist
{
  "category_id": 1,
  "name": "Fernando García",
  "email": "fernando@gmail.com",
  "phone": "3331234567",
  "tax_id": "GACF850101ABC",  # Validated as RFC
  "debt_amount": 5000.00       # Saved as MXN (from company)
}
```

#### Example 3: Search Clients by Country
```bash
# Mexican company sees only Mexican clients by default
GET /v1/blacklist/search?phone=333

# Can search in other countries
GET /v1/blacklist/search?country_code=US&email=john
```

#### Example 4: Multi-Currency Statistics
```bash
GET /v1/stats?country_code=MX
```

**Response:**
```json
{
  "country": {
    "code": "MX",
    "name": "Mexico",
    "currency": "MXN"
  },
  "total_blacklisted_clients": 150,
  "total_debt_usd": 125430.50,  # All debts converted to USD
  "risk_distribution": {
    "CRITICAL": 10,
    "HIGH": 25,
    "MEDIUM": 50,
    "LOW": 65
  }
}
```

### Benefits of International Support

1. **Legal Compliance**: Automatic jurisdictional separation complies with GDPR, CCPA, and other regional laws
2. **Fair Risk Assessment**: Multi-currency conversion ensures Trust Scores are calculated fairly regardless of currency
3. **Regional Marketing**: Enable country-specific marketing (e.g., "Fraud Prevention API for Mexico")
4. **Data Accuracy**: Tax ID validation reduces data entry errors
5. **User Experience**: Automatic currency detection simplifies the API for international users
6. **Scalability**: Easy to add new countries and currencies

### Future Enhancements

1. **Real-Time Exchange Rates**: Integrate with external API for live currency conversion
2. **Regional Webhooks**: Notify only companies in the same country
3. **Multi-Language Support**: Translate error messages and responses
4. **Regional Fraud Patterns**: AI-based fraud detection specific to each country
5. **Cross-Border Fraud Detection**: Optional flag to detect clients operating across multiple countries

## Database Schema

### Core Tables

#### `companies`
Registered companies that can report clients
```
- id
- name
- email (unique)
- password (hashed bcrypt)
- country_code (string(2), indexed) // ISO 3166-1 alpha-2
- currency (string(3)) // ISO 4217
- is_active (boolean, default false) // Admin approval required
- api_token (nullable, sha256 hashed)
- created_at, updated_at
```

#### `admins`
System administrators
```
- id
- name
- email (unique)
- password (hashed bcrypt)
- api_token (nullable, sha256 hashed)
- created_at, updated_at
```

**Default Admin:**
- Email: `admin@blacklist.com`
- Password: `password123`

#### `categories`
Business categories for classification
```
- id
- name
- slug (unique)
- description (nullable)
- created_at, updated_at
```

**Seeded Categories:**
- Shipping & Logistics
- E-commerce
- Financial Services
- Professional Services
- Real Estate
- Telecommunications
- Hospitality
- Healthcare
- Education
- Other

#### `fraud_types`
Types of fraud for classification
```
- id
- name
- slug (unique)
- description (nullable)
- is_active (boolean)
- created_at, updated_at
```

**Seeded Fraud Types:**
1. Non Payment
2. Chargeback Fraud
3. Fake Information
4. Stolen Credit Card
5. Package Theft
6. Identity Theft
7. Address Fraud
8. Return Fraud
9. Account Takeover
10. Multiple Disputes
11. Other

#### `blacklisted_clients`
Reported problematic clients
```
- id
- category_id (FK -> categories)
- name
- email (indexed)
- phone (indexed)
- ip_address (nullable)
- tax_id (nullable) // Generic Tax ID (was rfc_tax_id)
- address, city, state, postal_code (all nullable)
- country_code (string(2), indexed) // ISO 3166-1 alpha-2 (was country)
- currency (string(3)) // ISO 4217
- reports_count (integer, default 1) // Total number of incidents/reports
- trust_score (integer, 0-100, default 100) // AI-calculated risk score
- risk_level (string: LOW|MEDIUM|HIGH|CRITICAL)
- risk_factors (json, nullable) // Array of risk factor descriptions
- total_debt (decimal, default 0) // Sum of all debts in USD
- created_at, updated_at
```

**Virtual Attributes:**
- `risk_badge`: Returns emoji (🟢🟡🟠🔴) based on risk_level
- `unique_companies_count`: Count of distinct companies that reported this client

#### `blacklist_reports`
Individual company reports about clients
```
- id
- blacklisted_client_id (FK -> blacklisted_clients, cascade)
- company_id (FK -> companies, cascade)
- debt_amount (decimal, nullable)
- currency (string(3), indexed) // ISO 4217
- incident_date (date, nullable)
- fraud_type_id (FK -> fraud_types, set null)
- additional_info (text, nullable)
- created_at, updated_at
```

**Important:** NO unique constraint - same company can report same client multiple times

#### `phone_numbers`
Track multiple phone numbers per client
```
- id
- blacklisted_client_id (FK -> blacklisted_clients, cascade)
- phone (indexed)
- reported_by_company_id (FK -> companies)
- created_at
```

#### `api_keys`
API keys for company authentication
```
- id
- company_id (FK -> companies, cascade)
- name (descriptive name: "Production Server", "Mobile App", etc.)
- key_hash (SHA-256 hash, unique)
- key_prefix (first 16 chars for display: "blh_AYpbX24Ypo32...")
- is_active (boolean, default true)
- last_used_at (timestamp, nullable)
- created_at, updated_at
```

**Key Format:**
- Prefix: `blh_`
- Total length: 64 characters (blh_ + 60 random alphanumeric)
- Example: `blh_AYpbX24Ypo32HlIMocKVb1OCPxdvKBtfiSMg304Ffceb40c2a1b2c3`

**Features:**
- Unlimited keys per company (default limit: 10)
- Permanent (no expiration)
- Can be activated/deactivated without deletion
- Tracks last usage timestamp

### Relationships
- `BlacklistedClient` hasMany `BlacklistReport`
- `BlacklistedClient` hasMany `PhoneNumber`
- `BlacklistedClient` belongsTo `Category`
- `BlacklistReport` belongsTo `BlacklistedClient`
- `BlacklistReport` belongsTo `Company`
- `BlacklistReport` belongsTo `FraudType`
- `PhoneNumber` belongsTo `BlacklistedClient`
- `PhoneNumber` belongsTo `Company` (reported_by_company_id)
- `Company` hasMany `BlacklistReport`
- `Company` hasMany `ApiKey`
- `Category` hasMany `BlacklistedClient`
- `FraudType` hasMany `BlacklistReport`
- `ApiKey` belongsTo `Company`

## Trust Score System

### Algorithm (`TrustScoreService`)
Starting score: 100

**Deductions:**
- Each report: -15 points (max -60)
- Debt > $10,000: -20 points
- Debt > $5,000: -10 points
- Debt > $1,000: -5 points
- 3+ fraud types: -15 points
- 2 fraud types: -8 points
- 2+ reports in last 30 days: -10 points
- 3+ phone numbers: -5 points
- Geographic inconsistencies: -5 points

**Score = max(0, min(100, calculated_score))**

### Risk Levels
- **LOW** (80-100): 🟢 "Proceed with standard verification"
- **MEDIUM** (50-79): 🟡 "Request additional verification"
- **HIGH** (25-49): 🟠 "Require upfront payment"
- **CRITICAL** (0-24): 🔴 "AVOID - High fraud risk"

### Auto-Recalculation
Trust scores are automatically recalculated via `BlacklistReportObserver` whenever:
- A new report is created
- A report is updated
- A report is deleted

**Manual Recalculation:**
```bash
php artisan trust:recalculate
```

## API Endpoints

### Base URL
```
https://api.blacklisthub.io/v1
```

### Authentication Endpoints

#### Company Authentication
```
POST   /auth/register          - Register new company
POST   /auth/login             - Company login (returns bearer token)
POST   /auth/logout            - Company logout (requires auth)
```

#### Admin Authentication
```
POST   /admin/login            - Admin login
POST   /admin/logout           - Admin logout
```

### Public Endpoints (Require Company Auth)

#### Categories & Fraud Types
```
GET    /categories             - List all business categories
GET    /fraud-types            - List all fraud types
```

#### International Support
```
GET    /countries              - List all supported countries with currency and tax ID info
GET    /countries/{code}       - Get detailed information about a specific country
GET    /currencies             - List all supported currencies with codes and symbols
GET    /currency/convert       - Convert amount between currencies (query params: amount, from, to)
```

#### Blacklist Operations
```
POST   /blacklist              - Report single client
POST   /blacklist/bulk         - Report multiple clients (bulk)
GET    /blacklist              - List all blacklisted clients (paginated)
GET    /blacklist/search       - Advanced search with filters
GET    /blacklist/{id}         - Get client details with all reports
PUT    /blacklist/{id}         - Update client info (only if you reported them)
GET    /trust-analysis/{id}    - Get detailed trust score analysis
```

#### Statistics
```
GET    /stats                  - Get global and company-specific statistics
```

#### API Keys Management
```
GET    /api-keys               - List all API keys for authenticated company
POST   /api-keys               - Create new API key
PUT    /api-keys/{id}          - Update API key (name or active status)
DELETE /api-keys/{id}          - Delete API key
```

### Admin-Only Endpoints

#### Company Management
```
GET    /admin/companies              - List all companies (paginated)
PUT    /admin/companies/{id}/activate   - Activate company
PUT    /admin/companies/{id}/deactivate - Deactivate company
```

#### Category Management
```
GET    /admin/categories         - List categories with counts
POST   /admin/categories         - Create category
PUT    /admin/categories/{id}    - Update category
DELETE /admin/categories/{id}    - Delete category
```

#### Fraud Type Management
```
GET    /admin/fraud-types        - List fraud types with counts
POST   /admin/fraud-types        - Create fraud type
PUT    /admin/fraud-types/{id}   - Update fraud type
DELETE /admin/fraud-types/{id}   - Delete fraud type
```

#### Delete Operations
```
DELETE /blacklist/{id}           - Delete client (admin only)
```

#### API Keys Management (Admin)
```
GET    /admin/api-keys                          - List all API keys (all companies)
GET    /admin/companies/{id}/api-keys           - List API keys for specific company
POST   /admin/companies/{id}/api-keys           - Create API key for company
PUT    /admin/api-keys/{id}                     - Update API key
DELETE /admin/api-keys/{id}                     - Delete API key
```

## API-Only Application

BlacklistHub is a **pure API application** - it does not serve HTML views. All responses, including errors, are returned in JSON format.

**Important:**
- Accessing the root path `/` returns a JSON 404 error
- All undefined routes return JSON error responses
- There is no landing page or web interface
- The application is designed for API consumption only

### Accessing the API
```bash
# Root path (/)
curl http://localhost:8000/
# Response:
# {"success":false,"status":404,"message":"Endpoint no encontrado.","data":{}}

# API endpoints are under /v1/
curl http://localhost:8000/v1/categories -H "Authorization: Bearer TOKEN"
```

## API Response Format

All API responses follow this structure:

### Success Response
```json
{
    "success": true,
    "status": 200,
    "message": "Operation successful",
    "data": {
        // Response data here
    }
}
```

### Error Response
```json
{
    "success": false,
    "status": 400,
    "message": "Error message",
    "data": {
        "errors": {
            // Validation errors or additional info
        }
    }
}
```

## Authentication

### Bearer Token Authentication
Companies receive a token upon login:
```bash
curl -X POST https://api.blacklisthub.io/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"company@example.com","password":"password"}'
```

Response:
```json
{
    "success": true,
    "status": 200,
    "message": "Login successful",
    "data": {
        "company": {...},
        "token": "XyZ9aB3cD4eF5gH6iJ7kL8mN..."
    }
}
```

Use token in subsequent requests:
```bash
Authorization: Bearer XyZ9aB3cD4eF5gH6iJ7kL8mN...
```

**Token Storage:**
- Tokens are SHA-256 hashed in database
- Original token (80 chars) returned to user only once
- Tokens don't expire (logout to invalidate)

### Middleware
- `company.auth` - Validates company bearer token OR X-API-Key header, checks if company is active
- `admin.auth` - Validates admin bearer token

## API Keys Authentication

### Overview
Companies can authenticate using **two methods**:
1. **Bearer Token** - Session-based token from login (existing method)
2. **X-API-Key Header** - Permanent API key for server-to-server communication (new)

### Using API Keys

#### Method 1: Bearer Token (Session-based)
```bash
curl -X GET https://api.blacklisthub.io/v1/blacklist \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN"
```

#### Method 2: X-API-Key Header (Permanent)
```bash
curl -X GET https://api.blacklisthub.io/v1/blacklist \
  -H "X-API-Key: blh_AYpbX24Ypo32HlIMocKVb1OCPxdvKBtfiSMg304Ffceb40c2a1b2c3"
```

### Creating API Keys

#### As Company (Self-service)
```bash
# 1. Login to get bearer token
curl -X POST https://api.blacklisthub.io/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"company@example.com","password":"password"}'

# 2. Create API key
curl -X POST https://api.blacklisthub.io/v1/api-keys \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Production Server"}'
```

**Response:**
```json
{
  "success": true,
  "status": 201,
  "message": "API key created successfully",
  "data": {
    "api_key": {
      "id": 1,
      "company_id": 1,
      "name": "Production Server",
      "key_prefix": "blh_AYpbX24Ypo32...",
      "is_active": true,
      "last_used_at": null,
      "created_at": "2025-12-06T18:45:00.000000Z"
    },
    "plain_key": "blh_AYpbX24Ypo32HlIMocKVb1OCPxdvKBtfiSMg304Ffceb40c2a1b2c3",
    "warning": "Store this API key securely. It will not be shown again."
  }
}
```

⚠️ **CRITICAL:** The `plain_key` is shown **ONLY ONCE**. Store it securely!

#### As Admin (For any company)
```bash
curl -X POST https://api.blacklisthub.io/v1/admin/companies/1/api-keys \
  -H "Authorization: Bearer ADMIN_BEARER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin Created Key"}'
```

### Managing API Keys

#### List API Keys
```bash
# Company - List own keys
curl -X GET https://api.blacklisthub.io/v1/api-keys \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN"

# Admin - List keys for company
curl -X GET https://api.blacklisthub.io/v1/admin/companies/1/api-keys \
  -H "Authorization: Bearer ADMIN_BEARER_TOKEN"
```

#### Update API Key (Name or Status)
```bash
curl -X PUT https://api.blacklisthub.io/v1/api-keys/1 \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Updated Name","is_active":false}'
```

#### Delete API Key
```bash
curl -X DELETE https://api.blacklisthub.io/v1/api-keys/1 \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN"
```

### API Key Features
- ✅ **Multiple keys per company** - No hard limit (soft limit: 10)
- ✅ **Permanent** - Never expire until deleted
- ✅ **Descriptive names** - Identify purpose (e.g., "Production", "Staging", "Mobile App")
- ✅ **Activate/Deactivate** - Toggle without deletion
- ✅ **Usage tracking** - `last_used_at` timestamp updated on each use
- ✅ **Secure storage** - SHA-256 hashed in database
- ✅ **Admin control** - Admins can manage any company's keys

### Security Best Practices
1. **Never commit API keys** to version control
2. **Use environment variables** to store keys
3. **Rotate keys regularly** for production systems
4. **Use different keys** for different environments (prod, staging, dev)
5. **Deactivate unused keys** instead of deleting (for audit trail)
6. **Monitor last_used_at** to identify stale keys

### Authentication Flow
```
Request → Check Authorization Header
         ↓
    Has Bearer Token?
         ↓ Yes          ↓ No
    Validate Token   Check X-API-Key
         ↓                ↓
    Valid?            Valid?
         ↓ Yes            ↓ Yes
    Company Active?   Company Active?
         ↓ Yes            ↓ Yes
    Grant Access ← ← ← ← ←
```

## Key Business Rules

### Client Matching Logic
When reporting a client:
1. Search for existing client by email OR phone
2. If found → increment `reports_count`, add new report
3. If not found → create new client with `reports_count = 1`
4. Add phone to `phone_numbers` table if not already tracked
5. Recalculate trust score automatically

### Multiple Reports
- ✅ Same company CAN report same client multiple times
- ✅ Each report represents a separate incident
- ✅ `reports_count` = total incidents (not unique companies)
- ✅ Use `unique_companies_count` virtual attribute for unique company count

### Search Functionality
Search is **partial match** (LIKE %term%):
- Email: `fernando` matches `fernando@gmail.com`
- Phone: `333` matches `3331234567` and checks `phone_numbers` table
- Name: `García` matches `Fernando García López`
- Can combine multiple filters

### Update Permissions
A company can update a blacklisted client's info ONLY if:
- The company has reported that client at least once, OR
- The requester is an admin

### Delete Permissions
Only admins can delete blacklisted clients.

## Important Services

### `TrustScoreService`
Located: `app/Services/TrustScoreService.php`

**Methods:**
- `calculateTrustScore(BlacklistedClient $client): array` - Calculate score without saving
- `updateClientScore(BlacklistedClient $client): void` - Calculate and save to database
- `getRiskLevel(int $score): string` - Convert score to risk level
- `getRecommendation(int $score): string` - Get business recommendation

### `ApiKeyService`
Located: `app/Services/ApiKeyService.php`

**Methods:**
- `generate(): string` - Generate new API key (blh_ + 60 random chars)
- `hash(string $key): string` - Hash API key using SHA-256
- `getPrefix(string $key): string` - Get display prefix (first 16 chars + ...)
- `createForCompany(Company $company, string $name): array` - Create and store new API key
- `validateAndGetCompany(string $plainKey): ?Company` - Validate key and return company
- `revoke(ApiKey $apiKey): bool` - Delete an API key
- `toggle(ApiKey $apiKey): ApiKey` - Toggle active status

### `ApiResponse` Helper
Located: `app/Helpers/ApiResponse.php`

**Functions:**
- `api_success($data, $message, $status)` - Return success response
- `api_error($message, $status, $data)` - Return error response

## Observers

### `BlacklistReportObserver`
Located: `app/Observers/BlacklistReportObserver.php`

Automatically recalculates trust score when:
- Report is created
- Report is updated
- Report is deleted

Registered in: `AppServiceProvider`

## Commands

### Trust Score Recalculation
```bash
php artisan trust:recalculate
```
Recalculates trust scores for ALL blacklisted clients.

## Development Workflow

### Local Setup
```bash
# Clone repository
git clone https://github.com/yourusername/blacklisthub.io
cd blacklisthub.io

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database (update .env first)
php artisan migrate --seed

# Generate documentation
php artisan scribe:generate

# Start server
php artisan serve
```

### Testing Flow
1. Admin login: `POST /v1/admin/login`
2. Register company: `POST /v1/auth/register`
3. Admin activates company: `PUT /v1/admin/companies/{id}/activate`
4. Company login: `POST /v1/auth/login`
5. Get categories & fraud types
6. Report clients: `POST /v1/blacklist`
7. Search clients: `GET /v1/blacklist/search`
8. View stats: `GET /v1/stats`

## Common Scenarios

### Scenario 1: Client uses different emails but same phone
```json
POST /v1/blacklist/bulk
{
    "clients": [
        {"name": "John Doe", "email": "john1@gmail.com", "phone": "3331234567"},
        {"name": "John D", "email": "john2@hotmail.com", "phone": "3331234567"},
        {"name": "J. Doe", "email": "john3@yahoo.com", "phone": "3331234567"}
    ]
}
```
**Result:** 
- 1 client record (matched by phone)
- 3 reports from same company
- `reports_count = 3`
- `unique_companies_count = 1`
- Multiple emails visible in reports
- Phone `3331234567` in `phone_numbers` table

### Scenario 2: Same client reported by multiple companies
Company A reports → `reports_count = 1`, `unique_companies_count = 1`
Company B reports same client → `reports_count = 2`, `unique_companies_count = 2`
Company A reports again → `reports_count = 3`, `unique_companies_count = 2`

### Scenario 3: Fraud pattern detection
Client reported with:
- 5 different companies
- Total debt: $15,000
- 3 fraud types
- Reports in last 30 days

**Trust Score Calculation:**
- Start: 100
- Reports (5 × 15): -60 (capped)
- Debt > $10k: -20
- 3 fraud types: -15
- Recent activity: -10
- **Final Score: ~15** → CRITICAL 🔴

## Error Handling

All errors return standardized JSON format via `bootstrap/app.php`:
- Validation errors: 422
- Authentication errors: 401
- Authorization errors: 403
- Not found: 404
- Method not allowed: 405
- Server errors: 500

In debug mode, error responses include:
- Exception class
- File and line number
- Stack trace

## Security Considerations

### Password Hashing
- All passwords use Laravel's bcrypt (cost factor 10)
- Never store plain text passwords

### Token Security
- API tokens are SHA-256 hashed before storage
- Original 80-character token shown only once
- Tokens invalidated on logout

### Company Activation
- New companies start as `is_active = false`
- Admin must manually activate
- Inactive companies cannot login

### Input Validation
- All endpoints have Laravel validation rules
- Email format validation
- Numeric range validation
- Foreign key existence validation

### SQL Injection Prevention
- Eloquent ORM used throughout
- Raw queries only for specific index operations
- Parameterized statements when raw SQL needed

## Future Enhancements

### Version 1.1 (Implemented)
- ✅ API Key authentication (X-API-Key header)
- ✅ Multiple API keys per company
- ✅ Admin API key management

### Version 1.2 (Planned)
- Email notifications for new reports
- Webhook support
- Rate limiting per company/API key
- Export reports to CSV/PDF
- API key expiration dates (optional)
- API key scopes/permissions

### Version 2.0 (Future)
- Machine Learning fraud prediction
- Natural language search (ChatGPT-style)
- Real-time alerts
- GraphQL API
- Mobile SDK
- Multi-language support
- Two-factor authentication (2FA)

## File Structure
```
app/
├── Console/Commands/
│   └── RecalculateTrustScores.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── AdminApiKeyController.php
│   │   ├── AdminAuthController.php
│   │   ├── AdminCategoryController.php
│   │   ├── AdminCompanyController.php
│   │   ├── AdminFraudTypeController.php
│   │   ├── ApiKeyController.php
│   │   ├── AuthController.php
│   │   ├── BlacklistController.php        // Main controller (with international support)
│   │   ├── CategoryController.php
│   │   ├── FraudTypeController.php
│   │   ├── InternationalController.php    // Countries & currencies
│   │   └── StatsController.php            // Country-specific statistics
│   └── Middleware/
│       ├── AdminAuth.php
│       └── CompanyAuth.php                // Supports Bearer + X-API-Key
├── Models/
│   ├── Admin.php
│   ├── ApiKey.php
│   ├── BlacklistedClient.php              // Main model
│   ├── BlacklistReport.php
│   ├── Category.php
│   ├── Company.php
│   ├── FraudType.php
│   └── PhoneNumber.php
├── Observers/
│   └── BlacklistReportObserver.php
├── Services/
│   ├── ApiKeyService.php                  // API key generation/validation
│   ├── CurrencyService.php                // Currency conversion & formatting
│   ├── TaxIdValidationService.php         // Multi-country tax ID validation
│   └── TrustScoreService.php              // Trust score logic (multi-currency)
└── Helpers/
    ├── ApiResponse.php
    └── helpers.php

database/
├── migrations/
│   ├── xxxx_create_categories_table.php
│   ├── xxxx_create_companies_table.php
│   ├── xxxx_create_admins_table.php
│   ├── xxxx_create_fraud_types_table.php
│   ├── xxxx_create_blacklisted_clients_table.php
│   ├── xxxx_create_blacklist_reports_table.php
│   ├── xxxx_create_phone_numbers_table.php
│   ├── xxxx_create_api_keys_table.php
│   ├── xxxx_add_trust_score_fields_to_blacklisted_clients_table.php
│   ├── xxxx_add_missing_foreign_keys_to_blacklist_reports_table.php
│   └── xxxx_add_international_support_to_tables.php  // Multi-country & currency
└── seeders/
    ├── CategorySeeder.php
    ├── FraudTypeSeeder.php
    ├── AdminSeeder.php
    └── DatabaseSeeder.php

routes/
├── api.php                                // All API routes
└── web.php                                // Returns JSON 404 for all routes

config/
└── scribe.php                             // API documentation config

bootstrap/
└── app.php                                // Error handling, middleware

public/
└── docs/                                  // Generated API documentation
```

## Common Issues & Solutions

### Issue: "Duplicate entry" error when reporting
**Cause:** Old unique constraint still exists
**Solution:** 
```sql
ALTER TABLE blacklist_reports DROP INDEX blacklist_reports_blacklisted_client_id_company_id_unique;
```

### Issue: Trust score not updating
**Cause:** Observer not registered
**Solution:** Check `AppServiceProvider::boot()` has `BlacklistReport::observe(BlacklistReportObserver::class)`

### Issue: Company can't login after activation
**Cause:** Token might be corrupted
**Solution:** Company should logout and login again

### Issue: Search not finding by phone
**Cause:** Phone not in `phone_numbers` table
**Solution:** Search checks both `blacklisted_clients.phone` AND `phone_numbers.phone`

## Testing Examples

### Register and Report Flow
```bash
# 1. Register company
curl -X POST https://api.blacklisthub.io/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Co","email":"test@test.com","password":"password123"}'

# 2. Admin login
curl -X POST https://api.blacklisthub.io/v1/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@blacklist.com","password":"password123"}'

# 3. Activate company (use admin token)
curl -X PUT https://api.blacklisthub.io/v1/admin/companies/1/activate \
  -H "Authorization: Bearer ADMIN_TOKEN"

# 4. Company login
curl -X POST https://api.blacklisthub.io/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password123"}'

# 5. Report client
curl -X POST https://api.blacklisthub.io/v1/blacklist \
  -H "Authorization: Bearer COMPANY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "fraud_type_id": 1,
    "debt_amount": 5000
  }'

# 6. Search
curl -X GET "https://api.blacklisthub.io/v1/blacklist/search?phone=1234" \
  -H "Authorization: Bearer COMPANY_TOKEN"

# 7. Get detailed analysis
curl -X GET https://api.blacklisthub.io/v1/trust-analysis/1 \
  -H "Authorization: Bearer COMPANY_TOKEN"

# 8. Create API key
curl -X POST https://api.blacklisthub.io/v1/api-keys \
  -H "Authorization: Bearer COMPANY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Production Server"}'

# 9. Test with API key (using X-API-Key header)
curl -X GET https://api.blacklisthub.io/v1/blacklist \
  -H "X-API-Key: blh_YOUR_API_KEY_HERE"
```

## Environment Variables

### Required
```env
APP_NAME="BlacklistHub API"
APP_ENV=production
APP_KEY=                              # php artisan key:generate
APP_DEBUG=false
APP_URL=https://blacklisthub.io

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blacklist_api
DB_USERNAME=your_username
DB_PASSWORD=your_password

API_PREFIX=v1
```

### Optional
```env
LOG_CHANNEL=stack
LOG_LEVEL=error
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

## Deployment Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Change default admin password
- [ ] Configure proper database credentials
- [ ] Set up SSL certificates
- [ ] Configure DNS for api.blacklisthub.io
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Run seeders: `php artisan db:seed --force`
- [ ] Generate docs: `php artisan scribe:generate`
- [ ] Set proper file permissions (storage, bootstrap/cache)
- [ ] Configure cron for scheduled tasks (if any)
- [ ] Set up backups
- [ ] Configure rate limiting
- [ ] Set up monitoring/logging

## Support & Documentation

- **API Docs:** https://api.blacklisthub.io/docs
- **GitHub:** https://github.com/yourusername/blacklisthub-api
- **Issues:** https://github.com/yourusername/blacklisthub-api/issues
- **License:** MIT

---

**Last Updated:** December 8, 2025
**Current Version:** 2.0.0 (International Support)
**Maintained By:** BlacklistHub Team

## Quick Reference - API Keys

### Create API Key
```bash
curl -X POST http://localhost:8000/v1/api-keys \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"My API Key"}'
```

### Use API Key
```bash
curl -X GET http://localhost:8000/v1/blacklist \
  -H "X-API-Key: blh_YOUR_API_KEY"
```

### List API Keys
```bash
curl -X GET http://localhost:8000/v1/api-keys \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN"
```

### Deactivate API Key
```bash
curl -X PUT http://localhost:8000/v1/api-keys/1 \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"is_active":false}'
```

### Delete API Key
```bash
curl -X DELETE http://localhost:8000/v1/api-keys/1 \
  -H "Authorization: Bearer YOUR_BEARER_TOKEN"
```