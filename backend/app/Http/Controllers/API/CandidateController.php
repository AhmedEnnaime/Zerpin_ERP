<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CandidateController extends BaseController
{
    public function index()
    {
        $candidates = Candidate::with("recrutment")->get();
        return $this->sendResponse(CandidateResource::collection($candidates), 'Candidates retrieved successfully.', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fname' => 'required',
            'lname' => 'required',
            'birthday' => 'required|date',
            'cin' => 'required',
            'phone' => 'required|min:10',
            'email' => 'required|email',
            'cv' => 'required',
            'img' => 'required|image',
            'recrutment_id' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $image_path = $request->file('img')->store('image', 'public');
        $cv_path = $request->file('cv')->store('image', 'public');

        $candidate = Candidate::create([
            "fname" => $request->fname,
            "lname" => $request->lname,
            "birthday" => $request->birthday,
            "cin" => $request->cin,
            "phone" => $request->phone,
            "email" => $request->email,
            "cv" => $cv_path,
            "img" => $image_path,
            "recrutment_id" => $request->recrutment_id,
            "recrutment_state" => "EVALUATION",
        ]);

        return $this->sendResponse(new CandidateResource($candidate), 'Candidate added successfully.', 201);
    }

    public function updateState(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'recrutment_state' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $candidate = Candidate::find($id);

        if (!$candidate) {
            return $this->sendError('Candidate not found.');
        }

        $candidate->recrutment_state = $request->recrutment_state;

        if (Auth::user()->role == "ADMIN") {
            $candidate->save();
            return $this->sendResponse(new CandidateResource($candidate), 'Candidate updated successfully.', 200);
        } else {
            return $this->sendResponse([], 'Not allowed.', 404);
        }
    }


    public function destroy(Candidate $candidate)
    {
        if (Auth::user()->role == "ADMIN") {
            $candidate->delete();
            return $this->sendResponse([], 'Candidate deleted successfully.', 202);
        } else {
            return $this->sendResponse([], 'Not allowed.', 404);
        }
    }
}
