# Contributing to BlacklistHub API

First off, thank you for considering contributing to BlacklistHub! It's people like you that make this a great tool for fraud prevention.

## Code of Conduct

This project and everyone participating in it is governed by respect and professionalism. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates.

**When submitting a bug report, include:**
- A clear and descriptive title
- Steps to reproduce the behavior
- Expected behavior
- Actual behavior
- Screenshots if applicable
- Your environment (OS, PHP version, Laravel version)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues.

**Include:**
- A clear use case
- Why this enhancement would be useful
- Possible implementation approach

### Pull Requests

1. Fork the repo
2. Create your feature branch from `main`
3. Make your changes
4. Add tests if applicable
5. Update documentation
6. Commit with clear messages
7. Push to your fork
8. Submit a pull request

### Coding Standards

- Follow PSR-12
- Use meaningful variable names
- Add PHPDoc comments
- Keep methods focused and small
- Write tests for new features

## Development Process

1. Install dependencies: `composer install`
2. Copy `.env.example` to `.env`
3. Set up your database
4. Run migrations: `php artisan migrate --seed`
5. Make your changes
6. Run tests: `php artisan test`
7. Generate docs: `php artisan scribe:generate`

## Questions?

Feel free to open a discussion on GitHub!
```

---

### 18.3 Crear `LICENSE`
```
MIT License

Copyright (c) 2024 BlacklistHub

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.