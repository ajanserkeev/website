<?php

use Illuminate\Support\Facades\Schedule;

// Booking timers: expiry after 48 h, reminders, completion, review requests (step 4.9).
Schedule::command('bookings:tick')->everyFifteenMinutes()->withoutOverlapping();
