<?php

namespace App\Services;

class WishmeService
{
    /**
     * @return array{product: string, brand: string, status: string}
     */
    public function health(): array
    {
        return [
            'product' => (string) config('wishme.name'),
            'brand' => (string) config('wishme.brand'),
            'status' => 'ok',
        ];
    }
}
