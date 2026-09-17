<?php

namespace jeremykenedy\laravelusers\Test\Fixtures;

class Account extends User
{
    protected $connection = 'accounts';

    protected $table = 'accounts';
}
