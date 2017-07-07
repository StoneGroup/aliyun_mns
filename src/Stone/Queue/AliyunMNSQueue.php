<?php namespace Stone\Queue;

use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use AliMNS\Client;

class AliyunMNSQueue extends Queue implements QueueContract
{
    private $client;

    private static $queueMap;

    public function __construct($config = [])
    {
        $config = config('queue.mns');
        $this->client = new Client($config['baseuri'], $config['key'], $config['secret']);
    }

    public function size($queue = null)
    {
        $client = $this->client->getClient();

        if (isset($queue)) {
            $client->getQueueRef($queue);
        }

        $clientAttribute = $client->getAttribute();
        $count = 0;

        if ($clientAttribute->isSucceed()) {
            $attributes = $clientAttribute->getQueueAttributes();
            $data = [];
            $data['activeMessages'] = $attributes->getActiveMessages();
            $data['inactiveMessages'] = $attributes->getInactiveMessages();
            $count = $data['activeMessages'] + $data['inactiveMessages'];
        }

        return $count;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $payload = $this->createPayload($job, $data);
        return $this->pushRaw($payload, $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = array())
    {
        $this->client->setQueue(self::getQueue($queue));
        return $this->client->publish($payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $seconds = $this->getSeconds($delay);
        $payload = $this->createPayload($job, $data);
        $this->client->setQueue(self::getQueue($queue));

        return $this->client->publish($payload, $seconds);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Queue\Jobs\Job|null
     */
    public function pop($queue = null)
    {
        $this->client->setQueue(self::getQueue($queue));
        $job = $this->client->consume();
        if (!is_null($job)) {
            return $this->resolveJob($job, $queue);
        }
    }

    protected function resolveJob($job, $queue)
    {
        return new Jobs\AliyunMNSJob($this->container, $job, $this->client, $queue);
    }

    public static function getQueue($queue = null)
    {
        if (!isset(self::$queueMap)) {
            self::$queueMap = config('queue.mns.queue');
        }

        if (empty($queue)) {
            return self::$queueMap['default'];
        }

        if (empty(self::$queueMap[$queue])) {
            throw new \Exception('Aliyun MNS queue name is not setted');
        }

        return self::$queueMap[$queue];
    }
}
