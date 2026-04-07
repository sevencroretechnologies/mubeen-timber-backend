<?php

namespace App\Http\Controllers;

use App\Models\EstimationAttachment;
use App\Models\Estimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;

class EstimationAttachmentController extends Controller
{
    /**
     * Display a listing of attachments for an estimation.
     */
    public function index(Request $request)
    {
        $request->validate([
            'estimation_id' => 'required|exists:estimations,id',
        ]);

        $attachments = EstimationAttachment::where('estimation_id', $request->estimation_id)->get();

        return response()->json([
            'success' => true,
            'data' => $attachments
        ]);
    }

    /**
     * Store a newly created attachment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'estimation_id' => 'required|exists:estimations,id',
            'org_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $attachment = new EstimationAttachment();
            $attachment->estimation_id = $request->estimation_id;
            $attachment->org_id = $request->org_id;
            $attachment->company_id = $request->company_id;
            $attachment->description = $request->description;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                
                // Store in public/uploads/estimations
                $path = $file->move(public_path('uploads/estimations'), $filename);
                $attachment->image = 'uploads/estimations/' . $filename;
            }

            $attachment->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attachment uploaded successfully',
                'data' => $attachment
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified attachment.
     */
    public function show($id)
    {
        $attachment = EstimationAttachment::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $attachment
        ]);
    }

    /**
     * Remove the specified attachment from storage.
     */
    public function destroy($id)
    {
        try {
            $attachment = EstimationAttachment::findOrFail($id);
            
            // Note: We are using soft deletes, so the file remains on disk 
            // unless we explicitly want to delete it here. 
            // Usually for soft deletes, we keep the file.
            
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
