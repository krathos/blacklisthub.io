# BlacklistHub API 🛡️

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge" alt="License">
</p>

## 📋 About

**BlacklistHub** is a collaborative fraud prevention API that allows companies across different industries to report and search for problematic clients in a shared database. 

Think of it as a **shared blacklist network** where businesses can protect each other by reporting clients who:
- Failed to pay for services
- Committed chargeback fraud
- Provided fake information
- Engaged in identity theft
- And other fraudulent activities

### 🎯 Use Cases

- **Logistics & Shipping** - Track clients with unpaid shipments
- **E-commerce** - Identify serial refund abusers
- **Financial Services** - Flag clients with payment history issues
- **Professional Services** - Report non-paying customers
- **Real Estate** - Share information about problematic tenants
- **And many more...**

## ✨ Features

- 🔐 **Dual Authentication** - Bearer Token + API Keys (X-API-Key header)
- 🎯 **Trust Score System** - Algorithmic risk scoring (0-100) with fraud pattern detection
- 📊 **Multiple Categories** - Organize reports by business type
- 🏷️ **Fraud Classification** - 11 predefined fraud types
- 🔍 **Advanced Search** - Search by email, phone, name, tax ID, etc.
- 📱 **Phone Tracking** - Track multiple phone numbers per client
- 🔑 **API Key Management** - Create, manage, and revoke API keys
- 📈 **Statistics & Analytics** - View global and company-specific stats
- 💾 **Bulk Operations** - Upload multiple reports at once
- 📚 **Auto-generated Docs** - Interactive API documentation with Scribe
- 🌐 **API-Only** - Pure REST API (no HTML views)

## 🚀 Quick Start

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Node.js & NPM (optional, for frontend)

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/krathos/blacklisthub.io
cd blacklisthub.io
```

> **Official Website:** [blacklisthub.io](https://blacklisthub.io)

2. **Install dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Update `.env` with your database credentials**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blacklist_api
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Run migrations and seeders**
```bash
php artisan migrate --seed
```

6. **Generate API documentation**
```bash
php artisan scribe:generate
```

7. **Start the server**
```bash
php artisan serve
```

8. **Access the API**
- API Base URL: `http://localhost:8000/v1`
- Documentation: `http://localhost:8000/docs`
- Root path (`/`): Returns JSON 404 (API-only application)

### 🔑 Default Admin Credentials
```
Email: admin@blacklist.com
Password: password123
```

**⚠️ Change these credentials in production!**

## 📖 API Documentation

Once installed, visit `/docs` for complete API documentation with interactive examples.

### Quick API Overview

#### Authentication
```
POST   /v1/auth/register          - Register new company
POST   /v1/auth/login             - Company login (returns Bearer token)
POST   /v1/auth/logout            - Company logout
POST   /v1/admin/login            - Admin login
```

#### Blacklist Operations
```
POST   /v1/blacklist              - Report a client
POST   /v1/blacklist/bulk         - Report multiple clients
GET    /v1/blacklist              - List all blacklisted clients
GET    /v1/blacklist/search       - Search blacklisted clients
GET    /v1/blacklist/{id}         - Get client details
PUT    /v1/blacklist/{id}         - Update client info
GET    /v1/trust-analysis/{id}    - Get trust score analysis
```

#### API Keys Management
```
GET    /v1/api-keys               - List your API keys
POST   /v1/api-keys               - Create new API key
PUT    /v1/api-keys/{id}          - Update API key
DELETE /v1/api-keys/{id}          - Delete API key
```

#### Admin Endpoints
```
GET    /v1/admin/companies                - List all companies
PUT    /v1/admin/companies/{id}/activate  - Activate company
GET    /v1/admin/companies/{id}/api-keys  - List company's API keys
POST   /v1/admin/companies/{id}/api-keys  - Create API key for company
```

### Example: Report a Client

**Using Bearer Token:**
```bash
curl -X POST http://localhost:8000/v1/blacklist \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "category_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "fraud_type_id": 1,
    "debt_amount": 5000.00,
    "additional_info": "Failed to pay for 3 shipments"
  }'
```

**Using API Key:**
```bash
curl -X POST http://localhost:8000/v1/blacklist \
  -H "Content-Type: application/json" \
  -H "X-API-Key: blh_YOUR_API_KEY_HERE" \
  -d '{
    "category_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "fraud_type_id": 1,
    "debt_amount": 5000.00
  }'
```

### Example: Create API Key
```bash
# 1. Login to get bearer token
curl -X POST http://localhost:8000/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"company@example.com","password":"password"}'

# 2. Create API key
curl -X POST http://localhost:8000/v1/api-keys \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Production Server"}'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "api_key": {...},
    "plain_key": "blh_AYpbX24Ypo32HlIMocKVb1OCPxdvKBtfiSMg304Ffceb40c2a1b2c3",
    "warning": "Store this API key securely. It will not be shown again."
  }
}
```

## 🏗️ Architecture

### Database Schema

**Main Tables:**
- `companies` - Registered companies
- `admins` - System administrators
- `api_keys` - Company API keys for authentication
- `categories` - Business categories (10 predefined)
- `fraud_types` - Types of fraud (11 predefined)
- `blacklisted_clients` - Reported clients with trust scores
- `blacklist_reports` - Individual company reports
- `phone_numbers` - Phone number tracking across reports

### Key Features

**Trust Score Algorithm:**
- Starting score: 100
- Deductions based on: number of reports, debt amount, fraud types, recent activity
- Risk levels: LOW (🟢), MEDIUM (🟡), HIGH (🟠), CRITICAL (🔴)

**Multiple Authentication:**
- Bearer tokens from login (session-based)
- API Keys with X-API-Key header (permanent, for server-to-server)
- Admin approval required for new companies

### Tech Stack

- **Framework:** Laravel 11.x
- **Database:** MySQL 8.0+
- **Documentation:** Scribe (auto-generated)
- **Authentication:** Bearer Token + API Keys (X-API-Key header)
- **API Version:** v1
- **Response Format:** JSON-only (no HTML views)

## 🤝 Contributing

We welcome contributions from the community! Here's how you can help:

### Ways to Contribute

1. 🐛 **Report Bugs** - Open an issue describing the bug
2. 💡 **Suggest Features** - Share your ideas in issues
3. 📝 **Improve Documentation** - Fix typos, add examples
4. 🔧 **Submit Pull Requests** - Fix bugs or add features

### Contribution Guidelines

1. **Fork the repository**
2. **Create a feature branch** (`git checkout -b feature/AmazingFeature`)
3. **Commit your changes** (`git commit -m 'Add some AmazingFeature'`)
4. **Push to the branch** (`git push origin feature/AmazingFeature`)
5. **Open a Pull Request**

### Coding Standards

- Follow PSR-12 coding standards
- Write meaningful commit messages
- Add PHPDoc comments for new methods
- Include tests for new features
- Update documentation when needed

### Development Setup
```bash
# Install dev dependencies
composer install

# Run tests
php artisan test

# Check code style
./vendor/bin/phpcs

# Fix code style
./vendor/bin/phpcbfixer
```

## 🛣️ Roadmap

### Version 1.1 (Current) ✅
- ✅ Basic CRUD operations
- ✅ Dual authentication (Bearer + API Keys)
- ✅ Trust Score system with risk levels
- ✅ Categories and fraud types
- ✅ Advanced search functionality
- ✅ Statistics and analytics
- ✅ API Keys management
- ✅ Auto-generated documentation

### Version 1.2 (Planned)
- [ ] Email notifications for new reports
- [ ] Webhook support
- [ ] Rate limiting per company/API key
- [ ] API key expiration dates
- [ ] Export reports to CSV/PDF
- [ ] API key scopes/permissions

### Version 2.0 (Future)
- [ ] Enhanced ML fraud detection
- [ ] Real-time alerts and notifications
- [ ] GraphQL API support
- [ ] Mobile SDK (iOS/Android)
- [ ] Multi-language support
- [ ] Two-factor authentication (2FA)

## 📊 Statistics

Track global fraud trends and protect your business:

- Number of reported clients
- Most common fraud types
- Geographic distribution
- Category breakdown
- Your company's contribution

## 🔒 Security

- **Password Hashing:** bcrypt with cost factor 10
- **Token Security:** SHA-256 hashed API tokens and keys
- **API Keys:** Permanent keys stored hashed, shown only once
- **Admin Approval:** Required for new company activation
- **Role-based Access:** Companies and admins have different permissions
- **Input Validation:** Strict validation on all endpoints
- **SQL Injection:** Protected via Eloquent ORM
- **JSON-Only:** No HTML views eliminates XSS risks

### Reporting Security Issues

Please email security issues to: **krathos@gmail.com**

Do not open public issues for security vulnerabilities.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com)
- Documentation powered by [Scribe](https://scribe.knuckles.wtf/)
- Inspired by the need for collaborative fraud prevention

## 💬 Support & Contact

- 📚 **Full Documentation:** See [CLAUDE.md](CLAUDE.md) for complete technical docs
- 🐛 **Issues:** [GitHub Issues](https://github.com/yourusername/blacklisthub-api/issues)
- 💬 **Discussions:** [GitHub Discussions](https://github.com/yourusername/blacklisthub-api/discussions)
- 📖 **API Docs:** Available at `/docs` after installation
- 🌐 **Official Website:** [blacklisthub.io](https://blacklisthub.io)

### Creator & Maintainer

**Michel Solis**
- 📧 Email: krathos@gmail.com
- 💼 Creator of BlacklistHub API
- 💬 Questions or comments? Feel free to reach out!

## 🌟 Star History

If this project helps you, please consider giving it a ⭐️!

---

<p align="center">
  <strong>Created with ❤️ by Michel Solis</strong><br>
  <em>Protecting businesses, one report at a time.</em>
</p>

<p align="center">
  <a href="https://blacklisthub.io">blacklisthub.io</a> •
  <a href="mailto:krathos@gmail.com">krathos@gmail.com</a>
</p>