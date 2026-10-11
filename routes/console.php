<?php

/*
|--------------------------------------------------------------------------
| Console routes
|--------------------------------------------------------------------------
|
| Empty on purpose, and the first scheduled task changed that.
|
| Phase 01 section 25 asked for a scheduler foundation and nothing more, so the
| one scheduled task that phase had — queue housekeeping — lives in
| bootstrap/app.php under withSchedule(). That is the Laravel 11+ answer for
| applications that would rather keep this file for command definitions only,
| which is what bootstrap/app.php::withRouting(commands:) already assumed when
| it pointed here.
|
| PublishScheduledPosts was the first thing this file was going to hold, and it
| does not: the command itself is an ordinary class in app/Console/Commands and
| the *schedule entry* that runs it sits beside queue:prune-failed in
| bootstrap/app.php. One place to read what runs and how often, which is the
| thing worth having. Anything else scheduled by a business module goes in the
| same place rather than here, for the same reason.
|
| The starter `inspire` command was removed in P12. It came from the Laravel
| skeleton, is referenced by nothing, and left behind an artifact that read as
| though this project had a console surface it does not have.
|
| @see docs/DECISIONS.md D-26
*/
