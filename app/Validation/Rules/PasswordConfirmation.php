<?php

namespace LoginApp\Validation\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as v;


class PasswordConfirmation extends AbstractRule
{
    public function validate($input)
    {
        return v::keyValue('password', 'equals', 'password_confirmation')->validate($_POST);
    }
}