<?php

namespace App;

enum PostFlagStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Dismissed = 'dismissed';
}
