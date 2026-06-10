<?php

declare(strict_types=1);

namespace App\Security\Application;

enum Action: string
{
    case Autocomplete = 'autocomplete';

    case History = 'history';

    case List = 'list';

    case Edit = 'edit';

    case View = 'view';

    case Register = 'register';

    case Remove = 'remove';

    case Import = 'import';

    case Export = 'export';

    case Admin = 'admin';
}
