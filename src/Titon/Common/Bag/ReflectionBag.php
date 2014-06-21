<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Common\Bag;

use Titon\Common\Exception\InvalidDescriptorException;
use Titon\Utility\Path;
use \ReflectionClass;
use \ReflectionMethod;
use \ReflectionProperty;

/**
 * A bag that supplies meta data about the current class by using the reflection API.
 * This data includes path location, method and property information,
 * class name variants, so on and so forth.
 *
 * @package Titon\Common\Bag
 */
class ReflectionBag extends AbstractBag {

    /**
     * Class to introspect.
     *
     * @type object
     */
    protected $_class;

    /**
     * Reflection object.
     *
     * @type \ReflectionClass
     */
    protected $_reflection;

    /**
     * Store the class to grab information on and its reflection.
     *
     * @param object $class
     */
    public function __construct($class) {
        $this->_class = $class;
        $this->_reflection = new ReflectionClass($class);
    }

    /**
     * Return a reflected class value. If no value exists, attempt to generate a value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws \Titon\Common\Exception\InvalidDescriptorException
     */
    public function get($key, $default = null) {
        if ($this->has($key)) {
            return parent::get($key);
        }

        if (method_exists($this, $key)) {
            $value = call_user_func([$this, $key]);

            $this->set($key, $value);

            return $value;
        }

        throw new InvalidDescriptorException(sprintf('Reflection descriptor %s does not exist', $key));
    }

    /**
     * Return the reflection object.
     *
     * @return \ReflectionClass
     */
    public function reflection() {
        return $this->_reflection;
    }

    /**
     * Return the class name with the namespace.
     *
     * @return string
     */
    public function className() {
        return $this->reflection()->getName();
    }

    /**
     * Return the class name without the namespace.
     *
     * @return string
     */
    public function shortClassName() {
        return $this->reflection()->getShortName();
    }

    /**
     * Return the namespace without the class name.
     *
     * @return string
     */
    public function namespaceName() {
        return $this->reflection()->getNamespaceName();
    }

    /**
     * Return the file system path to the class.
     *
     * @return string
     */
    public function filePath() {
        return Path::toPath(get_class($this->_class));
    }

    /**
     * Return an array of public, protected, private and static methods.
     *
     * @return string[]
     */
    public function methods() {
        return array_unique(array_merge(
            $this->publicMethods(),
            $this->protectedMethods(),
            $this->privateMethods(),
            $this->staticMethods()
        ));
    }

    /**
     * Return an array of public methods.
     *
     * @return string[]
     */
    public function publicMethods() {
        return $this->_methods(ReflectionMethod::IS_PUBLIC);
    }

    /**
     * Return an array of protected methods.
     *
     * @return string[]
     */
    public function protectedMethods() {
        return $this->_methods(ReflectionMethod::IS_PROTECTED);
    }

    /**
     * Return an array of private methods.
     *
     * @return string[]
     */
    public function privateMethods() {
        return $this->_methods(ReflectionMethod::IS_PRIVATE);
    }

    /**
     * Return an array of static methods.
     *
     * @return string[]
     */
    public function staticMethods() {
        return $this->_methods(ReflectionMethod::IS_STATIC);
    }

    /**
     * Return an array of public, protected, private and static properties.
     *
     * @return string[]
     */
    public function properties() {
        return array_unique(array_merge(
            $this->publicProperties(),
            $this->protectedProperties(),
            $this->privateProperties(),
            $this->staticProperties()
        ));
    }

    /**
     * Return an array of public properties.
     *
     * @return string[]
     */
    public function publicProperties() {
        return $this->_properties(ReflectionProperty::IS_PUBLIC);
    }

    /**
     * Return an array of protected properties.
     *
     * @return string[]
     */
    public function protectedProperties() {
        return $this->_properties(ReflectionProperty::IS_PROTECTED);
    }

    /**
     * Return an array of private properties.
     *
     * @return string[]
     */
    public function privateProperties() {
        return $this->_properties(ReflectionProperty::IS_PRIVATE);
    }

    /**
     * Return an array of static properties.
     *
     * @return string[]
     */
    public function staticProperties() {
        return $this->_properties(ReflectionProperty::IS_STATIC);
    }

    /**
     * Return an array of constants defined in the class.
     *
     * @return string[]
     */
    public function constants() {
        return $this->reflection()->getConstants();
    }

    /**
     * Return an array of interfaces that the class implements.
     *
     * @return string[]
     */
    public function interfaces() {
        return $this->reflection()->getInterfaceNames();
    }

    /**
     * Return an array of traits that the class implements.
     *
     * @return string[]
     */
    public function traits() {
        $traits = $this->reflection()->getTraitNames();
        $parent = get_parent_class($this->_class);

        while ($parent) {
            $traits = array_merge($traits, class_uses($parent));
            $parent = get_parent_class($parent);
        }

        return array_values($traits);
    }

    /**
     * Return the parent class name.
     *
     * @return string
     */
    public function parent() {
        return $this->reflection()->getParentClass()->getName();
    }

    /**
     * Return an array of methods for the defined scope.
     *
     * @param int $scope
     * @return string[]
     */
    protected function _methods($scope) {
        $methods = [];

        foreach ($this->reflection()->getMethods($scope) as $method) {
            $methods[] = $method->getName();
        }

        return $methods;
    }

    /**
     * Return an array of properties for the defined scope.
     *
     * @param int $scope
     * @return string[]
     */
    protected function _properties($scope) {
        $props = [];

        foreach ($this->reflection()->getProperties($scope) as $prop) {
            $props[] = $prop->getName();
        }

        return $props;
    }

}