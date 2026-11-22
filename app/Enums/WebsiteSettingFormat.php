<?php

namespace App\Enums;

/**
 * The shape a website setting value must have, chosen by how the value is used.
 */
enum WebsiteSettingFormat
{
    /** Where the game client loads from; the client URL carries the login ticket. */
    case ClientUrl;

    /** Rendered as an href, src or CSS url(). */
    case Link;

    /** A file Atom reads and writes; empty turns the feature off. */
    case HotelFile;

    /** A folder Atom writes uploaded files into. */
    case HotelFolder;

    case Theme;

    case Host;

    case Port;

    case Toggle;

    case Rank;

    case Count;
}
