<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrivacyPolicyRequest;
use App\Models\PrivacySetting;
use League\CommonMark\CommonMarkConverter;

class PrivacyController extends Controller
{
    public function index()
    {
        $this->authorizePermission('users');
        $privacy = PrivacySetting::getActive();

        return view('privacy.index', compact('privacy'));
    }

    public function update(StorePrivacyPolicyRequest $request)
    {
        $this->authorizePermission('users');
        $privacy = PrivacySetting::getActive();
        $privacy->update($request->validated());

        return redirect()->route('privacy.index')->with('success', 'Privacy settings updated.');
    }

    public function policy()
    {
        $privacy = PrivacySetting::getActive();

        $converter = new CommonMarkConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $privacy->privacy_policy_content = $privacy->privacy_policy_content
            ? (string) $converter->convert($privacy->privacy_policy_content)
            : null;

        return view('privacy.policy', compact('privacy'));
    }

    public function purposes()
    {
        $privacy = PrivacySetting::getActive();

        return view('privacy.purposes', compact('privacy'));
    }

    public function liability()
    {
        return view('privacy.liability');
    }
}
