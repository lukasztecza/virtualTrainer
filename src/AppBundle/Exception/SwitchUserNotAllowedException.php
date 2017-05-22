<?php
namespace AppBundle\Exception;

class SwitchUserNotAllowedException extends \Exception implements AppBundleExceptionInterface
{
    protected $message;
    protected $code;

    public function __construct($message = 'Attempt to impersonate user which is not ROLE_USER only', $code = 403) {
        $this->message = $message;
        $this->code = $code;
    }
}
