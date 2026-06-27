<?php
declare(strict_types=1);

namespace App\Support;

use App\Infrastructure\Logging\DatabaseAuditLogger;
use App\Infrastructure\Persistence\PdoAbsenceRepository;
use App\Infrastructure\Persistence\PdoAccessLogRepository;
use App\Infrastructure\Persistence\PdoBadgeRepository;
use App\Infrastructure\Persistence\PdoEmergencyBadgeRepository;
use App\Infrastructure\Persistence\PdoFrequencyRepository;
use App\Infrastructure\Persistence\PdoGuardianPortalRepository;
use App\Infrastructure\Persistence\PdoGuardianRepository;
use App\Infrastructure\Persistence\PdoInviteRepository;
use App\Infrastructure\Persistence\PdoNotificationRepository;
use App\Infrastructure\Persistence\PdoPostInteractionRepository;
use App\Infrastructure\Persistence\PdoPostRepository;
use App\Infrastructure\Persistence\PdoQuickRegistrationRepository;
use App\Infrastructure\Persistence\PdoReportRepository;
use App\Infrastructure\Persistence\PdoSchoolAdminRepository;
use App\Infrastructure\Persistence\PdoStudentRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Services\AbsenceService;
use App\Services\AccessLookupService;
use App\Services\AccessService;
use App\Services\AuthService;
use App\Services\BadgeService;
use App\Services\EmergencyBadgeService;
use App\Services\FamilyOnboardingService;
use App\Services\FrequencyService;
use App\Services\GuardianPortalService;
use App\Services\InviteService;
use App\Services\NotificationService;
use App\Services\PostInteractionService;
use App\Services\PostService;
use App\Services\QuickRegistrationService;
use App\Services\ReportService;
use App\Services\SchoolAdminService;
use PDO;

final class ServiceFactory
{
    private function __construct() {}

    public static function audit(): DatabaseAuditLogger
    {
        return new DatabaseAuditLogger(self::pdo());
    }

    public static function auth(): AuthService
    {
        return new AuthService(new PdoUserRepository(self::pdo()), new PdoGuardianRepository(self::pdo()));
    }

    public static function posts(): PostService
    {
        return new PostService(new PdoPostRepository(self::pdo()), self::audit(), self::notifications());
    }

    public static function notifications(): NotificationService
    {
        return new NotificationService(new PdoNotificationRepository(self::pdo()));
    }

    public static function postInteractions(): PostInteractionService
    {
        return new PostInteractionService(new PdoPostInteractionRepository(self::pdo()), self::audit());
    }

    public static function invites(): InviteService
    {
        return new InviteService(
            new PdoInviteRepository(self::pdo()),
            new PdoGuardianRepository(self::pdo()),
            new PdoStudentRepository(self::pdo()),
            self::audit(),
            self::pdo(),
        );
    }

    public static function familyOnboarding(): FamilyOnboardingService
    {
        return new FamilyOnboardingService(new PdoInviteRepository(self::pdo()), self::audit());
    }

    public static function badges(): BadgeService
    {
        return new BadgeService(new PdoBadgeRepository(self::pdo()), self::audit());
    }

    public static function emergencyBadges(): EmergencyBadgeService
    {
        return new EmergencyBadgeService(new PdoEmergencyBadgeRepository(self::pdo()), self::audit());
    }

    public static function quickRegistrations(): QuickRegistrationService
    {
        return new QuickRegistrationService(new PdoQuickRegistrationRepository(self::pdo()), self::audit());
    }

    public static function absences(): AbsenceService
    {
        return new AbsenceService(new PdoAbsenceRepository(self::pdo()), self::audit());
    }

    public static function schoolAdmin(): SchoolAdminService
    {
        return new SchoolAdminService(new PdoSchoolAdminRepository(self::pdo()), self::audit());
    }

    public static function reports(): ReportService
    {
        return new ReportService(new PdoReportRepository(self::pdo()));
    }

    public static function frequency(): FrequencyService
    {
        return new FrequencyService(new PdoFrequencyRepository(self::pdo()));
    }

    public static function guardianPortal(): GuardianPortalService
    {
        return new GuardianPortalService(new PdoGuardianPortalRepository(self::pdo()));
    }

    public static function access(): AccessService
    {
        return new AccessService(new PdoGuardianRepository(self::pdo()), new PdoAccessLogRepository(self::pdo()), self::audit());
    }

    public static function accessLookup(): AccessLookupService
    {
        return new AccessLookupService(new PdoGuardianRepository(self::pdo()), new PdoStudentRepository(self::pdo()));
    }

    private static function pdo(): PDO
    {
        return \db();
    }
}
