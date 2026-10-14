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
        return $this->renderTab('policy');
    }

    public function purposes()
    {
        return $this->renderTab('purposes');
    }

    public function liability()
    {
        return $this->renderTab('liability');
    }

    private function renderTab(string $activeTab)
    {
        $privacy = PrivacySetting::getActive();

        if ($privacy->privacy_policy_content) {
            $converter = new CommonMarkConverter([
                'html_input' => 'allow',
                'allow_unsafe_links' => false,
            ]);

            $privacy->privacy_policy_content = (string) $converter->convert($privacy->privacy_policy_content);
        }

        return view('privacy.show', [
            'privacy' => $privacy,
            'activeTab' => $activeTab,
        ]);
    }
}
