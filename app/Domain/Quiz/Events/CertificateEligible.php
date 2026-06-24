<?php

namespace App\Domain\Quiz\Events;

use App\Models\QuizResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a student achieves certificate eligibility.
 * Future-ready: attach listeners (certificate generation, email, etc.)
 */
class CertificateEligible
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly QuizResult $result) {}
}
