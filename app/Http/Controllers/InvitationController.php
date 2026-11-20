<?php

namespace App\Http\Controllers;

use App\Contracts\InvitationServiceInterface;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvitationController extends Controller
{
    public function __construct(private readonly InvitationServiceInterface $invitations) {}

    public function create(Request $request): View
    {
        abort_unless($request->user()?->canInvite() === true, Response::HTTP_FORBIDDEN);

        return view('invitations.create', [
            'roles' => Role::ALL,
        ]);
    }

    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $invitation = $this->invitations->invite(
            $request->user(),
            $request->validated('email'),
            $request->validated('role'),
            (bool) $request->validated('new_company'),
            $request->validated('company_name')
        );

        return redirect()
            ->route('invitations.create')
            ->with('status', 'Invitation created.')
            ->with('invite_url', route('invitations.accept', $invitation->token));
    }

    public function show(string $token): View
    {
        $invitation = Invitation::query()
            ->with(['role:id,name', 'company:id,name'])
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        return view('invitations.accept', [
            'invitation' => $invitation,
        ]);
    }

    public function accept(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $user = $this->invitations->accept(
            $token,
            $request->validated('name'),
            $request->validated('password')
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('short-urls.index');
    }
}
