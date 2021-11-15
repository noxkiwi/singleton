<?php declare(strict_types = 1);
namespace noxkiwi\singleton;

use noxkiwi\core\Environment;
use noxkiwi\core\Exception\ConfigurationException;
use noxkiwi\singleton\Exception\SingletonException;
use function func_get_args;
use const E_ERROR;

/**
 * I am the base Singleton class.
 * I provide the singleton pattern for all purposes.
 * Also, I provide the logic to load classes without exact mentioning,
 * in that case I can utilize the Environment of the project to load the correct one (getClient()).
 *
 * For Example, this can come in handy to swap drivers of classes without changing the code.
 * As a precise example, you can simply swap the (machine based) APCU cache by a (centralized) Memcache instance. Just by configuration.
 *
 * @package      noxkiwi\singleton
 * @author       Jan Nox <jan.nox@pm.me>
 * @license      https://nox.kiwi/license
 * @copyright    2021 noxkiwi
 * @version      1.0.2
 * @link         https://nox.kiwi/
 */
abstract class Singleton
{
    protected const IDENTIFIER  = 'default';
    protected const USE_DRIVER  = false;
    protected const USE_MAPPING = false;
    /** @var static[] */
    private static array $instances = [];

    /**
     * Singleton constructor.
     */
    protected function __construct()
    {
    }

    /**
     * If the single instance does not exist, create it.
     * Return the single instance then.
     *
     * Notice - Many other implementations of the Singleton Pattern have final
     * Constructors and final getInstance() methods. At least the getInstnace()
     * of this Singleton implementation shall NOT be final. Why? You ask?
     *
     * @param string|null $identifier
     *
     * @throws \noxkiwi\singleton\Exception\SingletonException
     * @return static
     */
    final public static function getInstance(?string $identifier = null): static
    {
        if (static::USE_DRIVER) {
            return static::getDriver($identifier);
        }
        if (static::USE_MAPPING) {
            return static::getMapping();
        }
        $className = static::class;
        if (! isset(static::$instances[static::class])) {
            $arguments                        = func_get_args();
            $arguments                        = ! empty($arguments[0]) ? $arguments[0] : [];
            static::$instances[static::class] = new $className($arguments);
            static::$instances[static::class]->initialize();
        }

        return static::$instances[$className];
    }

    /**
     * @throws \noxkiwi\singleton\Exception\SingletonException
     * @return static
     */
    protected static function getMapping(): static
    {
        throw new SingletonException('NOT IMPLEMENTED', E_ERROR);
    }

    /**
     * I am the initiator for all Singleton Objects.
     */
    protected function initialize(): void
    {
    }

    /**
     * I will return the instance of the desired class utilizing the driver-layer in between.
     * <br />
     * @example Sms::getDriver('messagebird1') would return an instance of Sms\Messagebird -
     * but the final call (Messagebird) is determined by the ENVIRONMENT config of the keys sms->messagebird1
     *
     * @param string|null $identifier
     *
     * @throws \noxkiwi\singleton\Exception\SingletonException
     * @return mixed
     */
    final public static function getDriver(?string $identifier = null): static
    {
        $identifier ??= self::IDENTIFIER;
        $simpleName = static::class . $identifier;
        if (isset(static::$instances[$simpleName])) {
            return static::$instances[$simpleName];
        }
        $segments   = explode('\\', static::class);
        $className  = end($segments);
        $type       = strtolower($className);
        $identifier = strtolower($identifier ?? self::IDENTIFIER);
        try {
            $config = Environment::getInstance()->getDriverConfig($type, $identifier);
        } catch (ConfigurationException $exception) {
            throw new SingletonException($exception->getMessage(), E_ERROR);
        }
        $className = $config['driver'] . ucfirst($type);
        if (! class_exists($className)) {
            throw new SingletonException("$type '$identifier' is configured to use driver '$className' which cannot be found.", E_ERROR);
        }
        static::$instances[$simpleName] = new $className($config);

        return static::$instances[$simpleName];
    }

    /**
     * I will prevent calling the __clone magic method from outside.
     */
    final protected function __clone()
    {
    }

    /**
     * I will prevent calling the __wakeup magic method from outside.
     */
    final public function __wakeup()
    {
    }
}
