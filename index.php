<?php
require_once './vendor/autoload.php';
require_once './phpx.php';

// view('scripts', '');
echo Html\div(
    [
        'class' => 'container',
    ],
    view("scripts", ''),
    view('app', Html\b('this is a argument'))
);
