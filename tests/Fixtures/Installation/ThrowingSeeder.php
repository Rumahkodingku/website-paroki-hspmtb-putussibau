<?php

namespace Tests\Fixtures\Installation;

use Illuminate\Database\Seeder;
use RuntimeException;

final class ThrowingSeeder extends Seeder
{
    public function run(): void
    {
        throw new RuntimeException('Seeder sengaja gagal untuk pengujian.');
    }
}
