<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class InventoryController extends BaseController
{
    /**
    * addInventory
    */
    public function addInventory(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|min:2',            
            'name' => ['required', 'string', 'max:255',
                Rule::unique('inventory')->where(function ($query) use ($request) {
                    return $query->where('type', $request->input('type'));
                }),
            ],
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Add Inventory
        $Inventory = Inventory::create([
            ...$request->all(),
            'deleted' => 0,   
            'created_at' => now(),
        ]);        
        
        $success['inventory'] = $Inventory;

        return $this->sendResponse($success, 'Inventory created successfully.');
    }

    /**
    * updateInventory
    */
    public function updateInventory(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|integer|exists:inventory,id',
            'type' => 'required|string|min:2',            
            'name' => ['required', 'string', 'max:255',
                Rule::unique('inventory')->ignore($id)->where(function ($query) use ($request) {
                    return $query->where('type', $request->input('type'));
                }),
            ],
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Update Inventory
        $Inventory = Inventory::updateOrCreate(['id' => $id], [
            ...$request->all(),
            'updated_at' => now(),
        ]);

        $success['inventory'] = $Inventory;

        return $this->sendResponse($success, 'Inventory updated successfully.');
    }

    /**
     * getAllInventory
     */
    public function getAllInventory(Request $request){
        $inventory = Inventory::where(['deleted' => 0])->get();
        $success['inventoryList'] = $inventory;

        return $this->sendResponse($success, 'Inventory list successfully.');
    }

    /**
     * deleteInventory
     */
    public function deleteInventory(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id' => 'required|integer|exists:inventory,id',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());       
        }

        //Delete Inventory
        Inventory::where(['id' => $id])->delete();

        return $this->sendResponse(true, 'Inventory deleted successfully.');
    }
}
