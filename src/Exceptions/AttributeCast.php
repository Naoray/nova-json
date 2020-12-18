<?php

namespace Naoray\NovaJson\Exceptions;

use Exception;

class AttributeCast extends Exception
{
    public static function notFound()
    {
        return new static('All fields using the JSON field need to be casted to a json castable type.');
    }
}
