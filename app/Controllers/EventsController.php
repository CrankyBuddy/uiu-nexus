<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Models\Event;
use Nexus\Models\Notification;

final class EventsController extends Controller
{
    public function index(): string
    {
        Auth::enforceAuth();
        $events = Event::listUpcoming($this->config, 20);
        return $this->view('events/index', ['events' => $events]);
    }

    public function show(int $id): string
    {
        Auth::enforceAuth();
        $event = Event::find($this->config, $id);
        if (!$event || !(bool)$event['is_active']) {
            http_response_code(404);
            echo 'Event not found';
            return '';
        }
        $regCount = Event::countRegistrations($this->config, $id);
        $isReg = false;
        $uid = Auth::id();
        if ($uid) { $isReg = Event::isRegistered($this->config, $id, $uid); }
        return $this->view('events/show', ['event' => $event, 'registered' => $isReg, 'regCount' => $regCount]);
    }

    public function create(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'admin') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $title = trim((string)($_POST['title'] ?? ''));
            $desc = trim((string)($_POST['description'] ?? ''));
            $etype = (string)($_POST['event_type'] ?? 'workshop');
            $allowedTypes = ['career_fair','hackathon','workshop','networking','seminar'];
            if (!in_array($etype, $allowedTypes, true)) { $etype = 'workshop'; }
            $edate = (string)($_POST['event_date'] ?? '');
            // Normalize HTML datetime-local (YYYY-MM-DDTHH:MM) to MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
            $edate = str_replace('T', ' ', $edate);
            if ($edate !== '' && strlen($edate) === 16) { $edate .= ':00'; }
            $loc = trim((string)($_POST['location'] ?? ''));
            $venue = trim((string)($_POST['venue_details'] ?? ''));
            $maxp = ($_POST['max_participants'] ?? '') !== '' ? (int)$_POST['max_participants'] : null;
            if ($title === '' || $edate === '' || $etype === '') {
                return $this->view('events/create', ['error' => 'Title, type and date are required.']);
            }
            $id = Event::create($this->config, $title, $desc, $etype, $edate, $loc ?: null, $venue ?: null, (int)$user['user_id'], $maxp, true);
            $this->redirect('/events/' . $id);
            return '';
        }
        return $this->view('events/create');
    }

    public function register(int $id): string
    {
        Auth::enforceAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $uid = Auth::id();
        if (!$uid) { http_response_code(401); echo 'Unauthorized'; return ''; }
        $ev = Event::find($this->config, $id);
        if (!$ev || !(bool)$ev['is_active']) { http_response_code(404); echo 'Event not found'; return ''; }
        $ok = Event::register($this->config, $id, $uid);
        if ($ok) {
            // Notify organizer if set
            if (!empty($ev['organizer_id'])) {
                Notification::send($this->config, (int)$ev['organizer_id'], 'New Event Registration', 'Someone registered for your event: ' . (string)$ev['title'], 'event_registration', 'event', $id, '/events/' . $id);
            }
            $this->redirect('/events/' . $id);
            return '';
        }
        http_response_code(400);
        echo 'Registration failed (maybe full or already registered)';
        return '';
    }
}
