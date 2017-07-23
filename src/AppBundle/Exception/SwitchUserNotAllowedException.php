<?php
namespace AppBundle\Exception;

class SwitchUserNotAllowedException extends \Exception implements AppBundleExceptionInterface
{
    protected $message;
    protected $code;

    public function __construct($message = 'Unauthorized attempt to impersonate user.', $code = 403) {
        $this->message = $message;
        $this->code = $code;
    }
}
