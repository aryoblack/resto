<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminWebController extends Controller
{
    public function dashboard() { return view('admin.dashboard'); }
    public function orders() { return view('admin.orders'); }
    public function cashier() { return view('admin.cashier'); }
    public function menus() { return view('admin.menus'); }
    public function categories() { return view('admin.categories'); }
    public function tables() { return view('admin.tables'); }
    public function inventory() { return view('admin.inventory'); }
    public function suppliers() { return view('admin.suppliers'); }
    public function promos() { return view('admin.promos'); }
    public function reservations() { return view('admin.reservations'); }
    public function reports() { return view('admin.reports'); }
    public function staff() { return view('admin.staff'); }
    public function settings() { return view('admin.settings'); }
}
