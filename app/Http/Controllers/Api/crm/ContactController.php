<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Exception;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Contact::query();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('middle_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%")
                      ->orWhere('designation', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%")
                      ->orWhereHas('phones', function ($q2) use ($search) {
                          $q2->where('phone_no', 'like', "%{$search}%");
                      })
                      ->orWhereHas('emails', function ($q2) use ($search) {
                          $q2->where('email', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('gender')) {
                $query->where('gender', $request->gender);
            }

            $perPage = $request->query('per_page', 15);
            $queryParameters = Arr::except($request->query(), ['user_id']);

            $data = $query->orderBy('first_name')
                ->paginate($perPage)
                ->appends($queryParameters);

            return response()->json([
                'message' => 'All contacts retrieved successfully.',
                'data' => $data,
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'total_pages' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total_items' => $data->total(),
                    'next_page_url' => $data->nextPageUrl(),
                    'prev_page_url' => $data->previousPageUrl(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve contacts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'salutation'          => 'nullable|string|max:50',
            'first_name'          => 'required|string|max:255',
            'middle_name'         => 'nullable|string|max:255',
            'last_name'           => 'nullable|string|max:255',
            'designation'         => 'nullable|string|max:255',
            'gender'              => 'nullable|string|max:50',
            'company_name'        => 'nullable|string|max:255',
            'address'             => 'nullable|string|max:1000',
            'status'              => 'nullable|string|max:50',
            'phones'              => 'nullable|array',
            'phones.*.phone_no'   => 'nullable|string|max:50',
            'phones.*.is_primary' => 'nullable|boolean',
            'emails'              => 'nullable|array',
            'emails.*.email'      => 'nullable|email|max:255',
            'emails.*.is_primary' => 'nullable|boolean',
        ]);

        $contact = DB::transaction(function () use ($validated) {
            $contactData = collect($validated)->except(['phones', 'emails'])->toArray();
            $contact = Contact::create($contactData);

            // Save phones
            if (!empty($validated['phones'])) {
                foreach ($validated['phones'] as $phone) {
                    if (empty($phone['phone_no'])) continue;
                    $contact->phones()->create([
                        'phone_no'   => $phone['phone_no'],
                        'is_primary' => $phone['is_primary'] ?? false,
                    ]);
                }
            }

            // Save emails
            if (!empty($validated['emails'])) {
                foreach ($validated['emails'] as $email) {
                    if (empty($email['email'])) continue;
                    $contact->emails()->create([
                        'email'      => $email['email'],
                        'is_primary' => $email['is_primary'] ?? false,
                    ]);
                }
            }

            return $contact;
        });

        return response()->json($contact->fresh(), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(Contact::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);

        $validated = $request->validate([
            'salutation'          => 'nullable|string|max:50',
            'first_name'          => 'sometimes|required|string|max:255',
            'middle_name'         => 'nullable|string|max:255',
            'last_name'           => 'nullable|string|max:255',
            'designation'         => 'nullable|string|max:255',
            'gender'              => 'nullable|string|max:50',
            'company_name'        => 'nullable|string|max:255',
            'address'             => 'nullable|string|max:1000',
            'status'              => 'nullable|string|max:50',
            'phones'              => 'nullable|array',
            'phones.*.phone_no'   => 'nullable|string|max:50',
            'phones.*.is_primary' => 'nullable|boolean',
            'emails'              => 'nullable|array',
            'emails.*.email'      => 'nullable|email|max:255',
            'emails.*.is_primary' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($contact, $validated) {
            $contactData = collect($validated)->except(['phones', 'emails'])->toArray();
            $contact->update($contactData);

            // Replace phones
            if (array_key_exists('phones', $validated)) {
                $contact->phones()->delete();
                if (!empty($validated['phones'])) {
                    foreach ($validated['phones'] as $phone) {
                        if (empty($phone['phone_no'])) continue;
                        $contact->phones()->create([
                            'phone_no'   => $phone['phone_no'],
                            'is_primary' => $phone['is_primary'] ?? false,
                        ]);
                    }
                }
            }

            // Replace emails
            if (array_key_exists('emails', $validated)) {
                $contact->emails()->delete();
                if (!empty($validated['emails'])) {
                    foreach ($validated['emails'] as $email) {
                        if (empty($email['email'])) continue;
                        $contact->emails()->create([
                            'email'      => $email['email'],
                            'is_primary' => $email['is_primary'] ?? false,
                        ]);
                    }
                }
            }
        });

        return response()->json($contact->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        Contact::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
