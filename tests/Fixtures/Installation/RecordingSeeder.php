<?php

namespace Tests\Fixtures\Installation;

use Illuminate\Database\Seeder;

final class RecordingSeeder extends Seeder
{
    /**
     * @var list<class-string>
     */
    public static array $ran = [];

    public function run(): void
    {
        self::$ran[] = self::class;
    }
}
