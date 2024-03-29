<?php

namespace Bdf\QueueMessengerBundle\Transport\Stamp;

use Bdf\Queue\Message\Message;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * DestinationStamp configuration.
 */
class DestinationStamp implements StampInterface
{
    public const RPC_TIMEOUT = 1000;

    private $messageName;
    private $maxTries;
    private $noStore;
    private $rpcTimeout = self::RPC_TIMEOUT;
    private $needsReply;
    private $headers = [];

    /**
     * @return $this
     */
    public function setMessageName(?string $messageName)
    {
        $this->messageName = $messageName;

        return $this;
    }

    public function getMessageName(): ?string
    {
        return $this->messageName;
    }

    /**
     * Change the message max retry count
     * If set to zero, default retry count is used.
     *
     * @return $this
     */
    public function setMaxTries(?int $value)
    {
        $this->maxTries = $value;

        return $this;
    }

    /**
     * Get max number of retry.
     */
    public function getMaxTries(): ?int
    {
        return $this->maxTries;
    }

    /**
     * Disable storing job when failed.
     *
     * @return $this
     */
    public function disableStore(bool $flag = true)
    {
        $this->noStore = $flag;

        return $this;
    }

    /**
     * Does the job should be saved when failed to execute ?
     * If the return value is true, the failed job should not be stored.
     */
    public function noStore(): bool
    {
        return (bool) $this->noStore;
    }

    /**
     * Change the rpc timeout (in milliseconds).
     *
     * @return $this
     */
    public function setRpcTimeout(int $value)
    {
        $this->rpcTimeout = $value;

        return $this;
    }

    /**
     * Get the rpc timeout.
     */
    public function getRpcTimeout(): int
    {
        return $this->rpcTimeout;
    }

    /**
     * Defines if the message needs a reply or not.
     *
     * @param bool $needsReply true for enable reply
     *
     * @return $this
     *
     * @see PromiseInterface
     */
    public function setNeedsReply(bool $needsReply = true): Message
    {
        $this->needsReply = $needsReply;

        return $this;
    }

    /**
     * Check whether the message needs reply.
     */
    public function getNeedsReply(): bool
    {
        return (bool) $this->needsReply;
    }

    /**
     * Set the message driver options.
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get all message headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return $this
     */
    public function setTopic(string $topic)
    {
        $this->headers['topic'] = $topic;

        return $this;
    }

    /**
     * @return $this
     */
    public function setReplyTo(string $value)
    {
        $this->headers['replyTo'] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCorrelationId(string $value)
    {
        $this->headers['correlationId'] = $value;

        return $this;
    }
}
