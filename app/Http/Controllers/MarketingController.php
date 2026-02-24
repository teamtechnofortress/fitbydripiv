<?php

namespace App\Http\Controllers;

use App\Models\TextCampaign;
use App\Models\EmailCampaign;
use App\Models\SpecialPromo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use App\Mail\SendEmail;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\Mail;

class MarketingController extends BaseController
{
    /**
     * Save Text Campaign  Data
     */
    public function saveTextCampaign(Request $request){

        $validator = Validator::make($request->all(), [
            'staff_id'      => 'required|integer|exists:users,id',//
            'title'         => 'required|string',
            'message'      => 'required|string',
            // 'company_signature' => 'string',
            'include_signature' => 'required',
            'send_date'         => 'required',
            'send_time'         => 'required',
            'texts_per_send'    => 'required',
            'patient_start'     => 'required',
            'patient_end'       => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        //Add Campaign
        TextCampaign::create([
            ...$request->all(),            
            'sent'      => false,
            'deleted' => 0,
        ]);

        $textCampaigns = TextCampaign::where('deleted', 0)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $success['textCampaigns'] = $textCampaigns;

        return $this->sendResponse($success, 'Your TextCampaign is scheduled successfully.');
    }

    /**
     * Save Email Campaign  Data
     */
    public function saveEmailCampaign(Request $request){

        $validator = Validator::make($request->all(), [
            'content'   => 'required|string',
            'title'     => 'required|string|unique:email_campaigns,title,NULL,id', 
            'send_date' => 'required',
            'send_time' => 'required',
            'texts_per_send' => 'required',
            'patient_start' => 'required',
            'patient_end' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        //Add Campaign
        EmailCampaign::create([
            ...$request->all(),
            'sent'      => false,
            'deleted' => 0,
        ]);

        $emailCampaigns = EmailCampaign::where('deleted', 0)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $success['emailCampaigns'] = $emailCampaigns;

        return $this->sendResponse($success, 'Your EmailCampaign is scheduled successfully.');
    }

    /**
     * Save Special Promo/Reward Campaign  Data
     */
    public function saveSpecialPromo(Request $request){

        $validator = Validator::make($request->all(), [
            'promoTitle' => 'required|string',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        //Add Staff
        SpecialPromo::create([
            ...$request->all(),
            'deleted' => 0,
        ]);

        $specialPromos = SpecialPromo::where('deleted', 0)->get();

        $success['specialPromos'] = $specialPromos;

        return $this->sendResponse($success, 'Your Special Promo is saved successfully.');
    }

    /**
     * getSpecialPromos
     */
    public function getSpecialPromos(Request $request){
        $validator = Validator::make($request->all(), [

        ]);
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $specialPromos = SpecialPromo::where('deleted', 0)
                                        ->get();

        $success['specialPromos'] = $specialPromos;
        return $this->sendResponse($success, 'Successfully get specialPromos');
    }

    /**
     * getEmailCampaigns
     */
    public function getEmailCampaigns(Request $request){
        $validator = Validator::make($request->all(), [

        ]);
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $emailCampaigns = EmailCampaign::where('deleted', 0)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $success['emailCampaigns'] = $emailCampaigns;
        return $this->sendResponse($success, 'Successfully get emailCampaigns');
    }

    /**
     * getTextCampaigns
     */
    public function getTextCampaigns(Request $request){
        $validator = Validator::make($request->all(), [

        ]);
        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $textCampaigns = TextCampaign::where('deleted', 0)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $success['textCampaigns'] = $textCampaigns;
        return $this->sendResponse($success, 'Successfully get textCampaigns');
    }


     /**
     * Update Promo  Data
     */
    public function updateSpecialPromo(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:special_promos,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $promoInfo = [
            ...$request->all()
        ];

        /*
        * ########### Update the Promo ################
        */
        SpecialPromo::updateOrCreate(['id' => $id], $promoInfo);

        $specialPromos = SpecialPromo::where('deleted', 0)
                                        ->get();

        $success['specialPromos'] = $specialPromos;

        return $this->sendResponse($success, "Successfully Updated Promo Info.");
    }

    /**
     * deletePromo
     */
    public function removeSpecialPromo(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:special_promos,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        SpecialPromo::where(['id' => $id])->update(['deleted' => 1]);

        $specialPromos = SpecialPromo::where('deleted', 0)
                                        ->get();

        $success['specialPromos'] = $specialPromos;
        return $this->sendResponse($success, "Successfully Deleted SpecialPromo.");
    }

    /**
     * removeTextCampaign
     */
    public function removeTextCampaign(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:text_campaigns,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        TextCampaign::where(['id' => $id])->update(['deleted' => 1]);

        $textCampaigns = TextCampaign::where('deleted', 0)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $success['textCampaigns'] = $textCampaigns;
        return $this->sendResponse($success, "Successfully Deleted textCampaign.");
    }

    /**
     * deletePromo
     */
    public function removeEmailCampaign(Request $request, $id){
        $validator = Validator::make([...$request->all(), 'id' => $id], [
            'id'   => 'required|exists:email_campaigns,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        EmailCampaign::where(['id' => $id])->update(['deleted' => 1]);

        $emailCampaigns = EmailCampaign::where('deleted', 0)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $success['emailCampaigns'] = $emailCampaigns;
        return $this->sendResponse($success, "Successfully Deleted emailCampaign.");
    }
}
