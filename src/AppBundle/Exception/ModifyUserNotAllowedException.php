<?php
namespace AppBundle\Exception;

class ModifyUserNotAllowedException extends \Exception implements AppBundleExceptionInterface
{
    protected $message;
    protected $code;

    public function __construct($message = 'Unauthorized attempt to modify user', $code = 403) {
        $this->message = $message;
        $this->code = $code;
    }
}
