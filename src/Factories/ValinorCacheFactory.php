<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Factories;

use CuyZ\Valinor\Cache\Cache;
use CuyZ\Valinor\Cache\FileSystemCache;
use CuyZ\Valinor\Cache\FileWatchingCache;
use N1ebieski\KSEFClient\ValueObjects\CachePath;

final class ValinorCacheFactory extends AbstractFactory
{
    public static function make(?CachePath $path = null, bool $watcher = false): Cache
    {
        $path ??= CachePath::from(sys_get_temp_dir() . '/valinor-cache');

        $cache = new FileSystemCache($path->value);

        if ($watcher) {
            return new FileWatchingCache($cache);
        }

        return $cache;
    }
}
