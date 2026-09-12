<?php

namespace App\Enums;

enum OccasionType: string
{
    case Birthday = 'birthday';
    case Anniversary = 'anniversary';
    case BhaiPhota = 'bhai-phota';
    case RakshaBandhan = 'raksha-bandhan';
    case Proposal = 'proposal';
    case Dating = 'dating';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Birthday => 'Birthday',
            self::Anniversary => 'Anniversary',
            self::BhaiPhota => 'Bhai Phota',
            self::RakshaBandhan => 'Raksha Bandhan',
            self::Proposal => 'Proposal',
            self::Dating => 'Dating',
        };
    }
}
