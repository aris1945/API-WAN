<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sa;
use Illuminate\Http\Request;

class SaController extends Controller
{
    public function index()
    {
        // Ambil semua data SA
        $data = Sa::all(); 
        
        return response()->json([
            'status' => true,
            'message' => 'List Data SA',
            'data' => $data
        ]);
    }
}