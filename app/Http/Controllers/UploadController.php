<?php

namespace App\Http\Controllers;

use Faker\Provider\Base;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\API\BaseController;

class UploadController extends BaseController
{
    public function doUpload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');

        // Validate the file
        $request->validate([
            'file' => 'required|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:10240' // max 10MB
        ]);

        // Generate a unique name
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Store file in storage/app/public/uploads
        $path = $file->storeAs('public/uploads', $filename);

        // Return URL if needed
        return response()->json([
            'message' => 'File uploaded successfully',
            'url' => 'app/'.$path
        ]);
    }

    public function logoUpload(Request $request)
    {
        //
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');

        $request->validate([
            'file' => 'required|mimes:png|max:1024',
        ]);

        //
        $uploadPath = public_path('uploads');
        $filename = 'logo_temp.' . $file->getClientOriginalExtension();

        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $filename);        

        $success['logo'] = asset('uploads/' . $filename);

        return $this->sendResponse($success, 'Logo uploaded successfully.');
    }


    function getLogo()
    {
        $logoPath = public_path("uploads/logo_temp.png");

        if (file_exists($logoPath)) {
            $success['logo'] = asset('uploads/logo_temp.png');
            return $this->sendResponse($success, 'Logo retrieved successfully.');
        }

        return $this->sendError('Error validation', "Logo not found.");
    }

    public function instructionUpload(Request $request)
    {
        //
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');

        $request->validate([
            'file' => 'required|mimes:pdf|max:2048',
        ]);

        //
        $uploadPath = public_path('uploads/instructions');
        $filename = 'patient_instructions.' . $file->getClientOriginalExtension();

        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $filename);        

        $success['instruction'] = asset('uploads/instructions/' . $filename);

        return $this->sendResponse($success, 'Instructions uploaded successfully.');
    }

    function getInstruction()
    {
        $instructionsPath = public_path("uploads/instructions/patient_instructions.pdf");

        if (file_exists($instructionsPath)) {
            $success['instruction'] = asset('uploads/instructions/patient_instructions.pdf');
            return $this->sendResponse($success, 'Instructions retrieved successfully.');
        }

        return $this->sendError('Error validation', "Instructions not found.");
    }
}
