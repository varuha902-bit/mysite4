<?php

namespace WPEventGenius_Vendor\Spatie\IcalendarGenerator\Enums;

use WPEventGenius_Vendor\Spatie\Enum\Enum;
/**
 * @method static self tentative()
 * @method static self confirmed()
 * @method static self cancelled()
 */
class EventStatus extends Enum
{
    const MAP_VALUE = ['tentative' => 'TENTATIVE', 'confirmed' => 'CONFIRMED', 'cancelled' => 'CANCELLED'];
}
