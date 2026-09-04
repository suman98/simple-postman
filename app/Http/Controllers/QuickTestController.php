<?php

namespace App\Http\Controllers;

class QuickTestController extends Controller
{
    public function __invoke()
    {
        return view('quick-test');
    }
}
