<?php
namespace App\Lib\Parser;

abstract class Parser
{
    protected $request;

    public abstract function parse(): void;
}