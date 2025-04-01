<?php 

namespace App\Security\Application;

enum Action: string
{
    case List = 'list';

    case Edit = 'edit';

    case View = 'view';

    case Register = 'register';

    case Remove = 'remove';

    case Admin = 'admin';
}