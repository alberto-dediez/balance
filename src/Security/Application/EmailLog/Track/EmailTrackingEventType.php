<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Track;

enum EmailTrackingEventType: string
{
    case Delivered = 'delivered';
    case Deferred = 'deferred';
    case Bounced = 'bounce';
    case Dropped = 'dropped';
    case Opened = 'open';
    case Clicked = 'click';
    case SpamReported = 'spamreport';
}
