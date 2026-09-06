<?php

namespace App\Livewire\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    // True once credentials have been verified and the session has been
    // regenerated. The frontend waits for this before actually navigating,
    // so the split animation always has time to play first.
    public bool $authenticated = false;

    public function login(): void
    {
        $this->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Reuse the existing LoginRequest so throttling, multi-account
        // password matching, and the deactivated-account check all stay
        // exactly as they were under the controller-based flow.
        $formRequest = LoginRequest::createFrom(request());
        $formRequest->setContainer(app());
        $formRequest->merge([
            'email'    => $this->email,
            'password' => $this->password,
            'remember' => $this->remember,
        ]);

        // Throws ValidationException on bad credentials/lockout — Livewire
        // catches this the same way it catches its own validate() calls,
        // so $errors populates in the view automatically.
        $formRequest->authenticate();

        request()->session()->regenerate();

                $this->authenticated = true;

        // Pass the destination URL along with this event so the browser
        // can navigate client-side via Livewire.navigate() once the split
        // animation finishes — no second Livewire request needed. A second
        // round-trip here would fail: session()->regenerate() above just
        // rotated the CSRF token, so any further Livewire AJAX call made
        // with the page's now-stale token gets rejected with 419, which
        // triggers Livewire's own "This page has expired" dialog.
        $this->dispatch('login-success', url: route('dashboard'));
    }

        #[Layout('layouts.auth')]
    public function render()
    {
        return view('livewire.auth.login');
    }
}