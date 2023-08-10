<?php

namespace Codewiser\LaravelSendSayMailer;

use Closure;
use Illuminate\Support\ServiceProvider;

class SendSayServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(SendSayService::class, function () {

            $config = config('mail.mailers.sendsay', []);

            $service = new SendSayService(
                $config['endpoint'] ?? 'https://api.sendsay.ru/general/api/v100/json',
                $config['login'] ?? '',
                $config['password'] ?? '',
                $config['sub_login'] ?? ''
            );

            $from = config('mail.from', []);
            $service->setFromAddress($from['address'] ?? '');
            $service->setFromName($from['name'] ?? '');

            if ($logger = config('mail.mailers.log.channel')) {
                $service->setLogger(logger()->channel($logger));
            }

            return $service;
        });

        $this->app->bind(SendSayServiceInterface::class, function () {
            return app(SendSayService::class);
        });
    }

    public function boot()
    {
        \Illuminate\Support\Facades\Mail::extend(
            'sendsay',
            fn() => new SendSayTransport(app(SendSayServiceInterface::class))
        );
    }
}