<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CustomizationController extends Controller
{
    public function __invoke(): View
    {
        return view('customization.index');
    }
}
