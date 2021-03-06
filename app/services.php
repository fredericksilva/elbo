<?php

return (function() {
	$containerBuilder = new DI\ContainerBuilder();

	$config = new Elbo\Library\Configuration();

	if ($config->get('environment.phase') === 'production') {
		$redis = new Redis();
		$redis->connect($config->get('redis.host'), $config->get('redis.port'));

		$cache = new Doctrine\Common\Cache\RedisCache();
		$cache->setRedis($redis);
		$cache->setNamespace('elbo:sc:');

		$containerBuilder->setDefinitionCache($cache);

		$containerBuilder->addDefinitions([
			Redis::class => $redis
		]);
	}
	else {
		$cache = new Doctrine\Common\Cache\ArrayCache();
		$containerBuilder->setDefinitionCache($cache);

		$containerBuilder->addDefinitions([
			Redis::class => function(Elbo\Library\Configuration $config) {
				$redis = new Redis();
				$redis->connect($config->get('redis.host'), $config->get('redis.port'));

				return $redis;
			}
		]);
	}

	$containerBuilder->addDefinitions([
		Elbo\Library\Configuration::class => $config,

		Elbo\Library\Session::class => DI\object(Elbo\Library\Session::class),

		Elbo\Library\URLShortener::class => DI\object(Elbo\Library\URLShortener::class),

		Elbo\Library\EmailValidator::class => DI\object(Elbo\Library\EmailValidator::class),

		Symfony\Component\Filesystem\Filesystem::class => DI\object(Symfony\Component\Filesystem\Filesystem::class),

		BaconQrCode\Writer::class => DI\object(BaconQrCode\Writer::class),

		BaconQrCode\Renderer\RendererInterface::class => DI\object(BaconQrCode\Renderer\Image\Png::class),

		Elbo\RateLimiters\LoginRateLimiter::class => DI\object(Elbo\RateLimiters\LoginRateLimiter::class),

		Elbo\RateLimiters\AnonShortenRateLimiter::class => DI\object(Elbo\RateLimiters\AnonShortenRateLimiter::class),

		Elbo\RateLimiters\UserShortenRateLimiter::class => DI\object(Elbo\RateLimiters\UserShortenRateLimiter::class),

		ReCaptcha\ReCaptcha::class => function(Elbo\Library\Configuration $config) {
			return new ReCaptcha\ReCaptcha($config->get('api_key.recaptcha_secret_key'));
		},

		MaxMind\Db\Reader::class => function() {
			return new MaxMind\Db\Reader(__DIR__.'/../data/GeoLite2-Country.mmdb');
		},

		Nette\Mail\IMailer::class => function(Elbo\Library\Configuration $config) {
			if ($config->get('environment.phase') !== 'production') {
				return new Elbo\Library\MockMailer();
			}

			return new Nette\Mail\SmtpMailer($config->get('mailer.smtp'));
		},

		Twig_Environment::class => function(Elbo\Library\Configuration $config) {
			$fs_loader = new Twig_Loader_Filesystem(__DIR__.'/../resources/views/');
			$twig = new Twig_Environment($fs_loader);

			if ($config->get('environment.phase') === 'production') {
				$twig->setCache(__DIR__.'/../data/cache/twig/');
			}

			$twig->addFunction(new Twig_SimpleFunction('dump', function($var) {
				dump($var);
			}));

			$twig->addFunction(new Twig_SimpleFunction('config', function($key) use ($config) {
				return $config->get($key);
			}));

			$twig->addFunction(new Twig_SimpleFunction('current_year', function() {
				return date('Y');
			}));

			$twig->addFunction(new Twig_SimpleFunction('url', function($path, $arr) {
				$rv = $path;
				$begin_query_str = false;

				foreach ($arr as $param => $value) {
					if (!$begin_query_str) {
						$rv .= '?';
						$begin_query_str = true;
					}
					else {
						$rv .= '&';
					}

					$rv .= rawurlencode($param).'='.urlencode($value);
				}

				return $rv;
			}));

			return $twig;
		}
	]);

	return $containerBuilder->build();
})();
