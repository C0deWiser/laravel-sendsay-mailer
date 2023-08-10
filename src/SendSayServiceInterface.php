<?php

namespace Codewiser\LaravelSendSayMailer;

use Symfony\Component\Mime\Email;
use Throwable;

interface SendSayServiceInterface
{
    /**
     * Build a request payload from Symfony object.
     */
    public function buildMessagePayload(Email $email): array;

    /**
     * Build request to get statistics.
     */
    public function buildStatisticsRequest(array $attributes): array;

    /**
     * Build request to exchange trackId to issueId.
     */
    public function buildTrackRequest($track_id): array;

    /**
     * Send a request payload.
     *
     * @throws Throwable
     */
    public function send(array $request): array;
}