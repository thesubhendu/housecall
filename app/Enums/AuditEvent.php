<?php

namespace App\Enums;

enum AuditEvent: string
{
    case ReferralCreated = 'referral.created';
    case TriageStarted = 'triage.started';
    case TriageCompleted = 'triage.completed';
    case ReferralCancelled = 'referral.cancelled';
    case StatusChanged = 'status.changed';
}
