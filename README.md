# aliyun_mns
这个包的目的不是为了封装阿里云官方的MNS sdk，而是为了给laravel提供基于MNS的队列驱动，让现有的使用laravel队列的程序可以方便的切换到阿里云MNS上。

# 使用步骤

1. composer require stone/aliyun_mns:dev-master

2. 修改 config/queue.php， 新增mns配置:

	```
    'connections' => array(

		'sync' => array(
			'driver' => 'sync',
		),

		'redis' => array(
			'driver' => 'redis',
			'queue'  => 'default',
		),
        // 新增配置项
        'aliyunmns' => array(
            'driver' => 'aliyunmns',
            'queue'  => 'default',
        ),
	),

    // mns服务配置
    'mns' => [
	    'key' => 'xxxxx',
	    'secret' => 'xxxxx',
	    'baseuri' => 'http://xxxxx.aliyuncs.com',

	    // 队列名称对应关系
	    'queue' => [
	        'default' => 'shop-demo',
	    ],
    ],
	```

3. 扩展队列驱动

	```
	Queue::extend('aliyunmns', function()
	{
	    return new Stone\Queue\Connectors\AliyunMNSConnector();
	});
	```

4. 正常使用Laravel Queue 即可:
	[https://laravel.com/docs/5.2/queues](https://laravel.com/docs/5.2/queues)



