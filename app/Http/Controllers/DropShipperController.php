<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\DropShiper;

class DropShipperController extends Controller
{
        public function createDropShiper(Request $request)
        {
            $validatedData = $request->validate([
                'customer_id' => 'required|exists:users,id',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:drop_shipers,email',
                'phone' => 'nullable|string|max:20',
            ]);
        
            $dropshipper = DropShiper::create([
                'customer_id' => $validatedData['customer_id'],
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'] ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Drop shipper created successfully.',
                'data' => $dropshipper
            ], 201);
        }
        public function index()
        {
            // List all drop shippers
        }
    
        public function store(Request $request)
        {
            
        }
    
        public function show($id)
        {
            // Show details of a specific drop shipper
        }
    
        public function update(Request $request, $id)
        {
            // Update a specific drop shipper
        }
    
        public function destroy($id)
        {
            // Delete a specific drop shipper
        }
}
