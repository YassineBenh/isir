<?php

namespace App\Enums;

enum DestinationType: string
{
    case Slack = 'slack';
    case Discord = 'discord';
    case Email = 'email';
}
