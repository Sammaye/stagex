<?php

namespace glue\db;

use Glue;
use \glue\Component;

class Collection extends Component
{
    public $mongoCollection;

    public function __call($name, $parameters = array())
    {
        return call_user_func_array(array($this->mongoCollection, $name), $parameters);
    }
}