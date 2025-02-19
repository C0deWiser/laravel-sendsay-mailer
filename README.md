# SendSay mailer for Laravel

This package brings one more mailer to your Laravel project — 
https://sendsay.ru service.

It supports as personal, as mass sending of emails.

## Disclaimer

This mailer was tested with very specific tasks, so we can not guarantee 
that it will meet your expectations. 

## Installation

Just install package from composer.

## Configuration

Add `sendsay` section to `mail.mailers` config of your application:

```php
'sendsay' => [
    'transport' => 'sendsay',
    'endpoint' => env('SENDSAY_URL', 'https://api.sendsay.ru/general/api/v100/json'),
    'login' => env('SENDSAY_LOGIN'),
    'password' => env('SENDSAY_PASS'),
    'sub_login' => env('SENDSAY_SUBLOGIN', ''),
],
```

Service will write info/error logs to channel defined in `mail.mailers.log.channel` config.

Finally, set `MAIL_MAILER=sendsay` to your `.env` file.

## Mass sending

Compose `Mailable` with more than one recipient and just 
send it:

```php
use Illuminate\Support\Facades\Mail;

$recipients = [
    'foo@example.com',
    'bar@example.com',
];

Mail::send(new CustomMailable($recipients));
```

## Personal sending

Compose `Mailable` with only one recipient or use `Notification`.

## Getting response

The only way to pass mailer response through facade back to the application 
(that I found) it to append response as a debug of 
`\Symfony\Component\Mailer\SentMessage`:

```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Events\MessageSent;

$recipients = [
    'foo@example.com',
    'bar@example.com',
];

Event::listen(MessageSent::class, function (MessageSent $event) {
    // json encoded response
    dump($event->sent->getDebug());
});

Mail::send(new CustomMailable($recipients));
```