<?php

namespace Dms\Core\Language;

/**
 * The message class.
 * 
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class Message
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string[];
     */
    private $parameters;

    public function __construct($id, array $parameters = [])
    {
        $this->id         = $id;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string[] $parameters
     *
     * @return Message
     */
    public function withParameters(array $parameters)
    {
        return new self($this->id, $parameters + $this->parameters);
    }
}