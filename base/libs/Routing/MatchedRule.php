<?php
namespace Vendimia\Routing;

use Vendimia\DataContainer;

class MatchedRule extends DataContainer
{
    /**
     * False if there is no rule matched.
     * TODO: Remove this when required PHP version goes up to 7.1
     */
    public $matched = true;

    /**
     * Target information
     */
    public $target;

    /**
     * Target application, used for some default name resources
     */
    public $target_app;

    /**
     * Target short name in the form [app, controller]
     */
    public $target_name;

    /**
     * Target type: class, callable, legacy, view
     */
    public $target_type;

    /**
     * Target default resource names
     */
    public $target_resources;

    /**
     * Arguments from matched rule and URL
     */
    public $args;

    /**
     * Matched rule
     */
    public $rule;
}
