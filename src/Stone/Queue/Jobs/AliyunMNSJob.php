<?php namespace Stone\Queue\Jobs;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Contracts\Queue\Job as JobContract;
use Stone\Queue\AliyunMNSQueue;

class AliyunMNSJob extends Job implements JobContract {

	/**
	 * The class name of the job.
	 *
	 * @var AliyunMNS\Responses\ReceiveMessageResponse
	 */
	protected $job;

	/**
	 * The queue message data.
	 *
	 * @var string
	 */
	protected $data;

    private $client;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @param  AliyunMNS\Responses\ReceiveMessageResponse  $job
	 * @param  object  $receiptHandle
	 * @return void
	 */
	public function __construct(Container $container, $job, $client = null, $queue = 'default')
	{
		$this->job = $job;
		$this->container = $container;
        $this->client = $client;
        $this->queue = $queue;

        if (empty($this->queue)) {
            $this->queue = 'default';
        }
	}

	/**
	 * Get the raw body string for the job.
	 *
	 * @return string
	 */
	public function getRawBody()
	{
        return $this->job->getMessageBody();
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		parent::delete();
        $this->resetQueue();
        $this->client->delete($this->job->getReceiptHandle());
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
        parent::release($delay);

        // 由于阿里云消息服务的特性， 消息只要不删除，经过一段时间后可以再次被消费，这个时间通过设置visibility来实现
        // 因此不需要主动把消息再放回队列，这样做更安全可靠。 因为消息的取出和放入不是原子操作，存在失败的可能性
        // 一旦失败，就会出现漏处理消息的情况 
	}

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @return int
	 */
	public function attempts()
	{
        $this->resetQueue();
        return intval($this->job->getDequeueCount());
	}

    public function resetQueue()
    {
        $queue = AliyunMNSQueue::getQueue($this->queue);
        $this->client->setQueue($queue);
    }
}
