<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\ResetPassword; // ✅ ADD THIS

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // if(PHP_OS != 'WINNT'){
        //     app()->usePublicPath(base_path('public_html'));
        // }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    // 🔹 FIX: Tell Laravel how to generate the signed verification URL
    VerifyEmail::createUrlUsing(function ($notifiable) {
        return URL::temporarySignedRoute(
            'auth.email-verify', // ← Your custom API route name
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    });

    // 🔹 Your existing email body customization
    VerifyEmail::toMailUsing(function ($notifiable, $url) {
        $frontendUrl = rtrim(config('app.frontend_url'), '/') . '/email-verify?verify_url=' . urlencode($url);
        
        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->line('Click the button below to verify your email address.')
            ->action('Verify Email', $frontendUrl) // ← Now $url is correctly generated!
            ->line('If you did not create an account, no further action is required.');
    });
    ResetPassword::createUrlUsing(function ($user, string $token) {
    return config('app.frontend_url')
        ."/reset-password?token=".$token
        ."&email=".$user->email;
    });
}
}
