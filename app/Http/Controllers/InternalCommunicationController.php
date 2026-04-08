<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFarmOwner;
use App\Models\InternalMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InternalCommunicationController extends Controller
{
    use ResolvesFarmOwner;

    public function contactHr()
    {
        $farmOwner = $this->getFarmOwner();

        $messages = InternalMessage::where('farm_owner_id', $farmOwner->id)
            ->where(function ($query) {
                $query->where('sender_role', 'hr')
                    ->orWhere('recipient_role', 'hr');
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('communication.index', [
            'messages' => $messages,
            'pageTitle' => 'Contact HR',
            'recipientRole' => 'hr',
            'allowedTypes' => ['general', 'payslip_edit_request'],
            'layout' => 'farmowner.layouts.app',
            'header' => 'Contact HR',
            'subheader' => 'View payslip edit requests and HR concerns',
        ]);
    }

    public function contactFinance()
    {
        $farmOwner = $this->getFarmOwner();

        $messages = InternalMessage::where('farm_owner_id', $farmOwner->id)
            ->where(function ($query) {
                $query->where('sender_role', 'finance')
                    ->orWhere('recipient_role', 'finance');
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('communication.index', [
            'messages' => $messages,
            'pageTitle' => 'Contact Finance',
            'recipientRole' => 'finance',
            'allowedTypes' => ['general'],
            'layout' => 'farmowner.layouts.app',
            'header' => 'Contact Finance',
            'subheader' => 'Send and view finance concerns',
        ]);
    }

    public function departmentInbox(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        $user = Auth::user();

        abort_unless($user?->isDepartmentRole(), 403);

        $currentRole = (string) $user->role;
        $recipientRole = $request->input('to', 'farm_owner');

        if (!in_array($recipientRole, ['farm_owner', 'hr', 'finance'], true)) {
            $recipientRole = 'farm_owner';
        }

        $messages = InternalMessage::where('farm_owner_id', $farmOwner->id)
            ->where(function ($query) use ($currentRole) {
                $query->where('sender_role', $currentRole)
                    ->orWhere('recipient_role', $currentRole);
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('communication.index', [
            'messages' => $messages,
            'pageTitle' => 'Internal Communication',
            'recipientRole' => $recipientRole,
            'allowedTypes' => ['general'],
            'layout' => $currentRole === 'hr' ? 'hr.layouts.app' : 'department.layouts.app',
            'header' => 'Internal Communication',
            'subheader' => 'Coordinate with farm owner, HR, and finance',
            'currentRole' => $currentRole,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $farmOwner = $this->getFarmOwner();
        } catch (\Exception $e) {
            return back()->withErrors('Unable to identify farm owner. Please try again.');
        }

        $sender = Auth::user();
        
        if (!$sender) {
            abort(401, 'User not authenticated');
        }

        $validated = $request->validate([
            'recipient_role' => 'required|in:farm_owner,hr,finance',
            'message_type' => 'nullable|in:general,payslip_edit_request,payroll_approval',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Set message type based on user role
        $messageType = $validated['message_type'] ?? 'general';

        if (!$sender->isFarmOwner() && $messageType !== 'general') {
            $messageType = 'general';
        }

        try {
            InternalMessage::create([
                'farm_owner_id' => $farmOwner->id,
                'sender_id' => (int) $sender->id,
                'sender_role' => (string) $sender->role,
                'recipient_role' => $validated['recipient_role'],
                'message_type' => $messageType,
                'subject' => $validated['subject'],
                'message' => $validated['message'],
            ]);

            return back()->with('success', 'Message sent successfully.');
        } catch (\Exception $e) {
            \Log::error('Internal message creation failed', [
                'sender_id' => $sender->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->withErrors('Failed to send message. Please try again.');
        }
    }
}
