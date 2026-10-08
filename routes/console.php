<?php

/*
|--------------------------------------------------------------------------
| Console routes
|--------------------------------------------------------------------------
|
| Empty on purpose.
|
| Phase 01 section 25 asks for a scheduler foundation and nothing more, so the
| one scheduled task this phase has is queue housekeeping and it lives in
| bootstrap/app.php under withSchedule(). That location is the Laravel 11+
| answer for applications that would rather keep this file for command
| definitions only, which is what bootstrap/app.php::withRouting(commands:)
| already assumed when it pointed here.
|
| Two things that belong in this file when Phase 02 arrives:
|
| 1. PublishScheduledPosts, the business command roadmap section 25 defers
|    explicitly. It must not be written during Phase 01.
| 2. Anything else scheduled by a business module, so the schedule stays
|    reviewable in one place instead of being split across two files.
|
| The starter `inspire` command was removed in P12. It came from the Laravel
| skeleton, is referenced by nothing, and left behind an artifact that read
| as though this project had a console surface it does not have.
|
| @see docs/DECISIONS.md D-26
|
*/
