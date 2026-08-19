<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ContactList;
use App\Models\ContactListLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ContactListController extends Controller
{
    /**
     * Tenant Guard
     */
    protected function authorizeTenant(ContactList $contactList): void
    {
        $user = auth()->user();
        if ($user && $contactList->organization_id !== null && $contactList->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized tenant access.');
        }
    }

    /**
     * List Contact Lists
     */
    public function index(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $query = ContactList::where(function ($q) use ($orgId) {
            $q->where('organization_id', $orgId);
            if ($orgId == 1) {
                $q->orWhereNull('organization_id');
            }
        });

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . trim($request->search) . '%');
        }

        $lists = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $lists,
        ]);
    }

    /**
     * Create Contact List
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'businesses' => 'nullable|array',
            'businesses.*' => 'exists:businesses,id',
            'filter_criteria' => 'nullable|array',
        ]);

        $contactList = DB::transaction(function () use ($request) {
            $list = ContactList::create([
                'organization_id' => Auth::user()?->organization_id ?? 1,
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => Auth::id(),
            ]);

            $businessesToAttach = collect();

            // 1. Directly passed business IDs
            if (!empty($request->businesses) && is_array($request->businesses)) {
                $businessesToAttach = Business::with('audit')->whereIn('id', $request->businesses)->get();
            }

            // 2. Filter criteria based business query
            if (!empty($request->filter_criteria) && is_array($request->filter_criteria)) {
                $criteria = $request->filter_criteria;
                $query = Business::with('audit')->whereNotNull('email')->where('email', '!=', '');

                if (!empty($criteria['search'])) {
                    $search = trim($criteria['search']);
                    $query->where(function ($q) use ($search) {
                        $q->where('business_name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%")
                            ->orWhere('website', 'LIKE', "%{$search}%");
                    });
                }

                if (!empty($criteria['country'])) {
                    $query->where('country', 'LIKE', '%' . trim($criteria['country']) . '%');
                }

                if (!empty($criteria['category'])) {
                    $query->where('category', 'LIKE', '%' . trim($criteria['category']) . '%');
                }

                if (!empty($criteria['has_website'])) {
                    if ($criteria['has_website'] === 'yes') {
                        $query->whereNotNull('website')->where('website', '!=', '-');
                    } elseif ($criteria['has_website'] === 'no') {
                        $query->where(function ($q) {
                            $q->whereNull('website')->orWhere('website', '-');
                        });
                    }
                }

                if (!empty($criteria['psi_filter'])) {
                    $query->whereHas('audit', function ($q) use ($criteria) {
                        switch ($criteria['psi_filter']) {
                            case 'less_30': $q->where('mobile_pagespeed', '>', 0)->where('mobile_pagespeed', '<', 30); break;
                            case 'less_50': $q->where('mobile_pagespeed', '>', 0)->where('mobile_pagespeed', '<', 50); break;
                            case 'less_70': $q->where('mobile_pagespeed', '>', 0)->where('mobile_pagespeed', '<', 70); break;
                            case 'less_90': $q->where('mobile_pagespeed', '>', 0)->where('mobile_pagespeed', '<', 90); break;
                            case 'between_50_89': $q->where('mobile_pagespeed', '>=', 50)->where('mobile_pagespeed', '<', 90); break;
                            case 'good_90': $q->where('mobile_pagespeed', '>=', 90); break;
                        }
                    });
                }

                if (!empty($criteria['has_screenshot'])) {
                    if ($criteria['has_screenshot'] === 'yes') {
                        $query->whereHas('audit', function ($q) {
                            $q->whereNotNull('mobile_screenshot_path');
                        });
                    }
                }

                $filteredCollection = $query->get();
                $businessesToAttach = $businessesToAttach->concat($filteredCollection)->unique('id');
            }

            if (!empty($businessesToAttach) && count($businessesToAttach) > 0) {
                $now = now();
                $rows = [];
                foreach ($businessesToAttach as $biz) {
                    $rows[] = [
                        'contact_list_id' => $list->id,
                        'business_id' => $biz->id,
                        'business_name' => $biz->business_name,
                        'email' => $biz->email,
                        'website' => $biz->website,
                        'phone' => $biz->phone,
                        'category' => $biz->category,
                        'country' => $biz->country,
                        'mobile_pagespeed' => optional($biz->audit)->mobile_pagespeed,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                foreach (array_chunk($rows, 500) as $chunk) {
                    ContactListLead::insert($chunk);
                }
                $list->update([
                    'total_contacts' => ContactListLead::where('contact_list_id', $list->id)->count(),
                ]);
            }

            return $list;
        });

        return response()->json([
            'success' => true,
            'message' => 'Contact list created successfully.',
            'data' => $contactList,
        ], 201);
    }

    /**
     * Show Contact List
     */
    public function show(ContactList $contactList)
    {
        $this->authorizeTenant($contactList);

        return response()->json([
            'success' => true,
            'data' => $contactList,
        ]);
    }

    /**
     * Update Contact List
     */
    public function update(Request $request, ContactList $contactList)
    {
        $this->authorizeTenant($contactList);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $contactList->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact list updated successfully.',
            'data' => $contactList,
        ]);
    }

    /**
     * Delete Contact List
     */
    public function destroy(ContactList $contactList)
    {
        $this->authorizeTenant($contactList);

        $contactList->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact list deleted successfully.',
        ]);
    }

    /**
     * Add Manual Contact directly to Contact List (Without touching global businesses table)
     */
    public function addManualContact(ContactList $contactList, Request $request)
    {
        $this->authorizeTenant($contactList);

        $request->validate([
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'website' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'mobile_pagespeed' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $contactLead = ContactListLead::create([
            'contact_list_id' => $contactList->id,
            'business_id' => null, // Private custom contact
            'business_name' => $request->business_name,
            'email' => $request->email,
            'website' => $request->website,
            'phone' => $request->phone,
            'category' => $request->category,
            'country' => $request->country,
            'mobile_pagespeed' => $request->mobile_pagespeed,
            'notes' => $request->notes,
        ]);

        $contactList->update([
            'total_contacts' => ContactListLead::where('contact_list_id', $contactList->id)->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact added successfully to list.',
            'data' => $contactLead,
        ], 201);
    }

    /**
     * Import Custom CSV File directly into Contact List (Without modifying global businesses table)
     */
    public function importCsv(ContactList $contactList, Request $request)
    {
        $this->authorizeTenant($contactList);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            return response()->json([
                'success' => false,
                'message' => 'CSV file is empty or invalid format.',
            ], 400);
        }

        $headerMap = [];
        foreach ($header as $index => $col) {
            $normalized = strtolower(trim(str_replace([' ', '_', '-'], '', $col)));
            if (in_array($normalized, ['name', 'businessname', 'company', 'companyname'])) {
                $headerMap['name'] = $index;
            } elseif (in_array($normalized, ['email', 'emailaddress'])) {
                $headerMap['email'] = $index;
            } elseif (in_array($normalized, ['website', 'site', 'url'])) {
                $headerMap['website'] = $index;
            } elseif (in_array($normalized, ['phone', 'telephone', 'mobile'])) {
                $headerMap['phone'] = $index;
            } elseif (in_array($normalized, ['category', 'industry'])) {
                $headerMap['category'] = $index;
            } elseif (in_array($normalized, ['country', 'location'])) {
                $headerMap['country'] = $index;
            } elseif (in_array($normalized, ['pagespeed', 'psi', 'score'])) {
                $headerMap['mobile_pagespeed'] = $index;
            }
        }

        if (!isset($headerMap['email'])) {
            return response()->json([
                'success' => false,
                'message' => 'CSV must contain an "email" column.',
            ], 400);
        }

        $now = now();
        $rows = [];
        $importedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $email = isset($headerMap['email']) && isset($row[$headerMap['email']]) ? trim($row[$headerMap['email']]) : '';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $name = isset($headerMap['name']) && isset($row[$headerMap['name']]) ? trim($row[$headerMap['name']]) : explode('@', $email)[0];
            $website = isset($headerMap['website']) && isset($row[$headerMap['website']]) ? trim($row[$headerMap['website']]) : null;
            $phone = isset($headerMap['phone']) && isset($row[$headerMap['phone']]) ? trim($row[$headerMap['phone']]) : null;
            $category = isset($headerMap['category']) && isset($row[$headerMap['category']]) ? trim($row[$headerMap['category']]) : null;
            $country = isset($headerMap['country']) && isset($row[$headerMap['country']]) ? trim($row[$headerMap['country']]) : null;
            $pagespeed = isset($headerMap['mobile_pagespeed']) && isset($row[$headerMap['mobile_pagespeed']]) ? (int)trim($row[$headerMap['mobile_pagespeed']]) : null;

            $rows[] = [
                'contact_list_id' => $contactList->id,
                'business_id' => null,
                'business_name' => $name,
                'email' => $email,
                'website' => $website,
                'phone' => $phone,
                'category' => $category,
                'country' => $country,
                'mobile_pagespeed' => $pagespeed,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $importedCount++;
        }
        fclose($handle);

        if (!empty($rows)) {
            foreach (array_chunk($rows, 500) as $chunk) {
                ContactListLead::insert($chunk);
            }
            $contactList->update([
                'total_contacts' => ContactListLead::where('contact_list_id', $contactList->id)->count(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully imported {$importedCount} contact(s) into list.",
            'data' => [
                'imported_count' => $importedCount,
                'total_contacts' => $contactList->fresh()->total_contacts,
            ],
        ]);
    }

    /**
     * Update Contact Lead details (Modifies ONLY contact_list_leads, NOT main businesses table!)
     */
    public function updateLead(ContactList $contactList, ContactListLead $contactListLead, Request $request)
    {
        $this->authorizeTenant($contactList);

        if ($contactListLead->contact_list_id !== $contactList->id) {
            abort(404, 'Contact not found in this list.');
        }

        $request->validate([
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'website' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'mobile_pagespeed' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $contactListLead->update([
            'business_name' => $request->business_name,
            'email' => $request->email,
            'website' => $request->website,
            'phone' => $request->phone,
            'category' => $request->category,
            'country' => $request->country,
            'mobile_pagespeed' => $request->mobile_pagespeed,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact details updated in list successfully.',
            'data' => $contactListLead,
        ]);
    }

    /**
     * Remove Lead from Contact List
     */
    public function removeLead(ContactList $contactList, ContactListLead $contactListLead)
    {
        $this->authorizeTenant($contactList);

        if ($contactListLead->contact_list_id === $contactList->id) {
            $contactListLead->delete();
        }

        $contactList->update([
            'total_contacts' => ContactListLead::where('contact_list_id', $contactList->id)->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact removed from contact list.',
        ]);
    }

    /**
     * Get Leads inside Contact List
     */
    public function getLeads(Request $request, ContactList $contactList)
    {
        $this->authorizeTenant($contactList);

        $query = $contactList->listLeads()->with('business.audit');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('website', 'LIKE', "%{$search}%");
            });
        }

        $leads = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $leads,
        ]);
    }

    /**
     * Export Contact List as CSV
     */
    public function export(ContactList $contactList)
    {
        $this->authorizeTenant($contactList);

        $fileName = 'contacts_' . \Illuminate\Support\Str::slug($contactList->name) . '_' . date('Y-m-d') . '.csv';
        $leads = $contactList->listLeads()->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Business Name', 'Email', 'Website', 'Phone', 'Category', 'Country', 'PageSpeed Score', 'Notes'];

        $callback = function () use ($leads, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->business_name,
                    $lead->email,
                    $lead->website,
                    $lead->phone,
                    $lead->category,
                    $lead->country,
                    $lead->mobile_pagespeed ?? '',
                    $lead->notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
