<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetting;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(BusinessSetting::all());
    }
}
