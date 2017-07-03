<?php
namespace AppBundle\Exception;

interface AppBundleExceptionInterface
{
    public function __construct($message, $code);
}
