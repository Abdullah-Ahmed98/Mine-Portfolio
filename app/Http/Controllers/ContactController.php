<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;

/**
 * The contact form lives in the contact section of the single page, so only the
 * submission is handled here.
 */
class ContactController extends Controller
{
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::create([
            ...$request->validated(),
            'ip_address' => $request->ip(),
        ]);

        return back()
            ->with('status', Setting::get('contact.success', 'Thanks — your message has been sent.'))
            ->with('contact_sent', true);
    }
}
