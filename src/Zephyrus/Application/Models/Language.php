<?php namespace Zephyrus\Application\Models;

use Zephyrus\Core\Entity\Entity;

class Language extends Entity
{
    public string $locale;
    public string $lang_code;
    public string $country_code;
    public string $flag_emoji;
    public string $country;
    public string $lang;
}
