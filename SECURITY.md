# Security Policy

## Supported Versions

We actively support the following versions of the Laravel AutoGen Package Suite with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | ✅ Yes            |
| 0.x     | ❌ No (Development) |

## Reporting a Vulnerability

The Laravel AutoGen team takes security bugs seriously. We appreciate your efforts to responsibly disclose your findings, and will make every effort to acknowledge your contributions.

### How to Report Security Issues

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to: **security@autogen.dev**

You should receive a response within 48 hours. If for some reason you do not, please follow up via email to ensure we received your original message.

### Reporting Format

Please include the requested information listed below (as much as you can provide) to help us better understand the nature and scope of the possible issue:

- **Type of issue** (e.g., buffer overflow, SQL injection, cross-site scripting, etc.)
- **Full paths of source file(s)** related to the manifestation of the issue
- **The location of the affected source code** (tag/branch/commit or direct URL)
- **Any special configuration** required to reproduce the issue
- **Step-by-step instructions** to reproduce the issue
- **Proof-of-concept or exploit code** (if possible)
- **Impact of the issue**, including how an attacker might exploit the issue

This information will help us triage your report more quickly.

### What to Expect

After you submit a report, here's what happens:

1. **Acknowledgment**: We'll acknowledge receipt of your report within 48 hours
2. **Investigation**: We'll investigate and validate the vulnerability
3. **Resolution**: We'll work on a fix and determine the release timeline
4. **Coordination**: We'll coordinate with you on disclosure timing
5. **Credit**: We'll publicly credit you for the discovery (if desired)

## Security Best Practices

### For Users

When using AutoGen in your applications:

1. **Keep Updated**: Always use the latest version of AutoGen
2. **Review Generated Code**: Always review generated code before using in production
3. **Validate Inputs**: Ensure proper validation on all generated forms and APIs
4. **Database Security**: Use proper database credentials and connection security
5. **Environment Variables**: Never commit sensitive configuration to version control
6. **AI API Keys**: Protect your AI provider API keys and use appropriate rate limiting

### For Contributors

When contributing to AutoGen:

1. **Input Validation**: Always validate and sanitize user inputs
2. **SQL Injection Prevention**: Use parameterized queries and Laravel's query builder
3. **XSS Prevention**: Escape output and use Laravel's Blade templating safely
4. **CSRF Protection**: Ensure CSRF protection on all generated forms
5. **Authorization**: Implement proper authorization checks in generated code
6. **File Permissions**: Set appropriate file permissions on generated files
7. **Error Handling**: Don't expose sensitive information in error messages

## Common Security Considerations

### Generated Code Security

AutoGen generates code that follows Laravel security best practices:

- **Mass Assignment Protection**: Generated models include proper `$fillable` attributes
- **Validation**: Generated form requests include comprehensive validation rules
- **Authorization**: Generated controllers include policy checks when enabled
- **CSRF Protection**: Generated forms include CSRF tokens
- **SQL Injection Prevention**: Uses Eloquent ORM and query builder
- **XSS Prevention**: Blade templates escape output by default

### AI Provider Security

When using AI features:

- **API Key Security**: Store API keys securely in environment variables
- **Data Privacy**: Be aware that AI providers may log requests
- **Rate Limiting**: Implement appropriate rate limiting for AI requests
- **Sensitive Data**: Avoid sending sensitive data to AI providers
- **Audit Logs**: Keep logs of AI-generated code for security review

### Database Security

When working with databases:

- **Connection Security**: Use secure database connections (SSL/TLS)
- **Credential Management**: Store database credentials securely
- **Schema Introspection**: Limit database permissions for introspection
- **Legacy Systems**: Exercise extra caution with legacy database connections

## Vulnerability Disclosure Timeline

We strive to fix security vulnerabilities quickly:

- **Critical vulnerabilities**: 24-48 hours
- **High severity**: 1-2 weeks  
- **Medium severity**: 2-4 weeks
- **Low severity**: 4-8 weeks

## Security Updates

Security updates are published:

1. **Through Composer**: `composer update autogen/laravel-autogen`
2. **Release Notes**: Detailed in GitHub releases
3. **Security Advisory**: Posted on GitHub Security tab
4. **Mailing List**: Sent to security@autogen.dev subscribers

## Hall of Fame

We recognize security researchers who help improve AutoGen:

*[This section will be updated as we receive security reports]*

## Contact

- **Security Email**: security@autogen.dev
- **General Support**: support@autogen.dev
- **GitHub Issues**: For non-security bugs only

## Additional Resources

- [Laravel Security Documentation](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://phpsec.org/)

---

Thank you for helping keep Laravel AutoGen and our users safe!