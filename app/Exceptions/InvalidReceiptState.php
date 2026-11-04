<?php

namespace App\Exceptions;

use RuntimeException;

/** Raised when a receipt operation is not allowed in the receipt's or transfer's current state. */
class InvalidReceiptState extends RuntimeException {}
