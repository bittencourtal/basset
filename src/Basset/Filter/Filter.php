<?php namespace Basset\Filter;

use Closure;
use ReflectionClass;

class Filter {

    /**
     * Array of instantiation arguments.
     *
     * @var array
     */
    protected $arguments = array();

    /**
     * Array of before filtering callbacks.
     *
     * @var array
     */
    protected $before = array();

    /**
     * Filter name.
     *
     * @var string
     */
    protected $filter;

    /**
     * Resource being filtered.
     *
     * @var Basset\FilterableInterface
     */
    protected $resource;

    /**
     * Array of environments to apply filter on.
     *
     * @var array
     */
    protected $environments = array();

    /**
     * Group to restrict the filter to.
     *
     * @var string
     */
    protected $groupRestriction;

    /**
     * Create a new filter instance.
     *
     * @param  string  $filter
     * @return void
     */
    public function __construct($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Add a before filtering callback.
     *
     * @param  Closure  $callback
     * @return Basset\Filter\Filter
     */
    public function beforeFiltering(Closure $callback)
    {
        $this->before[] = $callback;

        return $this;
    }

    /**
     * Set the filters instantiation arguments
     *
     * @return Basset\Filter\Filter
     */
    public function setArguments()
    {
        $this->arguments = array_merge($this->arguments, func_get_args());

        return $this;
    }

    /**
     * Add an environment to apply the filter on.
     *
     * @param  string  $environment
     * @return Basset\Filter\Filter
     */
    public function onEnvironment($environment)
    {
        $this->environments[] = $environment;

        return $this;
    }

    /**
     * Add an array of environments to apply the filter on.
     *
     * @return Basset\Filter\Filter
     */
    public function onEnvironments()
    {
        $this->environments = array_merge($this->environments, func_get_args());

        return $this;
    }

    /**
     * Apply filter to only stylesheets.
     *
     * @return Basset\Filter\Filter
     */
    public function onlyStylesheets()
    {
        $this->groupRestriction = 'stylesheets';

        return $this;
    }

    /**
     * Apply filter to only javascripts.
     *
     * @return Basset\Filter\Filter
     */
    public function onlyJavascripts()
    {
        $this->groupRestriction = 'javascripts';

        return $this;
    }

    /**
     * Set the resource on the filter.
     * 
     * @param  Basset\Filter\FilterableInterface  $resource
     * @return Basset\Filter\Filter
     */
    public function setResource(FilterableInterface $resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get the parent resource.
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get the filter name.
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Get the filters group restriction.
     *
     * @return string
     */
    public function getGroupRestriction()
    {
        return $this->groupRestriction;
    }

    /**
     * Get the array of environments.
     *
     * @return array
     */
    public function getEnvironments()
    {
        return $this->environments;
    }

    /**
     * Get the filters instantiation arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Fire a callback passing in the filter instance as a parameter.
     * 
     * @param  Closure  $callback
     * @return Basset\Filter\Filter
     */
    public function fireCallback(Closure $callback = null)
    {
        if (is_callable($callback))
        {
            call_user_func($callback, $this);
        }

        return $this;
    }

    /**
     * Get the class name for the filter if it exists.
     *
     * @return string|bool
     */
    public function getClassName()
    {
        if (class_exists("Assetic\\Filter\\{$this->filter}"))
        {
            return "Assetic\\Filter\\{$this->filter}";
        }
        elseif (class_exists("Basset\\Filter\\{$this->filter}"))
        {
            return "Basset\\Filter\\{$this->filter}";
        }

        return false;
    }

    /**
     * Attempt to instantiate the filter if it exists.
     *
     * @return mixed
     */
    public function getInstance()
    {
        $class = $this->getClassName();

        if ($class)
        {
            $reflection = new ReflectionClass($class);

            // If no constructor is available on the filters class then we'll instantiate
            // the filter without passing in any arguments.
            if ( ! $reflection->getConstructor())
            {
                $instance = $reflection->newInstance();
            }
            else
            {
                $instance = $reflection->newInstanceArgs($this->arguments);
            }

            // Spin through each of the before filtering callbacks and fire each one. We'll
            // pass in an instance of the filter to the callback.
            foreach ($this->before as $callback)
            {
                if (is_callable($callback))
                {
                    call_user_func($callback, $instance);
                }
            }

            return $instance;
        }
    }

    /**
     * Dynamically chain uncallable methods to the parent resource.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->resource, $method), $parameters);
    }

}