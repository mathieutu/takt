<?php

namespace App\Http\Controllers;

use App\Models\ReceivedShare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReceivedShareController
{
    public function destroy(Request $request, ReceivedShare $receivedShare): RedirectResponse
    {
        abort_unless($request->user()->id === $receivedShare->user_id, 403);

        $receivedShare->delete();

        return back();
    }
}
