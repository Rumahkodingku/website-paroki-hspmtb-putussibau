<?php

/*
|--------------------------------------------------------------------------
| Demo content
|--------------------------------------------------------------------------
|
| DatabaseSeeder calls DemoContentSeeder so that `migrate:fresh --seed` leaves
| a database with enough sample content to look at every screen. That is a
| deliberate divergence from D-14 (which emptied DatabaseSeeder of fabricated
| data) taken at the project owner's request, and it is recorded in D-53.
|
| The flag exists so the end-to-end suite stays deterministic. `app:e2e:prepare`
| runs `migrate:fresh --seed` against its own database, and a database full of
| sample articles, masses and profiles would change what the specs see. The
| command turns this flag off before migrating, so the E2E database is rebuilt
| bare while a developer's own `migrate:fresh --seed` still gets the demo.
|
| Set DEMO_SEED=false to skip it by hand.
|
| @see docs/DECISIONS.md D-53
*/

return [

    'enabled' => (bool) env('DEMO_SEED', true),

];
