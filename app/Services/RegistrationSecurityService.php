<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\CloudflareService;

class RegistrationSecurityService
{
    protected CloudflareService $cloudflare;
    
    public function __construct(CloudflareService $cloudflare)
    {
        $this->cloudflare = $cloudflare;
    }
    
    /**
     * Check if registration should be allowed
     */
    public function canRegister(Request $request): array
    {
        // Get real IP from Cloudflare
        $realIp = $this->cloudflare->getRealIp($request);
        
        // Check Cloudflare security signals first
        if ($this->cloudflare->isSuspiciousRequest($request)) {
            return [
                'allowed' => false,
                'checks' => ['cloudflare' => false],
                'reason' => 'Your request has been flagged as suspicious. Please try again later.',
            ];
        }
        
        // Check if country is blocked
        if ($this->cloudflare->isBlockedCountry($request)) {
            return [
                'allowed' => false,
                'checks' => ['geo_location' => false],
                'reason' => 'Registration is not available from your location.',
            ];
        }
        
        $checks = [
            'ip_reputation' => $this->checkIpReputation($realIp),
            'email_domain' => $this->checkEmailDomain($request->input('email')),
            'velocity' => $this->checkRegistrationVelocity(),
            'turnstile' => $this->checkTurnstile($request),
        ];
        
        $allowed = !in_array(false, $checks, true);
        
        return [
            'allowed' => $allowed,
            'checks' => $checks,
            'reason' => $allowed ? null : $this->getBlockReason($checks),
        ];
    }
    
    /**
     * Log registration attempt
     */
    public function logRegistrationAttempt(Request $request, bool $successful): void
    {
        DB::table('registration_attempts')->insert([
            'ip_address' => $request->ip(),
            'email' => $request->input('email'),
            'user_agent' => $request->userAgent(),
            'fingerprint' => $this->generateFingerprint($request),
            'successful' => $successful,
            'checks_performed' => json_encode($this->getSecurityChecks($request)),
            'created_at' => now(),
        ]);
    }
    
    /**
     * Check IP reputation
     */
    private function checkIpReputation(string $ip): bool
    {
        // Check local blacklist
        $blacklistedIps = Cache::get('blacklisted_ips', []);
        if (in_array($ip, $blacklistedIps)) {
            return false;
        }
        
        // Check if IP has too many failed attempts
        $failedAttempts = DB::table('registration_attempts')
            ->where('ip_address', $ip)
            ->where('successful', false)
            ->where('created_at', '>', now()->subHours(24))
            ->count();
            
        if ($failedAttempts > 5) {
            return false;
        }
        
        // Optional: Check against external IP reputation service
        // This is commented out but can be enabled with proper API key
        /*
        try {
            $response = Http::timeout(2)->get("https://api.abuseipdb.com/api/v2/check", [
                'ipAddress' => $ip,
                'maxAgeInDays' => 90,
            ], [
                'Key' => env('ABUSEIPDB_API_KEY'),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['abuseConfidenceScore'] < 50;
            }
        } catch (\Exception $e) {
            Log::error('IP reputation check failed', ['error' => $e->getMessage()]);
        }
        */
        
        return true;
    }
    
    /**
     * Check email domain
     */
    private function checkEmailDomain(?string $email): bool
    {
        if (!$email) {
            return false;
        }
        
        $domain = substr(strrchr($email, "@"), 1);
        
        // Check MX records
        if (!checkdnsrr($domain, 'MX')) {
            return false;
        }
        
        // Check against known spam domains
        $spamDomains = Cache::remember('spam_email_domains', 86400, function () {
            return DB::table('spam_email_domains')->pluck('domain')->toArray();
        });
        
        return !in_array($domain, $spamDomains);
    }
    
    /**
     * Check registration velocity
     */
    private function checkRegistrationVelocity(): bool
    {
        // Check overall registration rate
        $recentRegistrations = DB::table('users')
            ->where('created_at', '>', now()->subMinutes(10))
            ->count();
            
        // If more than 10 registrations in 10 minutes, suspicious
        if ($recentRegistrations > 10) {
            Log::warning('High registration velocity detected', [
                'count' => $recentRegistrations,
                'timeframe' => '10 minutes'
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check Turnstile verification
     */
    private function checkTurnstile(Request $request): bool
    {
        if (!config('services.turnstile.enabled')) {
            return true;
        }
        
        $token = $request->input('cf-turnstile-response');
        if (!$token) {
            return false;
        }
        
        $result = $this->cloudflare->verifyTurnstile($token, $this->cloudflare->getRealIp($request));
        
        return $result['success'];
    }
    
    /**
     * Generate device fingerprint
     */
    private function generateFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept'),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->header('DNT'),
            $request->getHttpHost(),
            $request->header('Sec-CH-UA'),
            $request->header('Sec-CH-UA-Mobile'),
            $request->header('Sec-CH-UA-Platform'),
        ];
        
        return hash('sha256', implode('|', array_filter($components)));
    }
    
    /**
     * Get security checks performed
     */
    private function getSecurityChecks(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'referrer' => $request->header('Referer'),
            'accept_language' => $request->header('Accept-Language'),
            'timezone_offset' => $request->input('timezone_offset'),
            'screen_resolution' => $request->input('screen_resolution'),
            'timestamp' => time(),
        ];
    }
    
    /**
     * Get block reason from failed checks
     */
    private function getBlockReason(array $checks): string
    {
        if (isset($checks['cloudflare']) && !$checks['cloudflare']) {
            return 'Your request has been flagged as suspicious. Please try again later.';
        }
        
        if (isset($checks['turnstile']) && !$checks['turnstile']) {
            return 'Please complete the security verification.';
        }
        
        if (!$checks['ip_reputation']) {
            return 'Your IP address has been flagged for suspicious activity.';
        }
        
        if (!$checks['email_domain']) {
            return 'Please use a valid email address from a recognized email provider.';
        }
        
        if (!$checks['velocity']) {
            return 'Registration is temporarily unavailable due to high demand. Please try again later.';
        }
        
        if (isset($checks['geo_location']) && !$checks['geo_location']) {
            return 'Registration is not available from your location.';
        }
        
        return 'Registration is temporarily unavailable.';
    }
}