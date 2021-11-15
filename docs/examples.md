## noxkiwi\singleton > Examples

If you've already installed the singleton project, you can create your own inherited layer.

So let's assume you want to create a Cache layer that can be swapped without being forced to change the code - If you deploy it to an entirely different environment.

This is a very realistic scenario and it is also part of the project
`noxkiwi\core`

### Abstract class Cache
```
abstract class Cache extends Singleton
{
    /**
     * I will return your cache class.
     * @return noxkiwi\examples\Cache
     */
    final public static function getInstance() : Vehicle
    {
        return parent::getInstance();
    }
}
```


### Final class MemcachedCache
So here is the first driver for the Cache object. 
This object will only contain code that is used for this SINGLE implementation.
All common logic of the object *MUST* be implemented in the abstract we inherit from.
```
final class MemcachedCache extends Cache
{
    /// your MEMCACHE ONLY implementation code here
}
```

### Final class ApcuCache
That's another implementation of the Cache object. Only for an entirely different caching module. This object must also ONLY contain the logic that is required for it's specific implementation. No general code here, too.
```
final class ApcuCache extends Cache
{
    /// your APCU ONLY implementation code here
}
```


### Utilizing Cache from Singleton
So in the following example, I want to demonstrate, that the code does not contains any information about what Cache driver is used. In fact, the Cache object is very strict "SOLID". At least the "S" (Single Responsibility).
```
final class TestContext extends Context
{
    /** @var noxkiwi\cache\Cache $cache */
    private Cache $cache;
    
    /**
     * I will construct.
     */
    protected function __construct() {
        $this->cache = Cache::getDriver();
    }
    
    /**
     * I am a dummy test view function.
     */
    protected function viewDashboard() : void 
    {
        if ($this->cache->get('dashboardId')) {
            echo $this->cache->get('dashboardId');
            
            return;
        }    
        $this->cache->set('dashboardId', 42);
    }
}
```

### Changing the Cache driver
So if you change the environment, you will maybe need to change the caching structure. If, for example, a project moves from a local machine (using APCU) into a scaling cloud environment (where decentral caching could possibly create error scenarios), one would change something like this.

This is the environment configuration for APCU cache:
```
{
    "cache": {
        "default": {
            "driver"  : "noxkiwi\cache\Cache\Apcu",
            "timeout" : 86400
        }
    }
}
```

If, by any means, the Cache driver needs to be a different one, the environment will have to be changed:

```
{
    "cache": {
        "default": {
            "driver"  : "noxkiwi\cache\Cache\Memcache",
            "host"    : "nsfw-cache-01.local",
            "port"    : 11211,
            "timeout" : 86400
        }
    }
}
```

The resulting expectation is, that the project now runs entirely stable in the altered environment. 
So we reached our target - We can change environmental settings around the project without having to touch code for the new environment.
