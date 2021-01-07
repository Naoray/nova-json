<?php

namespace Naoray\NovaJson\Exceptions;

use Exception;

class AttributeCast extends Exception
{
    public static function notFoundFor($attribute)
    {
        return new static("No cast found for [{$attribute}] field.");
    }
}
