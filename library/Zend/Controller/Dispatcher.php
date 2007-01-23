<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Dispatcher
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */ 

/** Zend_Controller_Dispatcher_Exception */
require_once 'Zend/Controller/Dispatcher/Exception.php';

/** Zend_Controller_Dispatcher_Interface */
require_once 'Zend/Controller/Dispatcher/Interface.php';

/** Zend_Controller_Request_Abstract */
require_once 'Zend/Controller/Request/Abstract.php';

/** Zend_Controller_Response_Abstract */
require_once 'Zend/Controller/Response/Abstract.php';

/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

/**
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage Dispatcher
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Controller_Dispatcher implements Zend_Controller_Dispatcher_Interface
{
    /**
     * Current dispatchable directory
     * @var string
     */
    protected $_curDirectory;

    /**
     * Current module (formatted)
     * @var string
     */
    protected $_curModule;

    /**
     * Default action name; defaults to 'index'
     * @var string 
     */
    protected $_defaultAction = 'index';

    /**
     * Default controller name; defaults to 'index'
     * @var string 
     */
    protected $_defaultController = 'index';

    /**
     * Directories where Zend_Controller_Action files are stored.
     * @var array
     */
    protected $_directories = array();

    /**
     * Array of invocation parameters to use when instantiating action 
     * controllers
     * @var array 
     */
    protected $_invokeParams = array();

    /**
     * Path delimiter character
     * @var string
     */
    protected $_pathDelimiter = '_';

    /**
     * Response object to pass to action controllers, if any
     * @var Zend_Controller_Response_Abstract|null 
     */
    protected $_response = null;

    /**
     * Word delimiter characters
     * @var array
     */
    protected $_wordDelimiter = array('-', '.');

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct(array $params = array())
    {
        $this->setParams($params);
    }

    /**
     * Format the module name.
     * 
     * @param string $unformatted 
     * @return string
     */
    public function formatModuleName($unformatted)
    {
        return ucfirst($this->_formatName($unformatted));
    }

    /**
     * Formats a string into a controller name.  This is used to take a raw
     * controller name, such as one that would be packaged inside a Zend_Controller_Dispatcher_Token
     * object, and reformat it to a proper class name that a class extending
     * Zend_Controller_Action would use.
     *
     * @param string $unformatted
     * @return string
     */
    public function formatControllerName($unformatted)
    {
        return ucfirst($this->_formatName($unformatted)) . 'Controller';
    }

    /**
     * Formats a string into an action name.  This is used to take a raw
     * action name, such as one that would be packaged inside a Zend_Controller_Dispatcher_Token
     * object, and reformat into a proper method name that would be found
     * inside a class extending Zend_Controller_Action.
     *
     * @todo Should action method names allow underscores?
     * @param string $unformatted
     * @return string
     */
    public function formatActionName($unformatted)
    {
        $formatted = $this->_formatName($unformatted, true);
        return strtolower(substr($formatted, 0, 1)) . substr($formatted, 1) . 'Action';
    }

    /**
     * Convert a class name to a filename
     * 
     * @param string $class 
     * @return string
     */
    public function classToFilename($class)
    {
        return str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
    }

    /**
     * Verify delimiter
     *
     * Verify a delimiter to use in controllers/modules/actions. May be a 
     * single string or an array of strings.
     * 
     * @param string|array $spec 
     * @return array
     * @throws Zend_Controller_Dispatcher_Exception with invalid delimiters
     */
    public function _verifyDelimiter($spec)
    {
        if (is_string($spec)) {
            return (array) $spec;
        } elseif (is_array($spec)) {
            $allStrings = true;
            foreach ($spec as $delim) {
                if (!is_string($delim)) {
                    $allStrings = false;
                    break;
                }
            }

            if (!$allStrings) {
                require_once 'Zend/Controller/Dispatcher/Exception.php';
                throw new Zend_Controller_Dispatcher_Exception('Word delimiter array must contain only strings');
            }

            return $spec;
        }

        require_once 'Zend/Controller/Dispatcher/Exception.php';
        throw new Zend_Controller_Dispatcher_Exception('Invalid word delimiter');
    }

    /**
     * Retrieve the word delimiter character(s) used in 
     * controller/module/action names
     * 
     * @return array
     */
    public function getWordDelimiter()
    {
        return $this->_wordDelimiter;
    }

    /**
     * Set word delimiter
     *
     * Set the word delimiter to use in controllers/modules/actions. May be a 
     * single string or an array of strings.
     * 
     * @param string|array $spec 
     * @return Zend_Controller_Dispatcher
     */
    public function setWordDelimiter($spec)
    {
        $spec = $this->_verifyDelimiter($spec);
        $this->_wordDelimiter = $spec;

        return $this;
    }

    /**
     * Retrieve the path delimiter character(s) used in 
     * controller/module/action names
     * 
     * @return array
     */
    public function getPathDelimiter()
    {
        return $this->_pathDelimiter;
    }

    /**
     * Set path delimiter
     *
     * Set the path delimiter to use in controllers/modules/actions. May be a 
     * single string or an array of strings.
     * 
     * @param string|array $spec 
     * @return Zend_Controller_Dispatcher
     */
    public function setPathDelimiter($spec)
    {
        if (!is_string($spec)) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception('Invalid path delimiter');
        }
        $this->_pathDelimiter = $spec;

        return $this;
    }

    /**
     * Formats a string from a URI into a PHP-friendly name.  
     *
     * By default, replaces words separated by '-' or '.' with camelCaps. If 
     * $isAction is false, it also preserves underscores, and makes the letter 
     * following the underscore uppercase. All non-alphanumeric characters are 
     * removed.
     *
     * @param string $unformatted
     * @param boolean $isAction Defaults to false
     * @return string
     */
    protected function _formatName($unformatted, $isAction = false)
    {
        // preserve directories
        if (!$isAction) {
            $segments = explode($this->getPathDelimiter(), $unformatted);
        } else {
            $segments = (array) $unformatted;
        }

        foreach ($segments as $key => $segment) {
            $segment        = str_replace($this->getWordDelimiter(), ' ', strtolower($segment));
            $segment        = preg_replace('/[^a-z0-9 ]/', '', $segment);
            $segments[$key] = str_replace(' ', '', ucwords($segment));
        }

        return implode('_', $segments);
    }

    /**
    * Add a single path to the controller directory stack
    * 
    * @param string $path 
    * @return Zend_Controller_Dispatcher
    */
    public function addControllerDirectory($path, $module = null)
    {
        if (!is_string($path) || !is_dir($path) || !is_readable($path)) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception("Directory \"$path\" not found or not readable");
        }

        if ((null === $module) || ('default' == $module)) {
            $this->_directories['default'][] = rtrim($path, '\//');
        } else {
            $this->_directories[(string) $module] = rtrim($path, '\//');
        }

        return $this;
    }
    /**
     * Sets the directory(ies) where the Zend_Controller_Action class files are stored.
     *
     * @param string|array $path
     * @return Zend_Controller_Dispatcher
     */
    public function setControllerDirectory($path)
    {
        $dirs = (array) $path;
        $this->_directories = array('default' => array());

        foreach ($dirs as $key => $dir) {
            if (!is_dir($dir) || !is_readable($dir)) {
                require_once 'Zend/Controller/Dispatcher/Exception.php';
                throw new Zend_Controller_Dispatcher_Exception("Directory \"$dir\" not found or not readable");
            }
            if (!is_string($key) || ('default' == $key)) {
                $this->_directories['default'][] = rtrim($dir, '/\\');
            } else {
                $this->_directories[$key] = rtrim($dir, '/\\');
            }
        }

        return $this;
    }

    /**
     * Return the currently set directory for Zend_Controller_Action class 
     * lookup
     * 
     * @return array
     */
    public function getControllerDirectory()
    {
        return $this->_directories;
    }

    /**
     * Returns TRUE if the Zend_Controller_Request_Abstract object can be 
     * dispatched to a controller.
     *
     * Use this method wisely. By default, the dispatcher will fall back to the 
     * default controller (either in the module specified or the global default) 
     * if a given controller does not exist. This method returning false does 
     * not necessarily indicate the dispatcher will not still dispatch the call.
     *
     * @param Zend_Controller_Request_Abstract $action
     * @return boolean
     */
    public function isDispatchable(Zend_Controller_Request_Abstract $request)
    {
        $className = $this->_getController($request);
        if (!$className) {
            return false;
        }

        $fileSpec    = $this->classToFilename($className);
        $dispatchDir = $this->getDispatchDirectory();
        if (is_string($dispatchDir)) {
            // module controller found
            $test = $dispatchDir . DIRECTORY_SEPARATOR . $fileSpec;
            return Zend::isReadable($test);
        }

        // Test for controller in default controller directories
        $found = false;
        foreach ($dispatchDir as $dir) {
            $test = $dir . DIRECTORY_SEPARATOR . $fileSpec;
            if (Zend::isReadable($test)) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * Add or modify a parameter to use when instantiating an action controller
     * 
     * @param string $name
     * @param mixed $value 
     * @return Zend_Controller_Dispatcher
     */
    public function setParam($name, $value)
    {
        $name = (string) $name;
        $this->_invokeParams[$name] = $value;
        return $this;
    }

    /**
     * Set parameters to pass to action controller constructors
     * 
     * @param array $params 
     * @return Zend_Controller_Dispatcher
     */
    public function setParams(array $params)
    {
        $this->_invokeParams = array_merge($this->_invokeParams, $params);
        return $this;
    }

    /**
     * Retrieve a single parameter from the controller parameter stack
     * 
     * @param string $name 
     * @return mixed
     */
    public function getParam($name)
    {
        if(isset($this->_invokeParams[$name])) {
            return $this->_invokeParams[$name];
        }

        return null;
    }

    /**
     * Retrieve action controller instantiation parameters
     * 
     * @return array
     */
    public function getParams()
    {
        return $this->_invokeParams;
    }

    /**
     * Clear the controller parameter stack
     * 
     * By default, clears all parameters. If a parameter name is given, clears 
     * only that parameter; if an array of parameter names is provided, clears 
     * each.
     * 
     * @param null|string|array single key or array of keys for params to clear
     * @return Zend_Controller_Dispatcher
     */
    public function clearParams($name = null)
    {
        if (null === $name) {
            $this->_invokeParams = array();
        } elseif (is_string($name) && isset($this->_invokeParams[$name])) {
            unset($this->_invokeParams[$name]);
        } elseif (is_array($name)) {
            foreach ($name as $key) {
                if (is_string($key) && isset($this->_invokeParams[$key])) {
                    unset($this->_invokeParams[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Set response object to pass to action controllers
     * 
     * @param Zend_Controller_Response_Abstract|null $response 
     * @return Zend_Controller_Dispatcher
     */
    public function setResponse(Zend_Controller_Response_Abstract $response = null)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * Return the registered response object
     * 
     * @return Zend_Controller_Response_Abstract|null
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Set the default controller (minus any formatting)
     * 
     * @param string $controller 
     * @return Zend_Controller_Dispatcher
     */
    public function setDefaultController($controller)
    {
        $this->_defaultController = (string) $controller;
    }

    /**
     * Retrive the default controller name (minus formatting)
     * 
     * @return string
     */
    public function getDefaultController()
    {
        return $this->_defaultController;
    }

    /**
     * Set the default action (minus any formatting)
     * 
     * @param string $action 
     * @return Zend_Controller_Dispatcher
     */
    public function setDefaultAction($action)
    {
        $this->_defaultAction = (string) $action;
    }

    /**
     * Retrive the default action name (minus formatting)
     * 
     * @return string
     */
    public function getDefaultAction()
    {
        return $this->_defaultAction;
    }

    /**
     * Dispatch to a controller/action
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @return boolean
     */
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response)
    {
        $this->setResponse($response);

        /**
         * Get controller class
         */
        $className = $this->_getController($request);

        /**
         * If no class name returned, dispatch to default controller
         */
        if (empty($className)) {
            $className = $this->_getDefaultControllerName($request);
        }

        /**
         * Load the controller class file
         *
         * Attempts to load the controller class file from {@link getControllerDirectory()}.
         */
        $dispatchDir = $this->getDispatchDirectory();
        if (is_string($dispatchDir)) {
            // module found
            $loadFile = $dispatchDir . DIRECTORY_SEPARATOR . $this->classToFilename($className);
            $dir      = dirname($loadFile);
            $file     = basename($loadFile);
            Zend::loadFile($file, $dir, true);
            $moduleClass = $this->_curModule . '_' . $className;
            if (!class_exists($moduleClass)) {
                require_once 'Zend/Controller/Dispatcher/Exception.php';
                throw new Zend_Controller_Dispatcher_Exception('Invalid controller class ("' . $moduleClass . '")');
            }
            $className = $moduleClass;
        } else {
            Zend::loadClass($className, $dispatchDir);
        }
        
        /**
         * Instantiate controller with request, response, and invocation 
         * arguments; throw exception if it's not an action controller
         */
        $controller = new $className($request, $this->getResponse(), $this->getParams());
        if (!$controller instanceof Zend_Controller_Action) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception("Controller '$className' is not an instance of Zend_Controller_Action");
        }

        /**
         * Retrieve the action name
         */
        $action = $this->_getAction($request);

        /**
         * If method does not exist, default to __call()
         */
        $doCall = !method_exists($controller, $action);

        /**
         * Dispatch the method call
         */
        $request->setDispatched(true);
        $controller->preDispatch();
        if ($request->isDispatched()) {
            // preDispatch() didn't change the action, so we can continue
            if ($doCall) {
                $controller->__call($action, array());
            } else {
                $controller->$action();
            }
            $controller->postDispatch();
        }

        // Destroy the page controller instance and reflection objects
        $controller = null;
    }

    /**
     * Get controller name
     *
     * Try request first; if not found, try pulling from request parameter; 
     * if still not found, fallback to default
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return string|false Returns class name on success
     */
    protected function _getController($request)
    {
        $controllerName = $request->getControllerName();
        if (empty($controllerName)) {
            return false;
        }

        $className = $this->formatControllerName($controllerName);
        $controllerDirectory = $this->getControllerDirectory();

        /**
         * Check to see if a module name is present in the request, and that 
         * the module has been defined in the controller directory list; if so, 
         * prepend module to controller class name, using underscore as 
         * separator. 
         */
        $module = $request->getModuleName();
        
        if ($this->_isValidModule($module)) {
            $this->_curModule    = $this->formatModuleName($module);
            $this->_curDirectory = $controllerDirectory[$module];
        } else {
            $this->_curDirectory = $controllerDirectory['default'];
        }

        return $className;
    }

    /**
     * Determine if a given module is valid
     * 
     * @param string $module 
     * @return bool
     */
    protected function _isValidModule($module)
    {
        $controllerDir = $this->getControllerDirectory();
        return ((null !== $module) && ('default' != $module) && isset($controllerDir[$module]));
    }

    /**
     * Retrieve default controller
     *
     * Determines whether the default controller to use lies within the 
     * requested module, or if the global default should be used.
     *
     * Will only use the module default if this has been specified as a param:
     * <code>
     * $dispatcher->setParam('useModuleDefault', true);
     *
     * // OR
     * $front->setParam('useModuleDefault', true);
     * </code>
     * 
     * @param Zend_Controller_Request_Abstract $request 
     * @return string
     */
    protected function _getDefaultControllerName($request)
    {
        $controller = $this->getDefaultController();
        $default    = $this->formatControllerName($controller);
        $request->setControllerName($controller);

        $useModuleDefault = $this->getParam('useModuleDefault');

        $module = $request->getModuleName();
        if ($useModuleDefault && $this->_isValidModule($module)) {
            $controllerDirs = $this->getControllerDirectory();
            $moduleDir      = $controllerDirs[$module];
            $fileSpec       = $moduleDir . DIRECTORY_SEPARATOR . $this->classToFilename($default);
            if (Zend::isReadable($fileSpec)) {
                $this->_curModule    = $this->formatModuleName($module);
                $this->_curDirectory = $moduleDir;
            } 
        }

        return $default;
    }

    /**
     * Determine the action name
     *
     * First attempt to retrieve from request; then from request params 
     * using action key; default to default action
     *
     * Returns formatted action name
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return string
     */
    protected function _getAction($request)
    {
        $action = $request->getActionName();
        if (empty($action)) {
            $action = $this->getDefaultAction();
            $request->setActionName($action);
        }

        return $this->formatActionName($action);
    }
    
    /**
     * Return the value of the currently selected dispatch directory (as set by 
     * {@link _getController()})
     * 
     * @return string
     */
    public function getDispatchDirectory()
    {
        return $this->_curDirectory;
    }
}
