<?php

namespace App\Helpers;

use App\Models\AddonSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailHelper
{
    /**
     * Get active email configuration from addon_settings
     *
     * @return AddonSetting|null
     */
    public static function getActiveEmailConfig()
    {
        return AddonSetting::where('settings_type', 'email_config')
            ->where('is_active', true)
            ->where('mode', config('app.env') === 'production' ? 'live' : 'test')
            ->first();
    }

    /**
     * Configure Laravel mail settings dynamically from addon_settings
     *
     * @param AddonSetting|null $emailConfig
     * @return bool
     */
    public static function configureMailFromAddonSettings(?AddonSetting $emailConfig = null): bool
    {
        try {
            $emailConfig = $emailConfig ?? self::getActiveEmailConfig();
            
            if (!$emailConfig) {
                Log::warning('No active email configuration found in addon_settings');
                return false;
            }

            $values = $emailConfig->getValues();
            $gateway = $values['gateway'] ?? 'smtp';
            $status = $values['status'] ?? '0';

            // Check if email gateway is active
            if ($status == '0' || $status === 0 || $status === false) {
                Log::info("Email Gateway {$gateway} is not active");
                return false;
            }

            // Configure mail based on gateway type
            switch ($gateway) {
                case 'smtp':
                    return self::configureSmtp($values);
                
                case 'mailgun':
                    return self::configureMailgun($values);
                
                case 'ses':
                case 'aws_ses':
                    return self::configureSes($values);
                
                case 'postmark':
                    return self::configurePostmark($values);
                
                case 'sendgrid':
                    return self::configureSendgrid($values);
                
                case 'resend':
                    return self::configureResend($values);
                
                default:
                    Log::warning("Unsupported email gateway: {$gateway}");
                    return false;
            }
        } catch (\Exception $e) {
            Log::error("Failed to configure email from addon_settings: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure SMTP settings
     */
    private static function configureSmtp(array $values): bool
    {
        try {
            $host = $values['host'] ?? null;
            $port = $values['port'] ?? 587;
            $username = $values['username'] ?? null;
            $password = $values['password'] ?? null;
            $encryption = $values['encryption'] ?? 'tls';
            $fromAddress = $values['from_address'] ?? null;
            $fromName = $values['from_name'] ?? null;

            if (!$host || !$username || !$password) {
                Log::warning("SMTP configuration incomplete: missing host, username, or password");
                return false;
            }

            // Dynamically configure mail settings
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.encryption', $encryption);

            if ($fromAddress) {
                Config::set('mail.from.address', $fromAddress);
            }
            if ($fromName) {
                Config::set('mail.from.name', $fromName);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("SMTP configuration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure Mailgun settings
     */
    private static function configureMailgun(array $values): bool
    {
        try {
            $domain = $values['domain'] ?? null;
            $secret = $values['secret'] ?? null;

            if (!$domain || !$secret) {
                return false;
            }

            Config::set('mail.default', 'mailgun');
            Config::set('services.mailgun.domain', $domain);
            Config::set('services.mailgun.secret', $secret);

            return true;
        } catch (\Exception $e) {
            Log::error("Mailgun configuration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure AWS SES settings
     */
    private static function configureSes(array $values): bool
    {
        try {
            $key = $values['key'] ?? null;
            $secret = $values['secret'] ?? null;
            $region = $values['region'] ?? 'us-east-1';

            if (!$key || !$secret) {
                return false;
            }

            Config::set('mail.default', 'ses');
            Config::set('services.ses.key', $key);
            Config::set('services.ses.secret', $secret);
            Config::set('services.ses.region', $region);

            return true;
        } catch (\Exception $e) {
            Log::error("SES configuration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure Postmark settings
     */
    private static function configurePostmark(array $values): bool
    {
        try {
            $token = $values['token'] ?? null;

            if (!$token) {
                return false;
            }

            Config::set('mail.default', 'postmark');
            Config::set('services.postmark.token', $token);

            return true;
        } catch (\Exception $e) {
            Log::error("Postmark configuration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure SendGrid settings
     */
    private static function configureSendgrid(array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;

            if (!$apiKey) {
                return false;
            }

            // SendGrid uses SMTP with API key
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', 'smtp.sendgrid.net');
            Config::set('mail.mailers.smtp.port', 587);
            Config::set('mail.mailers.smtp.username', 'apikey');
            Config::set('mail.mailers.smtp.password', $apiKey);
            Config::set('mail.mailers.smtp.encryption', 'tls');

            return true;
        } catch (\Exception $e) {
            Log::error("SendGrid configuration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure Resend settings
     */
    private static function configureResend(array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;

            if (!$apiKey) {
                return false;
            }

            Config::set('mail.default', 'resend');
            Config::set('services.resend.key', $apiKey);

            return true;
        } catch (\Exception $e) {
            Log::error("Resend configuration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email dynamically using addon_settings configuration
     *
     * @param string $to
     * @param \Illuminate\Mail\Mailable $mailable
     * @param string|null $emailType Type of email (otp, invoice, marketing, etc.)
     * @return bool
     */
    public static function sendEmail(string $to, $mailable, ?string $emailType = null): bool
    {
        try {
            // Configure mail settings from addon_settings
            if (!self::configureMailFromAddonSettings()) {
                Log::warning("Failed to configure email from addon_settings, using default config");
            }

            // Send email
            Mail::to($to)->send($mailable);
            
            Log::info("Email sent successfully to {$to} (Type: {$emailType})");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email to {$to}: " . $e->getMessage());
            return false;
        }
    }
}

