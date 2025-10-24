<?php
declare(strict_types=1);

use Nexus\Core\Config;
use Nexus\Core\Router;
use Nexus\Helpers\Schema;

$composerAutoload = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Fallback PSR-4 autoloader if Composer isn't used yet
spl_autoload_register(function ($class) {
    if (strpos($class, 'Nexus\\') !== 0) {
        return;
    }
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . str_replace('Nexus\\', '', $class) . '.php';
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
    if (file_exists($path)) {
        require_once $path;
    }
});

// Start secure session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Load config
$config = Config::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config');
// Expose config for view helpers that may need it (e.g., Gate checks in layout)
$GLOBALS['config'] = $config;
// Detect schema v2 once at boot and cache in a global for simple access in views/controllers
try {
    $GLOBALS['schema_v2'] = Schema::isV2($config);
} catch (\Throwable $e) {
    $GLOBALS['schema_v2'] = false;
}

// Create router and register routes
$router = new Router($config);

// Default routes
$router->get('/', 'HomeController@index');
$router->get('/health', function () { echo 'OK'; });

// Auth routes
$router->get('/auth/login', 'AuthController@loginForm');
$router->post('/auth/login', 'AuthController@login');
$router->get('/auth/register', 'AuthController@registerForm');
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/logout', 'AuthController@logout');
$router->get('/auth/verify', 'AuthController@verify');

// Profile routes
$router->get('/profile', 'ProfileController@show');
$router->get('/profile/edit', 'ProfileController@editForm');
$router->post('/profile/edit', 'ProfileController@edit');
// Remove current CV (owner/admin)
$router->post('/profile/remove-cv', 'ProfileController@removeCv');
// Toggle individual visibility flags (owner/admin only)
$router->post('/profile/visibility', 'ProfileController@toggleVisibility');
// Public, read-only profile view
$router->get('/u/{id}', 'ProfileController@public');

// Wallet routes
$router->get('/wallet', 'WalletController@index');

// Leaderboards
$router->get('/leaderboards', 'LeaderboardController@index');
$router->get('/leaderboards/page', 'LeaderboardController@page');
$router->get('/search', 'SearchController@index');
$router->get('/people', 'PeopleController@index');
$router->get('/forum', 'ForumController@index');
$router->get('/forum/create', 'ForumController@create');
$router->post('/forum/create', 'ForumController@create');
$router->get('/forum/pending', 'ForumController@pending');
$router->post('/forum/post/{id}/approve', 'ForumController@approve');
$router->post('/forum/post/{id}/reject', 'ForumController@reject');
 $router->post('/forum/post/bulk', 'ForumController@bulkModerate');
$router->get('/forum/category/{id}', 'ForumController@category');
$router->get('/forum/post/{id}', 'ForumController@show');
$router->post('/forum/post/{id}/answer', 'ForumController@answer');
$router->post('/forum/post/{id}/vote', 'ForumController@vote');
$router->post('/forum/answer/{id}/best', 'ForumController@bestAnswer');
$router->post('/forum/post/{id}/delete', 'ForumController@delete');
$router->post('/forum/seen', 'ForumController@markSeen');
$router->get('/mentorship', 'MentorshipController@index');
$router->get('/mentorship/create', 'MentorshipController@create');
$router->post('/mentorship/create', 'MentorshipController@create');
$router->get('/mentorship/my-listings', 'MentorshipController@myListings');
$router->get('/mentorship/listing/{id}/edit', 'MentorshipController@edit');
$router->post('/mentorship/listing/{id}/edit', 'MentorshipController@edit');
$router->post('/mentorship/listing/{id}/delete', 'MentorshipController@delete');
$router->get('/mentorship/listing/{id}', 'MentorshipController@show');
$router->get('/mentorship/listing/{id}/request', 'MentorshipController@request');
$router->post('/mentorship/listing/{id}/request', 'MentorshipController@request');
$router->get('/mentorship/requests/mine', 'MentorshipController@myRequests');
$router->get('/mentorship/listing/{id}/requests', 'MentorshipController@listingRequests');
$router->post('/mentorship/request/{id}/accept', 'MentorshipController@acceptRequest');
$router->post('/mentorship/request/{id}/decline', 'MentorshipController@declineRequest');
$router->post('/mentorship/request/{id}/boost', 'MentorshipController@boostRequest');
$router->post('/mentorship/request/{id}/reserve', 'MentorshipController@reserveRequest');
$router->post('/mentorship/request/{id}/release', 'MentorshipController@releaseReservation');
$router->post('/mentorship/request/{id}/extend', 'MentorshipController@extendReservation');
$router->get('/mentorship/request/{id}/schedule', 'MentorshipController@schedule');
$router->post('/mentorship/request/{id}/schedule', 'MentorshipController@schedule');
$router->post('/mentorship/request/{id}/chat', 'MentorshipController@chat');
$router->post('/mentorship/session/{id}/complete', 'MentorshipController@completeSession');
$router->get('/mentorship/session/{id}/feedback', 'MentorshipController@feedback');
$router->post('/mentorship/session/{id}/feedback', 'MentorshipController@feedback');
// Mentorship cancellation
$router->post('/mentorship/request/{id}/cancel', 'MentorshipController@requestCancellation');
$router->post('/admin/mentorship/cancellations/{id}/approve', 'MentorshipController@approveCancellation');
$router->post('/admin/mentorship/cancellations/{id}/reject', 'MentorshipController@rejectCancellation');
$router->post('/admin/mentorship/request/{id}/cancel', 'MentorshipController@adminCancel');
// Admin force delete listing
$router->post('/admin/mentorship/listing/{id}/force-delete', 'MentorshipController@adminForceDelete');

// Dev-only maintenance endpoints (protect with maintenance_key)
$router->get('/_maint/seed', 'MaintenanceController@seed');
$router->get('/_maint/rebuild-leaderboards', 'MaintenanceController@rebuildLeaderboards');
$router->get('/_maint/reset-free-requests', 'MaintenanceController@resetFreeRequests');
$router->get('/_maint/mentorship-auto-complete', 'MaintenanceController@mentorshipAutoComplete');

// Recruitment (Jobs)
$router->get('/jobs', 'JobsController@index');
$router->get('/jobs/create', 'JobsController@create');
$router->post('/jobs/create', 'JobsController@create');
$router->get('/jobs/{id}', 'JobsController@show');
$router->post('/jobs/{id}/apply', 'JobsController@apply');
$router->post('/jobs/{id}/moderate', 'JobsController@moderate');
$router->post('/jobs/{id}/toggle', 'JobsController@toggleActive');
$router->post('/jobs/{id}/delete', 'JobsController@delete');
$router->get('/jobs/my-listings', 'JobsController@myListings');
$router->get('/jobs/listing/{id}/applications', 'JobsController@listingApplications');
$router->get('/applications/mine', 'JobsController@myApplications');
$router->get('/applications/{id}', 'JobsController@application');
$router->post('/applications/{id}/notes', 'JobsController@addNote');
$router->get('/applications/{id}/schedule', 'JobsController@scheduleInterview');
$router->post('/applications/{id}/schedule', 'JobsController@scheduleInterview');
$router->post('/applications/{id}/status', 'JobsController@updateApplicationStatus');
$router->get('/jobs/{id}/referrals', 'JobsController@referralsForJob');
$router->get('/jobs/{id}/refer', 'JobsController@refer');
$router->post('/jobs/{id}/refer', 'JobsController@refer');
$router->get('/referrals/mine', 'JobsController@myReferrals');

// References (mentor endorsements)
$router->get('/references/mine', 'ReferencesController@mine');
$router->get('/references/create', 'ReferencesController@create');
$router->post('/references/create', 'ReferencesController@create');
$router->post('/references/{id}/revoke', 'ReferencesController@revoke');
$router->post('/references/{id}/delete', 'ReferencesController@delete');

// Events & Announcements & Notifications
$router->get('/events', 'EventsController@index');
$router->get('/events/create', 'EventsController@create');
$router->post('/events/create', 'EventsController@create');
$router->get('/events/{id}', 'EventsController@show');
$router->post('/events/{id}/register', 'EventsController@register');
$router->get('/announcements', 'AnnouncementsController@index');
$router->get('/announcements/create', 'AnnouncementsController@create');
$router->post('/announcements/create', 'AnnouncementsController@create');
$router->get('/notifications', 'NotificationsController@index');
$router->post('/notifications/{id}/read', 'NotificationsController@markRead');
$router->post('/notifications/mark-all-read', 'NotificationsController@markAllRead');
$router->get('/recommendations/mine', 'RecommendationsController@my');
$router->get('/recommendations/create', 'RecommendationsController@create');
$router->post('/recommendations/create', 'RecommendationsController@create');
$router->get('/recommendations/inbox', 'RecommendationsController@inbox');
$router->get('/recommendations/{id}', 'RecommendationsController@show');
$router->post('/recommendations/{id}/accept', 'RecommendationsController@accept');
$router->post('/recommendations/{id}/reject', 'RecommendationsController@reject');
$router->post('/recommendations/{id}/revoke', 'RecommendationsController@revoke');
// Reporting (Phase 16)
$router->get('/report', 'ReportsController@create');
$router->post('/report', 'ReportsController@store');

// Messages (Phase 9)
$router->get('/messages', 'MessagesController@inbox');
$router->get('/messages/new', 'MessagesController@new');
$router->post('/messages/new', 'MessagesController@new');
$router->get('/messages/{id}', 'MessagesController@show');
$router->post('/messages/{id}/send', 'MessagesController@send');
$router->get('/messages/{id}/poll', 'MessagesController@poll');
$router->post('/messages/{id}/typing', 'MessagesController@typing');
$router->get('/messages/{id}/stream', 'MessagesController@stream');
$router->get('/messages/{id}/history', 'MessagesController@history');
$router->get('/messages/{id}/seen', 'MessagesController@seen');
$router->get('/messages/{id}/reported', 'MessagesController@reported');
$router->post('/messages/{id}/message/{messageId}/delete', 'MessagesController@delete');
$router->post('/messages/toggle-recruiter-replies', 'MessagesController@toggleRecruiterReplies');

// Admin (Phase 10)
$router->get('/admin', 'AdminController@index');
$router->get('/admin/users', 'AdminController@users');
$router->post('/admin/users/toggle', 'AdminController@toggleUser');
$router->post('/admin/users/change-role', 'AdminController@changeRole');
$router->post('/admin/users/wallet-adjust', 'AdminController@adjustWallet');
$router->get('/admin/permissions', 'AdminController@permissions');
$router->post('/admin/permissions/grant', 'AdminController@grantRolePermission');
$router->post('/admin/permissions/revoke', 'AdminController@revokeRolePermission');
$router->get('/admin/reports', 'AdminController@reports');
$router->get('/admin/reports/{id}', 'AdminController@reportDetail');
$router->post('/admin/reports/update', 'AdminController@updateReport');
$router->post('/admin/reports/{id}/attach', 'AdminController@attachToReport');
$router->get('/admin/audit-logs', 'AdminController@auditLogs');
$router->get('/admin/restrictions', 'AdminController@restrictions');
$router->post('/admin/restrictions/add', 'AdminController@addRestriction');
$router->post('/admin/restrictions/remove', 'AdminController@removeRestriction');
$router->get('/admin/cancellations', 'AdminController@cancellations');
// Quick suspend/lift user moderation tools
$router->post('/admin/users/suspend', 'AdminController@suspendUser');
$router->post('/admin/users/lift', 'AdminController@liftSuspension');
// User-specific restriction history
$router->get('/admin/users/{id}/restrictions', 'AdminController@userRestrictions');
// Admin Exports (Phase 12)
$router->get('/admin/exports', 'AdminController@exports');
$router->post('/admin/exports', 'AdminController@export');
// Admin Settings (Phase 13)
$router->get('/admin/settings', 'AdminController@settings');
$router->post('/admin/settings/update', 'AdminController@updateSetting');
$router->post('/admin/settings/delete', 'AdminController@deleteSetting');

// Stage 4: Field Locks
$router->get('/admin/locks', 'AdminController@locks');
$router->get('/admin/users/{id}/locks', 'AdminController@userLocks');
$router->post('/admin/locks/add', 'AdminController@addLock');
$router->post('/admin/locks/remove', 'AdminController@removeLock');

return [
    'config' => $config,
    'router' => $router,
];
