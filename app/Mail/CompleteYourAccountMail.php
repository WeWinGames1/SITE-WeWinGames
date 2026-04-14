<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompleteYourAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Complete Your WeWinGames Account',
            from: new Address(config('mail.from.address'), config('mail.from.name')),
        );
    }

    public function content(): Content
    {
        $completionUrl = url('/complete-registration').'?token='.$this->user->completion_token;

        return new Content(
            htmlString: $this->buildHtmlContent($completionUrl),
        );
    }

    protected function buildHtmlContent(string $completionUrl): string
    {
        $name = e($this->user->name);
        $expiryHours = 24;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Account</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background-color: #1a1a2e; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">WeWinGames</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 20px; color: #1a1a2e; font-size: 24px; font-weight: 600;">Welcome, {$name}!</h2>

                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.6;">
                                Your payment was successful! You now have full access to all your premium picks.
                            </p>

                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.6;">
                                To complete your account setup and create a password, click the button below:
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{$completionUrl}" style="display: inline-block; padding: 16px 40px; background-color: #4f46e5; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 16px; border-radius: 8px;">
                                            Complete Your Account
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 20px; color: #666666; font-size: 14px; line-height: 1.6;">
                                This link expires in {$expiryHours} hours. If you don't complete your account setup, you can still access your account using the "Forgot Password" option.
                            </p>

                            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eeeeee;">

                            <p style="margin: 0 0 10px; color: #666666; font-size: 14px; line-height: 1.6;">
                                <strong>What's next?</strong>
                            </p>
                            <ul style="margin: 0 0 20px; padding-left: 20px; color: #666666; font-size: 14px; line-height: 1.8;">
                                <li>Set your password to secure your account</li>
                                <li>Optionally connect your Discord for alerts</li>
                                <li>Start accessing your premium picks</li>
                            </ul>

                            <p style="margin: 0; color: #999999; font-size: 12px;">
                                If the button doesn't work, copy and paste this link into your browser:<br>
                                <a href="{$completionUrl}" style="color: #4f46e5; word-break: break-all;">{$completionUrl}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px 40px; background-color: #f9f9f9; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; color: #999999; font-size: 12px; text-align: center;">
                                &copy; WeWinGames. All rights reserved.<br>
                                Questions? Contact us at support@wewingames.com
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
