<?php
namespace AppBundle\Exception;

class RemoveUserNotAllowedException extends \Exception implements AppBundleExceptionInterface
{
    protected $message;
    protected $code;

    public function __construct($message = 'Unauthorized attempt to remove user.', $code = 403) {
        $this->message = $message;
        $this->code = $code;
    }
}
