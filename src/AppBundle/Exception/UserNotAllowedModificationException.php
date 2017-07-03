<?php
namespace AppBundle\Exception;

class UserNotAllowedModificationException extends \Exception implements AppBundleExceptionInterface
{
    protected $message;
    protected $code;

    public function __construct($message = 'Attempt to modify admin user whithout permission', $code = 403) {
        $this->message = $message;
        $this->code = $code;
    }
}
