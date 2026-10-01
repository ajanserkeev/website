<?php

namespace App\Services\Booking;

use DomainException;

/** A booking action that the current state does not allow; the message is shown to staff or the traveler. */
class InvalidBookingAction extends DomainException {}
