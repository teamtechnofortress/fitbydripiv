<?php

namespace App\Providers;

use App\Models\Faq;
use App\Models\DocumentType;
use App\Models\DrNetwork;
use App\Models\DrNetworkWebhookEvent;
use App\Models\NetworkDocumentRule;
use App\Models\NetworkFlowDefinition;
use App\Models\NetworkIntakeQuestion;
use App\Models\NetworkIntakeQuestionSet;
use App\Models\NetworkProductMapping;
use App\Models\NetworkStateMapping;
use App\Models\Order;
use App\Models\PageSection;
use App\Models\Payment;
use App\Models\Product;
use App\Models\State;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // if (PHP_OS != 'WINNT') {
        //     app()->usePublicPath(base_path('public_html'));
        // }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'orders'        => Order::class,
            'subscriptions' => Subscription::class,
            'payments'      => Payment::class,
            'users'         => User::class,
            'product'       => Product::class,
            'section'       => PageSection::class,
            'document_types' => DocumentType::class,
            'dr_networks' => DrNetwork::class,
            'dr_network_webhook_events' => DrNetworkWebhookEvent::class,
            'network_document_rules' => NetworkDocumentRule::class,
            'network_flow_definitions' => NetworkFlowDefinition::class,
            'network_intake_questions' => NetworkIntakeQuestion::class,
            'network_intake_question_sets' => NetworkIntakeQuestionSet::class,
            'network_product_mappings' => NetworkProductMapping::class,
            'network_state_mappings' => NetworkStateMapping::class,
            'states' => State::class,
        ]);

        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'auth.email-verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/') . '/email-verify?verify_url=' . urlencode($url);

            return (new MailMessage)
                ->subject('Verify Your Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email', $frontendUrl)
                ->line('If you did not create an account, no further action is required.');
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url')
                . '/reset-password?token=' . $token
                . '&email=' . $user->email;
        });
    }
}
