<?php

namespace Codewiser\LaravelSendSayMailer;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * @see https://sendsay.ru/api/
 */
class SendSayService implements SendSayServiceInterface
{
    protected ?string $from_name = null;
    protected ?string $from_address = null;
    protected ?LoggerInterface $logger = null;

    public function __construct(
        protected string $url,
        protected string $login,
        protected string $password,
        protected string $sub_login = ''
    ) {
        //
    }

    /**
     * Build personal email request.
     */
    public function buildMessagePayload(Email $email): array
    {
        if (count($email->getTo()) == 1) {
            $name = "{$email->getSubject()}. Email: {$email->getTo()[0]->getAddress()}";
            $group = 'personal';
        } else {
            $name = $email->getSubject();
            $group = 'masssending';
        }

        $attaches = [];

        foreach ($email->getAttachments() as $attachment) {
            $attaches[] = [
                'name'      => $attachment->getName(),
                'content'   => base64_encode($attachment->getBody()),
                'encoding'  => 'base64',
                'mime-type' => $attachment->getContentType()
            ];
        }

        $message = [];

        if ($email->getHtmlBody()) {
            $message['html'] = $email->getHtmlBody();
        }

        if ($email->getTextBody()) {
            $message['text'] = $email->getTextBody();
        }

        return [
            'action'     => 'issue.send',
            'label'      => $email->getHeaders()->get('x-metadata-label')?->getBody() ?? '',
            'letter'     => [
                'subject'    => $email->getSubject(),
                'from.name'  => $this->from_name,
                'from.email' => $this->from_address,
                'message'    => $message,
                'attaches'   => $attaches
            ],
            'name'       => $name,
            'group'      => $group,
            'sendwhen'   => 'now',
            'relink'     => 0,
            'users.list' => $this->buildUsers($email->getTo())
        ];
    }

    /**
     * @param  string[]|Address[]  $emails
     *
     * @return array
     */
    protected function buildUsers(array $emails): array
    {
        $users = [];

        foreach ($emails as $email) {
            $member = [
                'email' => is_string($email) ? $email : $email->getAddress(),
            ];
            $users[] = ['member' => $member];
        }

        return $users;
    }

    /**
     * Build request to exchange trackId to issueId.
     */
    public function buildTrackRequest($track_id): array
    {
        return [
            'action' => 'track.get',
            'id'     => $track_id,
        ];
    }

    /**
     * Build request to get statistics.
     */
    public function buildStatisticsRequest(array $attributes): array
    {
        return [
            'action' => 'stat.uni',
            'select' => $attributes,
            'order'  => ['issue.dt'],
            'filter' => [
                ['a' => 'issue.dt', 'op' => '>=', 'v' => 'current -1 days']
            ]
        ];
    }

    public function send(array $request): array
    {
        $auth = [
            'login'    => $this->login,
            'passwd'   => $this->password,
            'sublogin' => $this->sub_login,
        ];

        $response = Http::asJson()
            ->baseUrl($this->url)
            ->post($this->login, $request + ['one_time_auth' => $auth]);

        $this->debug($request, $response);

        return $response->throw()->json();
    }

    protected function debug(array $request, Response $response): void
    {
        $auth = [
            'login'    => $this->login,
            'passwd'   => str($this->password)->mask('*', 0)->toString(),
            'sublogin' => str($this->sub_login)->mask('*', 0)->toString(),
        ];

        if (isset($request['letter']['attaches'])) {
            foreach ($request['letter']['attaches'] as $i => $attach) {
                $request['letter']['attaches'][$i]['content'] = '[base64encoded]';
            }
        }

        $request = [
            'base_url' => $this->url,
            'method'   => 'POST',
            'path'     => $this->login,
            'data'     => $request + ['one_time_auth' => $auth]
        ];

        if ($response->failed()) {
            $response = [
                'status'        => $response->status(),
                'error_code'    => $response->toException()->getCode(),
                'error_message' => $response->toException()->getMessage(),
                'body'          => $response->body(),
            ];

            $this->logger?->error(class_basename($this), ['request' => $request, 'response' => $response]);
        } else {
            $response = [
                'status' => $response->status(),
                'json'   => $response->json()
            ];

            $this->logger?->debug(class_basename($this), ['request' => $request, 'response' => $response]);

            if (isset($request['data']['letter']['message']['html'])) {
                $request['data']['letter']['message']['html'] = '[html]';
            }

            $this->logger?->info(class_basename($this), ['request' => $request, 'response' => $response]);
        }
    }

    public function setFromName(string $from_name): void
    {
        $this->from_name = $from_name;
    }

    public function setFromAddress(string $from_address): void
    {
        $this->from_address = $from_address;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}