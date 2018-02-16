<?php
namespace Ch2\Autoload;

class Autoloader 
{
    const UNABLE_TO_LOAD = "Unable to load class";
    protected static $dirs = array(); // Hold the list of directories that may contain class files
    protected static $registered = 0; // Used to check if the loader class has alreaduy been registered as autoloader
    protected static $success = false; 

    /**
     * Initializes directories array
     * 
     * @param array $dirs
     */
    public function __construct(array $dirs = array())
    {
        self::init($dirs);
    }

    /**
     * Adds directories to the existing array of directories
     * 
     * @param array | string $dirs
     */
    public static function addDirs($dirs)
    {
        if (is_array($dirs)) {
            self::$dirs = array_merge(self::$dirs, $dirs);
        } else {
            self::$dirs[] = $dirs;
        }
    }

    /**
     * Adds a directory to the list of supported directories
     * Also registers "autoload" as as autoloaading method
     * 
     * @param array | string $dirs
     */
    public static function init($dirs = array())
    {
        if ($dirs) {
            self::addDirs($dirs);
        }
        if (self::$registered == 0) {
            spl_autoload_register(__CLASS__ . '::autoload');
            self::$registered++;
        }
    }

    /**
     * Locates a class file
     * 
     * @param string $className
     * @return boolean
     */
    public static function autoload($className)
    {
        $base_dir = dirname(__DIR__) . '/';

        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        foreach (self::$dirs as $start) {
            $file = $base_dir . $start . DIRECTORY_SEPARATOR . $fileName;
            if (self::loadFile($file)) {
                self::$success = true;
                break;
            }
            if (!self::$success) {
                if (!self::loadFile(__DIR__ . DIRECTORY_SEPARATOR . $fileName)) {
                    throw new \Exception(self::UNABLE_TO_LOAD . ' ' . $className);
                }
            }
        }
        return self::$success;
    }

    /**
     * Loads a file
     * 
     * @param string $fileName
     * @return boolean
     */
    public static function loadFile($fileName) : bool
    {   
        if (file_exists($fileName)) {
            require_once $fileName;
            return true;
        }
        return false;
    }

}

$loader = new Autoloader(array('Classes'));
