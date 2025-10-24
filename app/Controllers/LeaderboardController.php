<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Models\Leaderboard;

final class LeaderboardController extends Controller
{
    public function index(): string
    {
    $roles = ['student','alumni'];
        // Initial top-10 for server-rendered page
        $weekly = [];
        $monthly = [];
        $all = [];
        $coins = [
            'daily' => ['earned' => [], 'spent' => []],
            'weekly' => ['earned' => [], 'spent' => []],
            'monthly' => ['earned' => [], 'spent' => []],
            'all' => ['earned' => [], 'spent' => []],
        ];
        foreach ($roles as $r) {
            $weekly[$r] = Leaderboard::top($this->config, 'weekly', $r, 10);
            $monthly[$r] = Leaderboard::top($this->config, 'monthly', $r, 10);
            $all[$r] = Leaderboard::top($this->config, 'all', $r, 10);
            // Coins
            $coins['daily']['earned'][$r] = \Nexus\Models\Leaderboard::topCoins($this->config, 'daily', $r, 'earned', 10);
            $coins['daily']['spent'][$r] = \Nexus\Models\Leaderboard::topCoins($this->config, 'daily', $r, 'spent', 10);
            $coins['weekly']['earned'][$r] = \Nexus\Models\Leaderboard::topCoins($this->config, 'weekly', $r, 'earned', 10);
            $coins['weekly']['spent'][$r] = \Nexus\Models\Leaderboard::topCoins($this->config, 'weekly', $r, 'spent', 10);
            $coins['monthly']['earned'][$r] = \Nexus\Models\Leaderboard::topCoins($this->config, 'monthly', $r, 'earned', 10);
            $coins['monthly']['spent'][$r] = \Nexus\Models\Leaderboard::topCoins($this->config, 'monthly', $r, 'spent', 10);
            $coins['all']['earned'][$r] = \Nexus\Models\Leaderboard::topCoins($this->config, 'all', $r, 'earned', 10);
            $coins['all']['spent'][$r] = \Nexus\Models\Leaderboard::topCoins($this->config, 'all', $r, 'spent', 10);
        }
        return $this->view('leaderboard.index', compact('weekly','monthly','all','coins'));
    }

    // Optional JSON pagination endpoint: /leaderboards/page?type=rep&period=weekly&role=student&page=2&per_page=20
    public function page(): string
    {
        $type = (string)($_GET['type'] ?? 'rep');
        $period = (string)($_GET['period'] ?? 'weekly');
        $role = (string)($_GET['role'] ?? 'student');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(5, min(100, (int)($_GET['per_page'] ?? 20)));
        header('Content-Type: application/json');
        if ($type === 'coins') {
            $kind = (string)($_GET['kind'] ?? 'earned');
            $data = \Nexus\Models\Leaderboard::pageCoins($this->config, $period, $role, $kind, $page, $per);
            return json_encode(['ok' => true] + $data);
        }
        $data = \Nexus\Models\Leaderboard::page($this->config, $period, $role, $page, $per);
        return json_encode(['ok' => true] + $data);
    }
}
