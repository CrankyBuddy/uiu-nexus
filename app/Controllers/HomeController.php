<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;

final class HomeController extends Controller
{
    public function index(): string
    {
        $brand = [
            'name' => 'UIU NEXUS',
            'primary' => '#f56726',
            'bg' => '#fffffe',
        ];
        return $this->view('home.index', compact('brand'));
    }
}
