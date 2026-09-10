<?php

namespace App\Enums;

enum UploadKind: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
}
