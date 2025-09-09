<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class MailConfigurationService
{
    /**
     * Get available mail providers with their configurations
     */
    public static function getMailProviders(): array
    {
        return [
            'mailtrap' => [
                'name' => 'Mailtrap (Testing)',
                'host' => 'sandbox.smtp.mailtrap.io',
                'port' => 2525,
                'encryption' => null,
                'description' => 'Perfect for development and testing',
            ],
            'gmail' => [
                'name' => 'Gmail',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Requires app password, not regular password',
            ],
            'outlook' => [
                'name' => 'Outlook/Hotmail',
                'host' => 'smtp-mail.outlook.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Microsoft email service',
            ],
            'yahoo' => [
                'name' => 'Yahoo Mail',
                'host' => 'smtp.mail.yahoo.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Yahoo email service',
            ],
            'sendgrid' => [
                'name' => 'SendGrid',
                'host' => 'smtp.sendgrid.net',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Professional email service',
            ],
            'mailgun' => [
                'name' => 'Mailgun',
                'host' => 'smtp.mailgun.org',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Professional email service',
            ],
            'custom' => [
                'name' => 'Custom SMTP',
                'host' => '',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Configure your own SMTP server',
            ],
        ];
    }

    /**
     * Configure mail settings for a specific provider
     */
    public static function configureProvider(string $provider, array $credentials): bool
    {
        $providers = self::getMailProviders();

        if (!isset($providers[$provider])) {
            return false;
        }

        $config = $providers[$provider];

        // Update .env file
        self::updateEnvFile([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => $credentials['host'] ?? $config['host'],
            'MAIL_PORT' => $credentials['port'] ?? $config['port'],
            'MAIL_USERNAME' => $credentials['username'] ?? '',
            'MAIL_PASSWORD' => $credentials['password'] ?? '',
            'MAIL_ENCRYPTION' => $credentials['encryption'] ?? $config['encryption'],
            'MAIL_FROM_ADDRESS' => $credentials['from_address'] ?? 'noreply@cadebeckhrms.com',
            'MAIL_FROM_NAME' => $credentials['from_name'] ?? 'CADEBECK HRMS',
        ]);

        // Clear config cache
        Artisan::call('config:clear');

        return true;
    }

    /**
     * Test mail configuration
     */
    public static function testConfiguration(): array
    {
        try {
            // Send a test email
            \Illuminate\Support\Facades\Mail::raw('Test email from CADEBECK HRMS', function ($message) {
                $message->to(config('mail.from.address'))
                        ->subject('Mail Configuration Test - ' . now()->format('Y-m-d H:i:s'));
            });

            return [
                'success' => true,
                'message' => 'Test email sent successfully!',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get current mail configuration status
     */
    public static function getConfigurationStatus(): array
    {
        return [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username') ? 'Configured' : 'Not Set',
            'password' => config('mail.mailers.smtp.password') ? 'Configured' : 'Not Set',
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'encryption' => config('mail.mailers.smtp.encryption'),
        ];
    }

    /**
     * Update .env file with new values
     */
    private static function updateEnvFile(array $values): void
    {
        $envFile = base_path('.env');

        if (!file_exists($envFile)) {
            return;
        }

        $envContent = file_get_contents($envFile);

        foreach ($values as $key => $value) {
            // Escape special characters in value
            $escapedValue = str_replace(['"', "'"], ['\"', "\'"], $value);

            // Update or add the environment variable
            if (preg_match("/^{$key}=.*$/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*$/m", "{$key}=\"{$escapedValue}\"", $envContent);
            } else {
                $envContent .= "\n{$key}=\"{$escapedValue}\"";
            }
        }

        file_put_contents($envFile, $envContent);
    }

    /**
     * Get mail configuration tips for different providers
     */
    public static function getProviderTips(string $provider): array
    {
        $tips = [
            'gmail' => [
                'Use App Password instead of regular password',
                'Enable 2-Factor Authentication first',
                'Generate App Password from Google Account settings',
                'App Password is 16 characters long',
            ],
            'outlook' => [
                'Use your full email address as username',
                'May need to enable SMTP in account settings',
                'Check spam folder for test emails',
            ],
            'yahoo' => [
                'May need to generate an app password',
                'Check account security settings',
                'Allow less secure apps if available',
            ],
            'mailtrap' => [
                'Perfect for development and testing',
                'Emails are captured in Mailtrap dashboard',
                'No real emails are sent',
                'Great for debugging email templates',
            ],
            'sendgrid' => [
                'Requires API key as password',
                'Username is usually "apikey"',
                'Sign up at sendgrid.com',
                'Configure domain authentication for better deliverability',
            ],
            'mailgun' => [
                'Requires SMTP credentials from Mailgun dashboard',
                'Configure domain verification',
                'Check spam folder for test emails',
            ],
        ];

        return $tips[$provider] ?? ['No specific tips available for this provider.'];
    }
}
