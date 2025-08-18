<?php

use Lava83\DddFoundation\Domain\Shared\ValueObjects\Communication\Phonenumber;

require_once __DIR__.'/vendor/autoload.php';

$phonenumber = new Phonenumber('0049123456789');
$phonenumber2 = new Phonenumber('+49 0123456789');
